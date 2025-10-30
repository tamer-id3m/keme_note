<?php

namespace App\Services\V4\OnDemandSmartNote;

use App\Models\User;
use App\Helpers\Helpers;
use App\Enums\QueueStatus;
use App\Traits\QueueTrait;
use App\Models\v3\QueueList;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use App\Services\AI\OpenAIService;
use Illuminate\Support\Facades\Log;
use App\Models\v3\OnDemandSmartNote;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Jobs\ProcessOnDemandSmartNote;
use Elastic\ScoutDriverPlus\Support\Query;
use App\Services\V4\AiNote\AiNoteServiceInterface;
use Elastic\ScoutDriverPlus\Builders\BoolQueryBuilder;
use App\Http\Resources\V4\OnDemandSmartNote\OnDemandSmartNoteResource;

/**
 * Class OnDemandSmartNoteService
 *
 * This service handles the business logic for managing on-demand smart notes.
 * It provides methods for listing, retrieving, creating, updating, and deleting on-demand smart notes.
 *
 * @package App\Services\OnDemandSmartNote
 */

class OnDemandSmartNoteService
{
    use ApiResponseTrait, QueueTrait;

    protected AiNoteServiceInterface $aiNoteService;

    /**
     * Constructor for AINoteService.
     *
     * @param OpenAIService $openaiService The OpenAI service used for generating AI responses.
     */
    public function __construct(AiNoteServiceInterface $aiNoteService)
    {
        $this->aiNoteService = $aiNoteService;
        $this->setQueueModel("OnDemandSmartNote");
    }

    /**
     * Retrieve a paginated list of on-demand smart notes.
     *
     * This method handles the logic for fetching a paginated list of on-demand smart notes.
     * It supports sorting and pagination based on the request parameters.
     *
     * @param Request $request The HTTP request object containing query parameters.
     * @return \Illuminate\Http\JsonResponse The JSON response containing the paginated list of on-demand smart notes.
     *
     * @throws \Exception If an error occurs while fetching the data.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $sortBy = $request->sort_by ?? 'id';
            $sort = $request->sort ?? 'desc';
            $perPage = $request->per_page ?? 10;
            $user = Auth::user();

            $cacheKey = Helpers::generateCacheKey($request, 'on_demand_notes_index_');

            Cache::tags(['OnDemandSmartNotes'])->flush();

            $onDemandSmartNotes = Cache::tags('OnDemandSmartNotes')->remember($cacheKey, 3600, function () use ($sort, $sortBy, $perPage, $user) {

                $query = OnDemandSmartNote::with(['queueLists', 'patient', 'doctor', 'approver']);

                $query->where('is_shared', 1);

                if ($user->hasRole('Doctor')) {
                    $query->where('doctor_id', $user->id);
                }
                elseif ($user->hasRole('Staff')) {
                    $doctorIds = $user->staffDoctors->pluck('id')->toArray();
                    $query->whereIn('doctor_id', $doctorIds);
                }
                elseif($user->hasRole('Pcm')) {
                    $doctorIds = $user->pcmDoctors->pluck('id')->toArray();
                    $query->whereIn('doctor_id', $doctorIds);
                }

                return $query->orderBy($sortBy, $sort)
                    ->paginate($perPage ?? Helpers::getPagination());
            });

            $data = OnDemandSmartNoteResource::collection($onDemandSmartNotes)->response()->getData(true);

            return $this->ApiResponse('success', 200, $data);

        } catch (\Exception $e) {
            return $this->ApiResponse('An error occurred while fetching on demand smart notes', 500, [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Retrieve a specific on-demand smart note by its ID.
     *
     * This method handles the logic for fetching a single on-demand smart note by its ID.
     * If the note is not found, it returns a 400 error response.
     *
     * @param int $id The ID of the on-demand smart note to retrieve.
     * @return \Illuminate\Http\JsonResponse The JSON response containing the on-demand smart note data.
     *
     * @throws \Exception If an error occurs while fetching the data.
     */
    public function show($id): JsonResponse
    {
        try {
            $onDemandSmartNote = OnDemandSmartNote::findOrFail($id);

            $data = new OnDemandSmartNoteResource($onDemandSmartNote);

            return $this->ApiResponse('success', 200, $data);
        } catch (\Exception $e) {
            return $this->ApiResponse('Failed to retrieve on demand smart note', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Create a new on-demand smart note.
     *
     * This method handles the logic for creating a new on-demand smart note using the validated request data.
     *
     * @param Request $request The HTTP request object containing the validated data for the new note.
     * @return \Illuminate\Http\JsonResponse The JSON response containing the created on-demand smart note data.
     *
     * @throws \Exception If an error occurs while creating the note.
     */
    public function store(Request $request): JsonResponse
    { 
        $validatedData = $request->validated();
        if ($validatedData['patient_id'] == 'none') {
            $validatedData['patient_id'] = null;
        }

        try {

            $onDemandSmartNote = OnDemandSmartNote::create($validatedData);

            $this->dispatchQueue($request, $onDemandSmartNote);

            Cache::tags(['OnDemandSmartNotes'])->flush();

            $data = new OnDemandSmartNoteResource($onDemandSmartNote);

            return $this->ApiResponse('On Demand Smart Note is being processed.', 200, $data);
        } catch (\Exception $e) {
            return $this->ApiResponse('Failed to process On Demand Smart note', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Update an existing on-demand smart note by its ID.
     *
     * This method handles the logic for updating an existing on-demand smart note using the validated request data.
     * If the note is not found, it returns a 400 error response.
     *
     * @param Request $request The HTTP request object containing the validated data for the update.
     * @param int $id The ID of the on-demand smart note to update.
     * @return \Illuminate\Http\JsonResponse The JSON response containing the updated on-demand smart note data.
     *
     * @throws \Exception If an error occurs while updating the note.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            // Log::info("[Update] Starting update process for ID: $id");

            $validatedData = $request->validated();

            $onDemandSmartNote = OnDemandSmartNote::findOrFail($id);
            // Log::info("[Update] Found existing note", ['original_data' => $onDemandSmartNote->toArray()]);
            // Log::info("[Update] Setting default values", [
            //     'original_doctor_id' => $onDemandSmartNote->doctor_id,
            //     'original_patient_id' => $onDemandSmartNote->patient_id,
            //     'original_approved_by' => $onDemandSmartNote->approved_by
            // ]);

            $this->prepareData($onDemandSmartNote,$validatedData);

            $onDemandSmartNote->update($validatedData);

            Cache::tags(['OnDemandSmartNotes'])->flush();
            // Log::info("[Update] Note updated successfully", ['updated_fields' => array_keys($validatedData)]);

            $data = new OnDemandSmartNoteResource($onDemandSmartNote);
            // Log::info("[Update] Returning successful response");
            return $this->ApiResponse('Note Updated Successfully', 200, $data);
        } catch (\Exception $e) {
            Log::info("[Update] Error occurred", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'note_id' => $id
            ]);
            return $this->ApiResponse('Failed to retrieve on demand smart notes', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Delete an on-demand smart note by its ID.
     *
     * This method handles the logic for deleting an on-demand smart note by its ID.
     * If the note is not found, it returns a 400 error response.
     *
     * @param int $id The ID of the on-demand smart note to delete.
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the result of the deletion.
     */
    public function destroy($id): JsonResponse
    {
        $onDemandSmartNote = OnDemandSmartNote::findOrFail($id);

        $queue = QueueList::where('note_id', $id)
            ->whereNot("type", QueueStatus::IN_PROGRESS->value)
            ->get()
            ->last();

        if ($queue) {
            $onDemandSmartNote->delete();
            QueueList::where('note_id', $id)->delete();
        }

        $onDemandSmartNote->delete();

        Cache::tags(['OnDemandSmartNotes'])->flush();

        return $this->ApiResponse('Note Deleted Successfully', 200);
    }

    /**
     * Delete a queue list entry by its ID.
     *
     * This method attempts to locate a queue list record by the given ID.
     * If the record exists, it is deleted from the database.
     * If it does not exist, a 400 response with an error message is returned.
     *
     * @param int $id The ID of the queue list item to delete.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Exception If an unexpected error occurs during the delete process.
     */
    public function deleteQueueList($id): JsonResponse
    {
        $queueList = QueueList::where('id', $id)
            ->first();

        if (!$queueList) {
            return $this->ApiResponse(__('main.IdNotFound'), 400, []);
        }

        $queueList->delete();

        return $this->ApiResponse('Queue Deleted Successfully', 200);
    }

    /**
     * Retrieve on-demand smart notes for a given patient.
     *
     * This method fetches notes associated with the specified patient ID. If the authenticated user
     * has the 'Doctor' role, it filters the notes to only include those created by that doctor.
     *
     * @param int $patient_id The ID of the patient whose notes are to be retrieved.
     * @return \Illuminate\Http\JsonResponse JSON response containing the notes or an error message.
     */
    public function getNotesByPatient($request, $patientId): JsonResponse
    {
        try {
            $perPage       = max(1, (int) $request->input('per_page', 10));
            $sortBy        = $request->input('sort_by', 'id');
            $sortDirection = $request->input('sort_dir', 'desc');
            $user = Auth::user();

            // Cache::tags(['OnDemandSmartNotes'])->flush();

            $cacheKey = Helpers::generateCacheKey(
                $request,
                "on_demand_smart_notes::patient::{$patientId}"
            );

            $notes = Cache::tags(['OnDemandSmartNotes'])->remember($cacheKey, 3600, function () use ($patientId, $user, $sortBy, $perPage, $sortDirection) {
                $noteIds = $this->elsticQuery($patientId, $user);

                if (empty($noteIds)) {
                    return collect();
                }

                return OnDemandSmartNote::whereIn('id', $noteIds)
                    ->where('patient_id', $patientId)
                    ->orderBy($sortBy, $sortDirection)
                    ->paginate($perPage ?? Helpers::getPagination());
            });

            if ($notes->isEmpty()) {
                return $this->ApiResponse('No notes found for this patient', 200, []);
            }

            $data = OnDemandSmartNoteResource::collection($notes)->response()->getData(true);

            return $this->ApiResponse('success', 200, $data);

        } catch (\Exception $e) {
            return $this->ApiResponse('An error occurred while fetching notes', 500, [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Approve an on-demand smart note.
     *
     * This method validates the request data, updates the approval status of the specified smart note,
     * and processes the AI diagnosis. If the note is not found, an error response is returned.
     *
     * @param \Illuminate\Http\Request $request The request containing approval details.
     * @param int $id The ID of the on-demand smart note to be approved.
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure.
     *
     */
    public function noteApprove($request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            $onDemandSmartNote = OnDemandSmartNote::findOrFail($id);

            $onDemandSmartNote->update([
                'approved_by' => Auth::id(),
                'approved' => 1,
                'is_shared' => 0,
                'approval_date' => $validatedData['approval_date'] ?? $onDemandSmartNote->approval_date,
                'ai_diagnosis' => $validatedData['ai_diagnosis'],
            ]);

            $resource = 'on_demand';
            $clinicalNotes = $this->aiNoteService->parseNoteField($request->ai_diagnosis);


            $this->aiNoteService->createOrUpdateClinicalNote($clinicalNotes, $request, $onDemandSmartNote, $resource);


            Cache::tags(['clinical_notes'])->flush();
            Cache::tags(['OnDemandSmartNotes'])->flush();

            $data = new OnDemandSmartNoteResource($onDemandSmartNote);
            return $this->ApiResponse('Approved Successfully', 200, $data);

        } catch (\Exception $e) {
            return $this->ApiResponse('Failed to retrieve on demand smart notes', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Regenerates an On-Demand Smart Note by dispatching a processing job.
     *
     * @param int $id The ID of the On-Demand Smart Note to regenerate.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response indicating success or failure.
     *
     */
    public function regenerate($id): JsonResponse
    {
        try {
            $onDemandSmartNote = OnDemandSmartNote::findOrFail($id);

            $user = Auth::id();
        
            $this->createQueue($user, $onDemandSmartNote->id);
            dispatch(new ProcessOnDemandSmartNote($onDemandSmartNote, $user));
            // Log::info("[Update] Job dispatched to transcripts queue", [
            //     'job_type' => 'ProcessOnDemandSmartNote',
            //     'note_id' => $onDemandSmartNote->id
            // ]);

            Cache::tags(['OnDemandSmartNotes'])->flush();

            $data = new OnDemandSmartNoteResource($onDemandSmartNote);

            return $this->ApiResponse('Note Regenerated Successfully', 200, $data);
        } catch (\Exception $e) {
            return $this->ApiResponse('Failed to regenerate Demand Smart note', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Retrieve the authenticated user's current queue orders.
     *
     * This method returns the order of each queue item for the authenticated user, along with:
     * - the associated note ID and name (from either `onDemandSmartNote` or `publicAppointmentSummary`)
     * - the current queue status (e.g., QUEUED or IN_PROGRESS)
     * - the order in the global queue
     * - the total number of active queues
     *
     * The queue order is calculated based on the user's queue position relative to all active queues.
     *
     * @return \Illuminate\Http\JsonResponse JSON response containing user-specific queue order data.
     */
    public function getUserQueueOrders(): JsonResponse
    {
        $allQueues = QueueList::whereIn("type", [QueueStatus::QUEUED->value, QueueStatus::IN_PROGRESS->value])->get();
        $user = User::with("queuesList.onDemandSmartNote")->find(Auth::id());

        $userOrders = [];
        foreach ($user->queuesList as $userQueue) {
            $noteId = '';
            $noteName = '';

            if ($userQueue->onDemandSmartNote) {
                $noteId = $userQueue->onDemandSmartNote->id;
                $noteName = $userQueue->onDemandSmartNote->note;
            } elseif ($userQueue->publicAppointmentSummary) {
                $noteId = $userQueue->publicAppointmentSummary->id;
                $noteName = $userQueue->publicAppointmentSummary->patient_name;
            }

            $userOrders[] = [
                'note'          => ["id" => $noteId, "name" => $noteName],
                "status"        => $userQueue->type,
                'order'         => $this->getUserOrder($allQueues, $userQueue),
                'total'         => count($allQueues),
                "queue_list_id" => $userQueue->id,
            ];
        }

        $userOrders = collect($userOrders)->sortBy('order')->values()->toArray();

        return $this->ApiResponse('success', 200, $userOrders);
    }

    /**
     * Build and execute an Elasticsearch query to retrieve note IDs for a specific patient and user.
     *
     * This method constructs a boolean query using the Elasticsearch DSL to filter notes by patient ID.
     * If the user has a "Doctor" role, the query is further filtered by the doctor's ID.
     *
     * @param int $patient_id The ID of the patient to filter notes for.
     * @param \App\Models\User $user The currently authenticated user making the request.
     *
     * @return array List of matching OnDemandSmartNote IDs from Elasticsearch.
     */
    private function elsticQuery($patient_id, $user): array
    {
        $builder = new BoolQueryBuilder();
        $builder->must(Query::term()->field('patient_id')->value($patient_id));

        if ($user->hasRole('Doctor')) {
            $builder->must(Query::term()->field('doctor_id')->value($user->id));
        }

        $searchResults = OnDemandSmartNote::searchQuery($builder)
            ->size(Helpers::getElasticQuerySize(OnDemandSmartNote::class, $builder))
            ->execute()
            ->raw();

        return collect($searchResults['hits']['hits'])->pluck('_id')->all();
    }

    /**
     * Checks whether the currently authenticated user has an active queue for the given note.
     *
     * Active means the queue type is either QUEUED or IN_PROGRESS.
     *
     * @param int $onDemandSmartNoteId The ID of the smart note.
     * @return bool True if the user has an active queue for the note, false otherwise.
     */
    private function userHasQueue($onDemandSmartNoteId): bool
    {
        $count = QueueList::where('note_id', $onDemandSmartNoteId)->where('user_id', Auth::id())
            ->whereIn("type", [QueueStatus::QUEUED->value, QueueStatus::IN_PROGRESS->value])
            ->count();
        return $count > 0;
    }

    /**
     * Gets the current user's position in the queue list for a specific smart note.
     *
     * This method loops through all queues and returns the sequential position of the user’s queue entry.
     *
     * @param \Illuminate\Support\Collection $allQueues All queue entries related to the note.
     * @param \App\Models\v3\QueueList $userQueue The specific queue item for the current user.
     *
     * @return int|null The position/order of the user's queue, or null if not found.
     */
    private function getUserOrder($allQueues, $userQueue)
    {
        $count = 1;

        foreach ($allQueues as $queue) {
            if (($queue->note_id == $userQueue->note_id && $queue->user_id == $userQueue->user_id)) {
                return $count;
            } else {
                $count++;
            }
        }
    }

    /**
     * Dispatches a job to process the On-Demand Smart Note.
     *
     * This function checks if a secondary context (`context2_id`) exists.
     * If so, it dispatches a job for context2 processing, otherwise the standard processing job is dispatched.
     * It also creates an initial queue entry for the current user.
     *
     * @param \Illuminate\Http\Request $request The current request containing input data.
     * @param \App\Models\v3\OnDemandSmartNote $onDemandSmartNote The smart note being processed.
     *
     * @return void
     */
    private function dispatchQueue($request, $onDemandSmartNote): void
    {
        $user = Auth::id();

        $this->createQueue($user, $onDemandSmartNote->id);
        dispatch(new ProcessOnDemandSmartNote($onDemandSmartNote, $user));
        Log::info("Starting create job");
    }

    /**
     * Prepares and normalizes smart note data before further processing.
     *
     * This method fills missing or optional fields with fallback values from the existing note.
     * It also handles special cases like 'none' patient ID or missing context2.
     *
     * @param \App\Models\v3\OnDemandSmartNote $onDemandSmartNote The note used as reference for default values.
     *
     * @return void
     */
    private function prepareData($onDemandSmartNote, &$validatedData): void
    {
        $validatedData['doctor_id'] = $validatedData['doctor_id'] ?? $onDemandSmartNote->doctor_id;
        $validatedData['approved_by'] = $validatedData['approved_by'] ?? $onDemandSmartNote->approved_by;
        $validatedData['approved'] = $validatedData['approved'] ?? $onDemandSmartNote->approved;
        $validatedData['is_shared'] = $onDemandSmartNote->is_shared;
        $validatedData['approval_date'] = $validatedData['approval_date'] ?? $onDemandSmartNote->approval_date;

        if (array_key_exists('patient_id', $validatedData)) {
            $validatedData['patient_id'] = $validatedData['patient_id'] === 'none'
                ? null
                : $validatedData['patient_id'];
        }
    }
}