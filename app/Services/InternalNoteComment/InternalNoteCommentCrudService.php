<?php

namespace App\Services\InternalNoteComment;

use App\Models\User;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\InternalNote;
use App\Models\NoteComment;
use App\Traits\ApiResponseTrait;
use App\Models\CommentHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\Notification\NotifyUserService;
use App\Http\Resources\InternalNote\InternalNoteHistoryResource;
use App\Http\Resources\InternalNoteComment\InternalNoteCommentResource;

/**
 * Class InternalNoteCommentCrudService
 *
 * Service class responsible for handling the CRUD operations related to internal note comments.
 * It leverages the provided services to notify about internal note comments and users.
 *
 * @package App\Services
 */
class InternalNoteCommentCrudService 
{
    use ApiResponseTrait;

    protected $notifyIntenalNoteCommentService;
    protected $notifyUserService;

    /**
     * InternalNoteCommentCrudService constructor.
     *
     * Initializes the service with dependencies for notifying internal note comments
     * and notifying users.
     *
     * @param  \App\Services\InternalNoteComment\NotifyIntenalNoteCommentService  $notifyIntenalNoteCommentService  The service responsible for notifying about internal note comments.
     * @param  \App\Services\Notification\NotifyUserService  $notifyUserService  The service responsible for notifying users.
     */

    public function __construct(NotifyIntenalNoteCommentService $notifyIntenalNoteCommentService, NotifyUserService $notifyUserService)
    {
        $this->notifyIntenalNoteCommentService = $notifyIntenalNoteCommentService;
        $this->notifyUserService   = $notifyUserService;
    }

    /**
     * Retrieve all comments for a specific patient.
     *
     * @param  int  $id  The ID of the patient whose comments are to be fetched.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns a list of comments for the specified patient.
     *                                       - User Not Found: Returns a 404 response if the specified patient ID is not found.
     *                                       - Error: Returns a 500 response for unexpected exceptions during processing.
     */
    public function index($id): JsonResponse
    {
        $comments = NoteComment::with('user')->where('patient_id', $id)->get();
        $patient = User::findOrFail($id);
        $clinicTimeZone = Clinic::where('id', $patient->clinic_id)->pluck('time_zone')->first();

        $comments = $comments->map(function ($comment) use ($clinicTimeZone) {
            return (new InternalNoteCommentResource($comment))->withTimeZone($clinicTimeZone);
        });

        return $this->ApiResponse('success', 200, $comments);
    }

    /**
     * Store a new internal note comment and notify relevant users.
     *
     * This method creates a new note comment, handles any mentions in the comment body,
     * and sends notifications to the admin users associated with the note.
     * The transaction ensures data integrity and consistency. If an error occurs, the transaction is rolled back.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming request containing the validated data for creating the comment.
     *
     * @return \Illuminate\Http\JsonResponse  Returns a JSON response with the appropriate message and the created comment resource
     *         if successful, or an error message if an exception occurs.
     */
    public function store($request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $validData = $request->validated();
            $comment = NoteComment::create($validData + ['updated_at' => now()]);
             $note = InternalNote::where('id', $request->internal_note_id)->first();
             $patient = Patient::where('id', $note->patient_id)->first();
             $patientUuid = $patient->uuid ?? null;

            if (! $comment) {
                DB::rollBack();
                return $this->ApiResponse('Failed to create comment', 500);
            }

            $this->notifyIntenalNoteCommentService->handleMentions($validData['body'], $comment, $patientUuid);

            $users = User::whereHas('roles', function ($query) {
                $query->where('name', 'Admin');
            })->where('id', '!=', Auth::id())->get();

            $this->sendNotification($users, $comment, $patientUuid);

            DB::commit();

            return $this->ApiResponse('Added Successfully', 201, new InternalNoteCommentResource($comment));
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->ApiResponse('Error occurred during retrieval', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Retrieve a specific comment by ID.
     *
     * @param  int  $id  The ID of the comment to retrieve.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns the comment details.
     *                                       - Comment Not Found: Returns a 404 response if the comment does not exist.
     */
    public function show($id): JsonResponse
    {
        $comment = NoteComment::with('user')->findOrFail($id);

        return $this->ApiResponse('success', 200, new InternalNoteCommentResource($comment));
    }

    /**
     * Update an existing internal note comment and handle notifications for mentions.
     *
     * This method updates an existing note comment by editing its body and marking it as edited.
     * It handles mentions in the comment body, saves the changes, and stores the comment's history for auditing purposes.
     * A transaction is used to ensure data integrity. If an error occurs, the transaction is rolled back.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming request containing the validated data for updating the comment.
     * @param  int  $noteId  The ID of the internal note the comment is associated with.
     * @param  int  $commentId  The ID of the comment to be updated.
     *
     * @return \Illuminate\Http\JsonResponse  Returns a JSON response with the appropriate message and the updated comment resource
     *         if successful, or an error message if an exception occurs.
     */
    public function update($request, $noteId, $commentId): JsonResponse
    {
        DB::beginTransaction();

        try {
            $validData = $request->validated();
            $oldComment = NoteComment::where('internal_note_id', $noteId)
                ->where('id', $commentId)
                ->firstOrFail();

            $note = InternalNote::where('id', $noteId)->first();
            $patient = Patient::where('id', $note->patient_id)->first();
            $patientUuid = $patient->uuid ?? null;

            $this->storeCommentHistory($oldComment);

            $oldComment->update([
                'body' => $validData['body'],
                'edited' => true,
                'updated_at' => now(),
            ]);

            $this->notifyIntenalNoteCommentService->handleMentions($validData['body'], $oldComment, $patientUuid);

            DB::commit();

            return $this->ApiResponse('Updated Successfully', 200, new InternalNoteCommentResource($oldComment));
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->ApiResponse('Error occurred during retrieval', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Delete an existing comment.
     *
     * @param  int  $noteId  The ID of the note the comment belongs to.
     * @param  int  $commentId  The ID of the comment to delete.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns a success message upon deletion.
     *                                       - Comment Not Found: Returns a 404 response if the comment does not exist.
     */
    public function destroy($noteId, $commentId): JsonResponse
    {
        $comment = NoteComment::where('internal_note_id', $noteId)->where('id', $commentId)->firstOrFail();

        $comment->delete();

        return $this->ApiResponse('Deleted Successfully', 200, '');
    }

    /**
     * Retrieve the history of a specific comment.
     *
     * This method fetches all history records associated with the given comment ID,
     * orders them by creation date in descending order, and returns them as a collection
     * of InternalNoteHistoryResource.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming HTTP request.
     * @param  int  $commentId  The ID of the comment for which history is being retrieved.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns a list of history records for the specified comment.
     *                                       - Error: Returns a 500 response if an unexpected error occurs.
     */
    public function retrieveCommentHistory($commentId): JsonResponse
    {
        // Fetch all history records related to the specified comment ID
        $notes = CommentHistory::where('comment_id', $commentId)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->ApiResponse('success', 200, InternalNoteHistoryResource::collection($notes));
    }

    /**
     * Store the history of a comment before updating it.
     *
     * @param  \App\Models\NoteComment  $comment  The original comment object before the update.
     * @return void
     */
    private function storeCommentHistory(NoteComment $comment): void
    {
        CommentHistory::create([
            'body' => $comment->body,
            'user_id' => $comment->user_id,
            'patient_id' => $comment->patient_id,
            'internal_note_id' => $comment->internal_note_id,
            'comment_id' => $comment->id,
            'edited_by' => Auth::id(),
        ]);
    }

    /**
     * Send a notification to the given users about a new staff note comment.
     *
     * @param \Illuminate\Support\Collection|\App\Models\User[] $users List of users to notify (excluding the author).
     * @param \App\Models\NoteComment $comment The newly created or updated comment.
     * @param string|null $patientUuid The UUID of the patient related to the note.
     *
     * @return void
     */
    private function sendNotification($users, $comment, $patientUuid): void
    {
        $authUser = Auth::user();
        $fullName = "{$authUser->name} {$authUser->last_name}";

        foreach ($users as $user) {
            $senderId = $authUser->id;
            $title = 'Staff note comment created!';
            $message = $fullName . ' commented on staff note';
            $type = 'note';
            $patientId = $comment->patient_id;
            $noteId = $comment->internal_note_id;

            $this->notifyUserService->notifyUser(
                $user,
                $senderId,
                $title,
                $message,
                $type,
                $patientId,
                $noteId,
                $patientUuid
            );
        }
    }
}