<?php

namespace App\Http\Controllers\V4;

use App\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use App\Services\InternalNote\IntenalNoteCrudOperationsService;

/**
 * Class InternalNoteHistoryController
 *
 * This controller handles the retrieval of the history for internal notes.
 * It provides an endpoint to fetch all history records associated with a specific
 * internal note, ordered by the date they were updated.
 */
class InternalNoteHistoryController extends Controller
{
    use ApiResponseTrait;

    protected $intenalNoteCrudOperationsService;

    /**
     * Class constructor.
     *
     * Initializes the `IntenalNoteCrudOperationsService` instance to be used for internal note history operations.
     *
     * @param  \App\Services\InternalNote\IntenalNoteCrudOperationsService  $intenalNoteCrudOperationsService
     * An instance of the `IntenalNoteCrudOperationsService` to be used by this controller for performing CRUD operations on internal notes.
     */

    public function __construct(IntenalNoteCrudOperationsService $intenalNoteCrudOperationsService)
    {
        $this->intenalNoteCrudOperationsService = $intenalNoteCrudOperationsService;
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
    public function __invoke($internalNoteID)
    {
        return $this->intenalNoteCrudOperationsService->getInternalNoteHistory($internalNoteID);
    }
}