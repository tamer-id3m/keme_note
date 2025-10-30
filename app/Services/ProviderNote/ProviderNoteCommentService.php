<?php

namespace App\Services\V4\ProviderNote;

use App\Http\Resources\V4\ProviderNote\ProviderCommentHistoryResource;
use App\Http\Resources\V4\ProviderNote\ProviderNoteCommentResource;
use App\Models\ProviderNoteComment;
use App\Models\ProviderNoteCommentHistory;
use App\Models\User;
use App\Models\v3\ProviderNote;
use App\Services\Notification\NotifyUserService;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;

/**
 * Class ProviderNoteCommentService
 *
 * This controller handles CRUD operations for provider note comments.
 * It manages creating, retrieving, updating, and deleting provider note comments,
 * including maintaining a history of updates.
 */
class ProviderNoteCommentService
{
    use ApiResponseTrait;

    protected $notifyUserService;

    public function __construct(NotifyUserService $notifyUserService)
    {

        $this->notifyUserService = $notifyUserService;
    }

    /**
     * Retrieve a list of provider note comments.
     *
     * Fetches all provider note comments along with associated user roles.
     *
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns the list of provider note comments with associated user roles.
     *                                       - Error: Returns a 500 response if an unexpected error occurs during processing.
     */
    public function index()
    {
        $comments = ProviderNoteComment::with(['user.roles'])->get();

        $data = ProviderNoteCommentResource::collection($comments)->response()->getData(true);

        return $this->ApiResponse('success', 200, $data);
    }

    /**
     * Retrieve a specific provider note comment.
     *
     * Fetches a single provider note comment by its ID, including associated user roles.
     *
     * @param  int  $id  The ID of the provider note comment to retrieve.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns the provider note comment with associated user roles.
     *                                       - Not Found: Returns a 404 response if the comment ID is not found.
     *                                       - Error: Returns a 500 response if an unexpected error occurs during processing.
     */
    public function show($id)
    {
        $comment = ProviderNoteComment::with(['user.roles'])->findOrFail($id);

        return $this->ApiResponse('success', 200, new ProviderNoteCommentResource($comment));
    }

    /**
     * Store a new provider note comment.
     *
     * Creates a new provider note comment based on the validated input data.
     *
     * @param  \App\Http\Requests\CreateProviderNoteCommentRequest  $request  The incoming request containing validated comment data.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns the newly created provider note comment.
     *                                       - Validation Error: Returns a 422 response if validation fails.
     *                                       - Error: Returns a 500 response for any unexpected exceptions during processing.
     */
    public function store($request)
    {
        $validatedData = $request->validated();
        $user = Auth::user();

        // Create the provider note comment for all roles
        $comment = ProviderNoteComment::create($validatedData);

        // Notify PCMs if the user is a Doctor
        if ($user->hasRole('Doctor')) {
            $doctor_id = $user->id;
            $fullName = $user->name . ' ' . ($user->last_name ?? '');
            $pcms = User::role('Pcm')->where('doctor_id', $doctor_id)->get();
            $patient = User::role('Patient')->where('id', $validatedData['patient_id'])->first();

            foreach ($pcms as $pcm) {
                $this->notifyUserService->notifyUser(
                    $pcm,
                    $user->id,
                    'You have been mentioned in a note',
                    "$fullName added New Provider Request comment",
                    'request',
                    $comment->user_id,
                    $comment->provider_note_id,
                    $patient->uuid
                );
            }
        }

        // Notify the doctor if the user is a PCM
        if ($user->hasRole('Pcm')) {
            $providerNote = ProviderNote::find($request->provider_note_id);
            $noteDoctorId = $providerNote->doctor_id;
            $doctorIds = $user->pcmDoctors->pluck('id')->toArray();
            if (in_array($noteDoctorId, $doctorIds)) {
                $fullName = $user->name . ' ' . ($user->last_name ?? '');
                $doctor = User::role('Doctor')->where('id', $noteDoctorId)->first();
                $patient = User::role('Patient')->where('id', $validatedData['patient_id'])->first();

                $this->notifyUserService->notifyUser(
                    $doctor,
                    $user->id,
                    'You have been mentioned in a note',
                    "$fullName added New Provider Request comment",
                    'request',
                    $comment->user_id,
                    $comment->provider_note_id,
                    $patient->uuid
                );
            }
        }
        $admins = User::role('Admin')->get();
        $fullName = $user->name . ' ' . ($user->last_name ?? '');
        $patient = User::role('Patient')->where('id', $validatedData['patient_id'])->first();

        foreach ($admins as $admin) {
            $this->notifyUserService->notifyUser(
                $admin,
                $user->id,
                'You have been mentioned in a note',
                "$fullName added New Provider Request comment",
                'request',
                $comment->user_id,
                $comment->provider_note_id,
                $patient->uuid
            );
        }

        return $this->ApiResponse('Added Successfully', 201, new ProviderNoteCommentResource($comment));
    }

    /**
     * Update an existing provider note comment.
     *
     * Updates the provider note comment with new data and saves the original data to the history.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming request containing the updated data.
     * @param  int  $id  The ID of the provider note comment to update.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns the updated provider note comment.
     *                                       - Not Found: Returns a 404 response if the comment ID is not found.
     *                                       - Error: Returns a 500 response for unexpected exceptions during processing.
     */
    public function update($request, $id)
    {
        $comment = ProviderNoteComment::with(['user.roles'])->findOrFail($id);

        $this->storeHistory($comment);

        $comment->update($request->validated() + ['edited' => 1]);

        return $this->ApiResponse('Updated Successfully', 200, new ProviderNoteCommentResource($comment));
    }

    /**
     * Store the original comment data in the history.
     *
     * @param  \App\Models\ProviderNoteComment  $comment
     * @return void
     */
    private function storeHistory($comment)
    {
        ProviderNoteCommentHistory::create([
            'body' => $comment->body,
            'user_id' => $comment->user_id,
            'patient_id' => $comment->patient_id,
            'provider_note_comment_id' => $comment->id,
            'edited_by' => Auth::id(),
        ]);
    }

    /**
     * Delete a provider note comment.
     *
     * Removes the specified provider note comment from the system.
     *
     * @param  int  $id  The ID of the provider note comment to delete.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns a 200 response if the comment was deleted successfully.
     *                                       - Not Found: Returns a 404 response if the comment ID is not found.
     *                                       - Error: Returns a 500 response for unexpected exceptions during processing.
     */
    public function destroy($id)
    {
        $comment = ProviderNoteComment::findOrFail($id);

        $comment->delete();

        return $this->ApiResponse('Deleted Successfully', 200);
    }

    /**
     * Retrieve the history of a specific provider note comment.
     *
     * @param  int  $commentId  The ID of the provider note comment.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCommentHistory($commentId)
    {
        $comments = ProviderNoteCommentHistory::with('user')
            ->where('provider_note_comment_id', $commentId)
            ->orderBy('updated_at', 'asc')
            ->get();

        return $this->ApiResponse('success', 200, ProviderCommentHistoryResource::collection($comments));
    }
}