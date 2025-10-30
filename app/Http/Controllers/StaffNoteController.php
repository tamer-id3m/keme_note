<?php

namespace App\Http\Controllers\V3;

use App\Http\Controllers\Controller;
use App\Http\Requests\V3\StaffNote\CreateStaffNoteRequest;
use App\Http\Requests\V3\StaffNote\UpdateStaffNoteRequest;
use App\Services\StaffNote\StaffNoteService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

/**
 * Class StaffNoteController
 *
 * This controller handles CRUD operations for staff notes. It includes
 * permission checks, notifications, and mention handling in notes.
 * It also utilizes the ApiResponseTrait for standardized API responses.
 */
class StaffNoteController extends Controller
{
    use ApiResponseTrait;

    protected $model;

    protected $staffNoteService;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct(StaffNoteService $staffNoteService)
    {
        $this->model = 'App\Models\v3\StaffNote';
        $this->staffNoteService = $staffNoteService;
        // $this->middleware('permission:staffNote-list', ['only' => ['index']]);
        // $this->middleware('permission:staffNote-show', ['only' => ['show']]);
        // $this->middleware('permission:staffNote-create', ['only' => ['store']]);
        // $this->middleware('permission:staffNote-edit', ['only' => ['update']]);
        // $this->middleware('permission:staffNote-delete', ['only' => ['destroy']]);
    }

    /**
     * Delegates the staff note retrieval to the StaffNoteService.
     *
     * This method calls the `index` method in the `StaffNoteService` to handle the logic of fetching
     * and returning the paginated list of staff notes for a specific patient, with sorting and pagination applied.
     *
     * @param  \Illuminate\Http\Request  $request  The HTTP request instance containing sorting and pagination parameters.
     * @param  int  $id  The patient ID for which to fetch the staff notes.
     * @return \Illuminate\Http\JsonResponse JSON response with the paginated list of staff notes.
     */
    public function index(Request $request, $id)
    {
        return $this->staffNoteService->index($request, $id);
    }

    /**
     * Delegates the staff note creation to the StaffNoteService.
     *
     * This method calls the `store` method in the `StaffNoteService` to handle the logic of creating a new staff note.
     * It ensures that the request is validated through the `CreateStaffNoteRequest` before delegating the processing to the service.
     *
     * @param  \App\Http\Requests\CreateStaffNoteRequest  $request  The validated request containing the staff note data.
     * @return \Illuminate\Http\JsonResponse JSON response with the result of the staff note creation.
     */
    public function store(CreateStaffNoteRequest $request)
    {
        return $this->staffNoteService->store($request);
    }

    /**
     * Delegates the staff note retrieval to the StaffNoteService.
     *
     * This method calls the `show` method in the `StaffNoteService` to handle the logic of retrieving a specific staff note
     * by its UUID. It ensures that the service handles the retrieval and response generation.
     *
     * @param  string  $uuid  The UUID of the staff note to retrieve.
     * @return \Illuminate\Http\JsonResponse JSON response with the staff note data or an error message.
     */
    public function show($uuid)
    {
        return $this->staffNoteService->show($uuid);
    }

    /**
     * Handles the update of a staff note by delegating the request to the staffNoteService.
     *
     * This method validates the update request and passes it along with the staff note UUID
     * to the corresponding service method (`staffNoteService->update`).
     * The service is responsible for finding and updating the staff note and returning the appropriate response.
     *
     * @param  \App\Http\Requests\UpdateStaffNoteRequest  $request  The request containing the validated data to update the staff note.
     * @param  string  $uuid  The UUID of the staff note to update.
     * @return \Illuminate\Http\JsonResponse The response containing the updated staff note data or an error message.
     */
    public function update(UpdateStaffNoteRequest $request, $uuid)
    {
        return $this->staffNoteService->update($request, $uuid);
    }

    /**
     * Deletes a staff note by delegating to the staffNoteService.
     *
     * This method validates the request and passes the UUID of the staff note to the service's `destroy` method.
     * The service is responsible for handling the deletion and returning the appropriate response.
     *
     * @param  string  $uuid  The UUID of the staff note to delete.
     * @return \Illuminate\Http\JsonResponse The response indicating the result of the deletion operation.
     */
    public function destroy($uuid)
    {
        return $this->staffNoteService->destroy($uuid);
    }
}