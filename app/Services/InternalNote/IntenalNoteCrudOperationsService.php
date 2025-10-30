<?php

namespace App\Services\InternalNote;

use App\Helpers\Helpers;
use App\Models\InternalNote;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\InternalNoteHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Elastic\ScoutDriverPlus\Support\Query;
use App\Services\Notification\NotifyUserService;
use Elastic\ScoutDriverPlus\Builders\BoolQueryBuilder;
use App\Services\InternalNote\NotifyInternalNoteService;
use App\Http\Resources\InternalNote\InternalNoteResource;
use App\Http\Resources\InternalNote\InternalNoteHistoryResource;
use App\Http\Resources\InternalNote\InternalNoteWithCommentsResource;

/**
 * Class InternalNoteCrudOperationsService
 *
 * Service class responsible for handling CRUD operations related to internal notes.
 * It utilizes the provided services for notifying about internal notes and notifying users.
 *
 * @package App\Services
 */

class IntenalNoteCrudOperationsService
{
    use ApiResponseTrait;

    protected $notifyInternalNoteService;
    protected $notifyUserService;

    /**
     * InternalNoteCrudOperationsService constructor.
     *
     * Initializes the service with dependencies for notifying internal notes
     * and notifying users.
     *
     * @param  \App\Services\Notification\NotifyInternalNoteService  $notifyInternalNoteService  The service responsible for notifying about internal notes.
     * @param  \App\Services\Notification\NotifyUserService  $notifyUserService  The service responsible for notifying users.
     */
    public function __construct(NotifyInternalNoteService $notifyInternalNoteService, NotifyUserService $notifyUserService)
    {
        $this->notifyInternalNoteService = $notifyInternalNoteService;
        $this->notifyUserService = $notifyUserService;
    }

    /**
     * Retrieve a list of internal notes for a patient.
     *
     * Fetches internal notes related to a specific patient, with optional sorting and filtering.
     *
     * @param  \Illuminate\Http\Request  $request  The request containing optional filters and sorting parameters.
     * @param  int  $id  The ID of the patient for whom the internal notes are being retrieved.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns a paginated list of internal notes with associated user and note comments.
     *                                       - Unauthorized: Returns a 403 response if the user lacks permission to view internal notes.
     *                                       - Patient Not Found: Returns a 404 response if the specified patient ID is not found.
     *                                       - Error: Returns a 500 response for unexpected errors during the retrieval process.
     */
    public function index($request, $id): mixed
    {
        try {
            $sort = $request->get('sort', 'desc');
            $sortBy = $request->get('sortBy', 'id');
            $page = $request->input('page', 1);
            $page = max(1, (int)$page);
            $perPage = $request->input('per_page', Helpers::ContextPagination());
            $filter = $request->input('id');
            $cacheKey = Helpers::generateCacheKey($request, 'InternalNote_index_'.$id);

            $notes = Cache::tags(['internalNote'])->remember($cacheKey, 3600, function () use (
                $request,
                $id,
                $sort,
                $sortBy,
                $perPage,
                $page,
                $filter
            ) {

                $baseQuery = InternalNote::where('patient_id', $id);

                if ($request->filled('id')) {

                    $ids = $this->elsticQuery( $filter);

                    return $baseQuery->whereIn('id', $ids)
                        ->orderBy($sortBy, $sort)
                        ->paginate($perPage, ['*'], 'page', $page);
                }

                return $baseQuery->orderBy($sortBy, $sort)
                    ->paginate($perPage, ['*'], 'page', $page);
            });

            $data = InternalNoteResource::collection($notes)->response()->getData(true);

            return $this->apiResponse('success', 200, $data);

        } catch (\Exception $e) {
            return $this->apiResponse('An error occurred', 500, null, $e->getMessage());
        }
    }

    /**
     * Store a new internal note and notify relevant users.
     *
     * This method creates a new internal note, notifies users mentioned in the note's body,
     * and sends notifications to users with the necessary permissions for internal note creation.
     * The operation is wrapped in a transaction to ensure data integrity. If an error occurs, the transaction is rolled back.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming request containing the validated data for creating the internal note.
     *
     * @return \Illuminate\Http\JsonResponse  Returns a JSON response with the appropriate message and the created internal note resource
     *         if successful, or an error message if an exception occurs.
     */
    public function store($request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $validData = $request->validated();
            $note = InternalNote::create($validData + ['updated_at' => now()]);

            if (! $note) {
                DB::rollBack();

                return $this->ApiResponse('Failed to create note', 500);
            }

            $this->notifyInternalNoteService->notifyMentionedUsers($validData['body'], $note);
            $this->notifyInternalNoteService->notifyUsersWithPermission(
                'internalnote-create',
                'New Staff Note',
                Auth::user()->name . ' created a staff note.',
                $note
            );

            DB::commit();

            Cache::tags(['internalNote'])->flush();

            return $this->ApiResponse('Added Successfully', 201, new InternalNoteResource($note));
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->ApiResponse('Error occurred while creating note', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified internal note.
     *
     * Retrieves a single internal note by its ID, along with the associated user and comments.
     *
     * @param  int  $note  The ID of the internal note to be retrieved.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns the internal note with associated data.
     *                                       - Unauthorized: Returns a 403 response if the user lacks permission to view the note.
     *                                       - Note Not Found: Returns a 404 response if the specified note is not found.
     *                                       - Error: Returns a 500 response for unexpected errors during retrieval.
     */
    public function show($id): JsonResponse
    {
        $note = InternalNote::with(['user', 'noteComments'])->findOrFail($id);

        return $this->ApiResponse('success', 200, new InternalNoteWithCommentsResource($note));
    }

    /**
     * Update an existing internal note and notify relevant users.
     *
     * This method updates an existing internal note, creates a history record for the previous note data,
     * and sends notifications to mentioned users and users with specific permissions about the update.
     * The operation is wrapped in a transaction to ensure data integrity. If an error occurs, the transaction is rolled back.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming request containing the validated data for updating the internal note.
     * @param  int  $id  The ID of the internal note to be updated.
     *
     * @return \Illuminate\Http\JsonResponse  Returns a JSON response with the appropriate message and the updated internal note resource
     *         if successful, or an error message if an exception occurs.
     */
    public function update($request, $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $validData = $request->validated();

            $oldNote = InternalNote::findOrFail($id);

            InternalNoteHistory::create([
                'body' => $oldNote->body,
                'user_id' => $oldNote->user_id,
                'patient_id' => $oldNote->patient_id,
                'internal_note_id' => $oldNote->id,
                'edited_by' => Auth::id(),
            ]);

            $oldNote->update([
                'body' => $validData['body'],
                'edited' => true,
                'updated_at' => now(),
            ]);

            $this->notifyInternalNoteService->notifyMentionedUsers($validData['body'], $oldNote);
            $this->notifyInternalNoteService->notifyUsersWithPermission(
                'internalnote-edit',
                'Staff Note Updated!',
                Auth::user()->name . ' updated a note.',
                $oldNote
            );

            DB::commit();

            Cache::tags(['internalNote'])->flush();

            return $this->ApiResponse('Updated Successfully', 200, new InternalNoteResource($oldNote));
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->ApiResponse('Error occurred while updating note', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * delete the specified internal note by its ID.
     *
     * This method finds an internal note by its ID and performs delete,
     * marking the record as deleted with permanently removing it from the database.
     * It returns a success response once the deletion is completed.
     *
     * @param  int  $id  The ID of the internal note to be soft deleted.
     * @return \Illuminate\Http\JsonResponse A JSON response:
     *                                       - Success: Returns a success message upon deletion.
     *                                       - Unauthorized: Returns a 403 response if the user lacks permission to delete the note.
     *                                       - Note Not Found: Returns a 404 response if the internal note is not found.
     *                                       - Error: Returns a 500 response for unexpected errors during the deletion process.
     *
     * @throws \Throwable If any unexpected error occurs during the process.
     *
     * @OA\Delete(
    *     path="/v4/internal-notes/delete/{id}",
    *     operationId="deleteInternalNote",
    *     tags={"Internal Notes"},
    *     summary="Delete an internal note",
    *     description="Soft deletes an internal note by ID and flushes the cache.",
    *     security={{"bearerAuth":{}}},
    *
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         required=true,
    *         description="The ID of the internal note to delete",
    *         @OA\Schema(type="integer", example=5)
    *     ),
    *
    *     @OA\Response(
    *         response=200,
    *         description="Note deleted successfully",
    *         @OA\JsonContent(
    *             @OA\Property(property="message", type="string", example="Deleted Successfully"),
    *             @OA\Property(property="data", type="string", example=""),
    *             @OA\Property(property="status_code", type="integer", example=200)
    *         )
    *     ),
    *
    *     @OA\Response(
    *         response=404,
    *         description="Note not found"
    *     ),
    *
    *     @OA\Response(
    *         response=403,
    *         description="Unauthorized access"
    *     ),
    *
    *     @OA\Response(
    *         response=500,
    *         description="Unexpected server error"
    *     )
    * )
     */
    public function destroy($id): JsonResponse
    {
        $note = InternalNote::findOrfail($id);
        $note->delete();
        Cache::tags(['internalNote'])->flush();

        return $this->ApiResponse('Deleted Successfully', 200, '');
    }

    /**
     * Retrieve all history records for a specific internal note.
     *
     * This method fetches the history of an internal note based on the provided internal note ID.
     * It retrieves records in ascending order of their last updated timestamp and returns them
     * in a paginated JSON response.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming request object.
     * @param  int  $internalNoteID  The ID of the internal note for which the history is being retrieved.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns a list of internal note history records.
     *                                       - Unauthorized: Returns a 403 response if the user lacks permission to view the history.
     *                                       - Internal Note Not Found: Returns a 404 response if no history is found for the given note ID.
     *                                       - Error: Returns a 500 response for unexpected errors during the history retrieval process.
     */
    public function getInternalNoteHistory($internalNoteID): JsonResponse
    {
        // Fetch all notes related to the given internal note ID
        $notes = InternalNoteHistory::with('user')->where('internal_note_id', $internalNoteID)->orderBy('updated_at', 'asc')->get();

        return $this->ApiResponse('success', 200, InternalNoteHistoryResource::collection($notes));
    }

    /**
     * Perform an Elasticsearch query to filter internal notes by ID.
     *
     * @param mixed $filter The filter value used to match the internal note ID.
     *
     * @return array Returns an array of matching internal note IDs from Elasticsearch results.
     */
    private function elsticQuery($filter): array
    {
        $builder = new BoolQueryBuilder();

        if (!empty($filter)) {
            $builder->must(Query::term()->field('id')->value((int)$filter));
        }

        $searchResults = InternalNote::searchQuery($builder)
        ->size(Helpers::getElasticQuerySize(InternalNote::class, $builder))
        ->execute()
        ->raw();

        $ids = collect($searchResults['hits']['hits'])->pluck('_id')->all();

        return $ids;
    }
}