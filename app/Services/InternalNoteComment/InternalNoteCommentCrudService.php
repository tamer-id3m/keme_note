<?php

namespace App\Services\InternalNoteComment;

use App\Models\InternalNote;
use App\Models\NoteComment;
use App\Traits\ApiResponseTrait;
use App\Models\CommentHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Clients\UserClient;
use App\Http\Clients\ClinicClient;
use App\Services\Notification\NotifyUserService;
use App\Http\Resources\InternalNote\InternalNoteHistoryResource;
use App\Http\Resources\InternalNoteComment\InternalNoteCommentResource;

class InternalNoteCommentCrudService 
{
    use ApiResponseTrait;

    protected $notifyIntenalNoteCommentService;
    protected $notifyUserService;
    protected $userClient;
    protected $clinicClient;

    public function __construct(
        NotifyIntenalNoteCommentService $notifyIntenalNoteCommentService, 
        NotifyUserService $notifyUserService,
        UserClient $userClient,
        ClinicClient $clinicClient
    ) {
        $this->notifyIntenalNoteCommentService = $notifyIntenalNoteCommentService;
        $this->notifyUserService = $notifyUserService;
        $this->userClient = $userClient;
        $this->clinicClient = $clinicClient;
    }

    /**
     * Retrieve all comments for a specific patient.
     */
    public function index($id): JsonResponse
    {
        $comments = NoteComment::with('user')->where('patient_id', $id)->get();
        
        // Get patient and clinic timezone from external services
        $patientUser = $this->userClient->getPatientById($id);
        $clinicTimeZone = null;
        
        if ($patientUser && $patientUser->clinic_id) {
            $clinicTimeZone = $this->clinicClient->getClinicTimezone($patientUser->clinic_id);
        }

        $comments = $comments->map(function ($comment) use ($clinicTimeZone) {
            return (new InternalNoteCommentResource($comment))->withTimeZone($clinicTimeZone);
        });

        return $this->ApiResponse('success', 200, $comments);
    }

    /**
     * Store a new internal note comment and notify relevant users.
     */
    public function store($request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $validData = $request->validated();
            $comment = NoteComment::create($validData + ['updated_at' => now()]);
            
            $note = InternalNote::where('id', $request->internal_note_id)->first();
            
            // Get patient UUID from User Service
            $patientUuid = null;
            if ($note && $note->patient_id) {
                $patientUser = $this->userClient->getPatientById($note->patient_id);
                $patientUuid = $patientUser->uuid ?? null;
            }

            if (! $comment) {
                DB::rollBack();
                return $this->ApiResponse('Failed to create comment', 500);
            }

            $this->notifyIntenalNoteCommentService->handleMentions($validData['body'], $comment, $patientUuid);

            // Get Admin users from User Service
            $users = $this->userClient->getUsersByRole('Admin');
            $users = $users->filter(function ($user) {
                return $user->id != Auth::id();
            });

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
     */
    public function show($id): JsonResponse
    {
        $comment = NoteComment::with('user')->findOrFail($id);
        return $this->ApiResponse('success', 200, new InternalNoteCommentResource($comment));
    }

    /**
     * Update an existing internal note comment.
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
            
            // Get patient UUID from User Service
            $patientUuid = null;
            if ($note && $note->patient_id) {
                $patientUser = $this->userClient->getPatientById($note->patient_id);
                $patientUuid = $patientUser->uuid ?? null;
            }

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
     */
    public function destroy($noteId, $commentId): JsonResponse
    {
        $comment = NoteComment::where('internal_note_id', $noteId)->where('id', $commentId)->firstOrFail();
        $comment->delete();
        return $this->ApiResponse('Deleted Successfully', 200, '');
    }

    /**
     * Retrieve the history of a specific comment.
     */
    public function retrieveCommentHistory($commentId): JsonResponse
    {
        $notes = CommentHistory::where('comment_id', $commentId)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->ApiResponse('success', 200, InternalNoteHistoryResource::collection($notes));
    }

    /**
     * Store the history of a comment before updating it.
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