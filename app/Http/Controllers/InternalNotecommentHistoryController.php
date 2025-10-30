<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\InternalNoteComment\InternalNoteCommentCrudService;
use App\Traits\ApiResponseTrait;

/**
 * Class InternalNotecommentHistoryController
 *
 * This controller handles retrieving the history of internal note comments based on a given comment ID.
 * It fetches all notes related to the comment and returns them in descending order by creation date.
 */
class InternalNotecommentHistoryController extends Controller
{
    use ApiResponseTrait;

    protected $internalNoteCommentCrudService;

    public function __construct(InternalNoteCommentCrudService $internalNoteCommentCrudService)
    {
        $this->internalNoteCommentCrudService = $internalNoteCommentCrudService;
    }

    /**
     * Invoke method to retrieve the history of a specific comment.
     *
     * This method retrieves all history records related to the specified comment ID,
     * orders them by creation date in descending order, and returns them as a collection
     * of InternalNoteHistoryResource.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming HTTP request.
     * @param  int  $commentId  The ID of the comment whose history is being retrieved.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns a list of note history records for the specified comment.
     *                                       - Error: Returns a 500 response if an unexpected error occurs.
     */
    public function __invoke($commentId)
    {
        return $this->internalNoteCommentCrudService->retrieveCommentHistory($commentId);
    }
}