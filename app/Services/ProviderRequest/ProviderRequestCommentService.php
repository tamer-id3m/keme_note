<?php

namespace App\Services\V4\ProviderRequest;

use App\Http\Resources\V3\ProviderRequestCommentHistoryResource;
use App\Http\Resources\V4\ProviderRequest\ProviderRequestCommentResource;
use App\Models\v3\ProviderRequestComment;
use App\Models\v3\ProviderRequestCommentHistory;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;

/**
 * Class ProviderRequestCommentController
 *
 * Handles CRUD operations for provider request comments, with access control
 * based on user roles. Tracks historical data for updates.
 */
class ProviderRequestCommentService
{
    use ApiResponseTrait;

    /**
     * Retrieve a list of provider request comments.
     *
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns a list of provider request comments with associated user roles.
     *                                       - Unauthorized: Returns a 403 response if the user lacks the appropriate role.
     */
    public function index()
    {
        if (! $this->hasPermission()) {
            return $this->apiResponse('You Don\'t Have The Permission', 403);
        }

        $comments = ProviderRequestComment::with(['user.roles'])->get();
        $data = ProviderRequestCommentResource::collection($comments)->response()->getData(true);

        return $this->apiResponse('Success', 200, $data);
    }

    /**
     * Retrieve a specific provider request comment by UUID.
     *
     * @param  string  $id  The UUID of the provider request comment.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns the specified provider request comment with associated user roles.
     *                                       - Not Found: Returns a 404 response if the comment is not found.
     */
    public function show($id)
    {
        if (! $this->hasPermission()) {
            return $this->apiResponse('You Don\'t Have The Permission', 403);
        }

        $comment = $this->findComment($id);
        if (! $comment) {
            return $this->apiResponse('ID Not Found', 404);
        }

        return $this->apiResponse('Success', 200, new ProviderRequestCommentResource($comment));
    }

    /**
     * Store a newly created provider request comment.
     *
     * @param  \App\Http\Requests\V3\ProviderRequest\ProviderRequestComments\StoreProviderRequestComment  $request
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns the created provider request comment.
     *                                       - Error: Returns a 500 response if the comment creation fails.
     */
    public function store($request)
    {
        if (! $this->hasPermission()) {
            return $this->apiResponse('You Don\'t Have The Permission', 403);
        }

        $validatedData = $request->validated();
        $comment = ProviderRequestComment::create($validatedData);

        if (! $comment) {
            return $this->apiResponse('Creation Failed', 500);
        }

        return $this->apiResponse('Added Successfully', 200, new ProviderRequestCommentResource($comment));
    }

    /**
     * Update an existing provider request comment.
     *
     * @param  \App\Http\Requests\V3\ProviderRequest\ProviderRequestComments\UpdateProviderRequestComment  $request
     * @param  string  $id  The UUID of the provider request comment to be updated.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns the updated provider request comment.
     *                                       - Not Found: Returns a 404 response if the comment is not found.
     */
    public function update($request, $id)
    {
        $comment = $this->findComment($id);
        if (! $comment) {
            return $this->apiResponse('ID Not Found', 404);
        }

        $validatedData = $request->validated();
        $this->logCommentHistory($comment);

        $comment->update($validatedData + ['edited' => 1]);

        return $this->apiResponse('Updated Successfully', 200, new ProviderRequestCommentResource($comment));
    }

    /**
     * Log the history of a provider request comment before updating.
     *
     * @return void
     */
    private function logCommentHistory(ProviderRequestComment $comment)
    {
        ProviderRequestCommentHistory::create([
            'body' => $comment->body,
            'user_id' => $comment->user_id,
            'patient_id' => $comment->patient_id,
            'provider_note_comment_id' => $comment->id,
            'edited_by' => Auth::id(),
        ]);
    }

    /**
     * Delete an existing provider request comment.
     *
     * @param  string  $id  The UUID of the provider request comment to be deleted.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns a message confirming the deletion.
     *                                       - Not Found: Returns a 404 response if the comment is not found.
     */
    public function destroy($id)
    {
        $comment = $this->findComment($id);
        if (! $comment) {
            return $this->apiResponse('ID Not Found', 404);
        }

        $comment->delete();

        return $this->apiResponse('Deleted Successfully', 200);
    }

    /**
     * Check if the authenticated user has the required role permissions.
     *
     * @return bool
     */
    private function hasPermission()
    {
        return Auth::user()->hasAnyRole(['Admin', 'Doctor', 'Pcm']);
    }

    /**
     * Find a provider request comment by UUID.
     *
     * @param  string  $id  The UUID of the provider request comment.
     * @return \App\Models\v3\ProviderRequestComment|null
     */
    private function findComment($id)
    {
        return ProviderRequestComment::with(['user.roles'])->where('uuid', $id)->first();
    }

    /**
     * Retrieve the history of a specific provider request comment.
     *
     * Fetches all historical records of a provider request comment, including details about the
     * user who made changes. The history is ordered by the `updated_at` timestamp in ascending order.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming request.
     * @param  string  $commentId  The ID of the provider request comment whose history is being retrieved.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns a list of historical versions of the provider request comment.
     *                                       - Error: Returns a 500 response for unexpected exceptions during processing.
     */
    public function getCommentEditHistory($commentId)
    {
        $comments = ProviderRequestCommentHistory::with('user')->where('provider_note_comment_id', $commentId)->orderBy('updated_at', 'asc')->get();

        return $this->apiResponse('success', 200, ProviderRequestCommentHistoryResource::collection($comments));
    }
}