<?php

namespace App\Http\Controllers\V4;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\OnDemandSmartNote\NoteApproveRequest;
use App\Services\V4\OnDemandSmartNote\OnDemandSmartNoteService;
use App\Http\Requests\OnDemandSmartNote\CreateOnDemandSmartNoteRequest;
use App\Http\Requests\OnDemandSmartNote\UpdateOnDemandSmartNoteRequest;

/**
 * Class OnDemandSmartNoteController
 *
 * This controller handles the HTTP requests related to on-demand smart notes.
 * It delegates the business logic to the `OnDemandSmartNoteService` class.
 *
 * @package App\Http\Controllers\V4
 *
 * @OA\Tag(
 *    name="On-Demand Smart Notes",
 *    description="Endpoints for managing OnDemand Smart Notes data",
 * )
 */
class OnDemandSmartNoteController extends Controller
{
    /**
    * The service instance for handling on-demand smart note business logic.
    *
    * @var OnDemandSmartNoteService
    */
    protected $onDemandSmartNoteService;

    /**
     * OnDemandSmartNoteController constructor.
     *
     * Initializes the controller by injecting an instance of `OnDemandSmartNoteService`.
     *
     * @param OnDemandSmartNoteService $onDemandSmartNoteService
    */
    public function __construct(OnDemandSmartNoteService $onDemandSmartNoteService)
    {
        $this->onDemandSmartNoteService = $onDemandSmartNoteService;

        $this->middleware('permission:on-demand-smart-note-list', ['only' => ['index']]);
        $this->middleware('permission:on-demand-smart-note-create', ['only' => ['store']]);
        $this->middleware('permission:on-demand-smart-note-show', ['only' => ['show']]);
        $this->middleware('permission:on-demand-smart-note-edit', ['only' => ['update']]);
        $this->middleware('permission:on-demand-smart-note-delete', ['only' => ['destroy']]);
        $this->middleware('permission:on-demand-smart-note-regenerate', ['only' => ['regenerate']]);
    }

    /**
     * Display a listing of on-demand smart notes.
     *
     * This method handles the HTTP GET request to retrieve a list of on-demand smart notes.
     * It delegates the request to the `index` method of the `OnDemandSmartNoteService`.
     *
     * @param Request $request The HTTP request object.
     * @return mixed The response returned by the service.
     *
     * @OA\Get(
    *     path="/v4/on-demand-smart-notes/index",
    *     summary="Get paginated list of On-Demand Smart Notes",
    *     description="Returns a paginated list of On-Demand Smart Notes filtered by user role (Doctor or Staff), supports sorting and pagination.",
    *     operationId="getOnDemandSmartNotes",
    *     tags={"On-Demand Smart Notes"},
    *     security={{"bearerAuth":{}}},
    *
    *     @OA\Parameter(
    *         name="sort_by",
    *         in="query",
    *         description="Column to sort by (e.g., 'id', 'created_at')",
    *         required=false,
    *         @OA\Schema(type="string", default="id")
    *     ),
    *     @OA\Parameter(
    *         name="sort",
    *         in="query",
    *         description="Sort direction ('asc' or 'desc')",
    *         required=false,
    *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
    *     ),
    *     @OA\Parameter(
    *         name="per_page",
    *         in="query",
    *         description="Number of items per page",
    *         required=false,
    *         @OA\Schema(type="integer", default=10)
    *     ),
    *
    *     @OA\Response(
    *         response=200,
    *         description="Successful response with paginated smart notes",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=true),
    *             @OA\Property(property="message", type="string", example="On demand Notes Successfully"),
    *             @OA\Property(property="status_code", type="integer", example=200),
    *             @OA\Property(property="data", type="object",
    *                 @OA\Property(property="notes", type="array",
    *                     @OA\Items(ref="#/components/schemas/OnDemandSmartNoteResource")
    *                 ),
    *                 @OA\Property(property="path", type="string", example="http://127.0.0.1:8000/api/ai-notes/on-demand-smart-notes/index"),
    *                 @OA\Property(property="per_page", type="integer", example=10),
    *                 @OA\Property(property="next_cursor", type="string", nullable=true),
    *                 @OA\Property(property="next_page_url", type="string", nullable=true),
    *                 @OA\Property(property="prev_cursor", type="string", nullable=true),
    *                 @OA\Property(property="prev_page_url", type="string", nullable=true),
    *                 @OA\Property(property="total", type="integer", example=57)
    *             )
    *         )
    *     ),
    *
    *     @OA\Response(
    *         response=500,
    *         description="Unexpected error",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="An error occurred while fetching on demand smart notes"),
    *             @OA\Property(property="status_code", type="integer", example=500),
    *             @OA\Property(property="data", type="object",
    *                 @OA\Property(property="error", type="string", example="Exception message")
    *             )
    *         )
    *     )
    * )
    *
    * @OA\Schema(
    *     schema="OnDemandSmartNoteResource",
    *     type="object",
    *     title="OnDemandSmartNote",
    *     description="A single On-Demand Smart Note resource",
    *     @OA\Property(property="id", type="integer", example=101),
    *     @OA\Property(property="approved", type="boolean", example=true),
    *     @OA\Property(property="is_shared", type="boolean", example=false),
    *     @OA\Property(property="note", type="string", example="Patient diagnosed with ..."),
    *     @OA\Property(property="approval_date", type="string", format="date-time", example="2024-10-12T10:30:00Z"),
    *
    *     @OA\Property(property="patient_id", type="integer", example=234),
    *     @OA\Property(property="patient_uuid", type="string", format="uuid", example="4c1e4a24-ecad-11ed-a05b-0242ac120003"),
    *     @OA\Property(property="patient_name", type="string", example="John Doe"),

    *     @OA\Property(property="doctor_id", type="integer", example=45),
    *     @OA\Property(property="doctor_uuid", type="string", format="uuid", example="5fa67d38-ecad-11ed-a05b-0242ac120003"),
    *     @OA\Property(property="doctor_name", type="string", example="Dr. Sarah Smith"),

    *     @OA\Property(property="ai_env_id", type="integer", example=3),
    *     @OA\Property(property="ai_env_name", type="string", example="Keme-GPT Env"),
    *
    *     @OA\Property(property="ai_model_id", type="integer", example=2),
    *     @OA\Property(property="ai_model_name", type="string", example="gpt-4"),

    *     @OA\Property(property="approved_by", type="integer", nullable=true, example=1),
    *     @OA\Property(property="approved_by_name", type="string", nullable=true, example="Dr. Mike Evans"),

    *     @OA\Property(property="ai_diagnosis", type="string", example="Likely viral infection. Recommended rest and hydration."),
    *
    *     @OA\Property(property="context_id", type="integer", example=12),
    *     @OA\Property(property="context2_id", type="integer", nullable=true, example=13),

    *     @OA\Property(property="context_name", type="string", example="Cardiology Visit"),
    *     @OA\Property(property="context2_name", type="string", nullable=true, example="Follow-up Consultation"),

    *     @OA\Property(property="current_status", type="string", example="queued"),

    *     @OA\Property(property="keme_direct", type="boolean", example=false),

    *     @OA\Property(property="spoken_languages", type="string", example="en,ar")
    * )
    */
    public function index(Request $request): JsonResponse
    {
        return $this->onDemandSmartNoteService->index($request);
    }

    /**
     * Display the specified on-demand smart note.
     *
     * This method handles the HTTP GET request to retrieve a specific on-demand smart note by its ID.
     * It delegates the request to the `show` method of the `OnDemandSmartNoteService`.
     *
     * @param int $id The ID of the on-demand smart note to retrieve.
     * @return mixed The response returned by the service.
     *
    * @OA\Get(
    *     path="/v4/on-demand-smart-notes/show/{id}",
    *     summary="Get a specific On Demand Smart Note",
    *     tags={"On-Demand Smart Notes"},
    *     security={{"bearerAuth":{}}},
    *
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         required=true,
    *         description="ID of the OnDemandSmartNote to retrieve",
    *         @OA\Schema(type="integer", example=1)
    *     ),
    *
    *     @OA\Response(
    *         response=200,
    *         description="Success",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=true),
    *             @OA\Property(property="message", type="string", example="success"),
    *             @OA\Property(property="status_code", type="integer", example=200),
    *             @OA\Property(property="data", type="object",
    *                 @OA\Property(property="id", type="integer", example=1),
    *                 @OA\Property(property="approved", type="boolean", example=true),
    *                 @OA\Property(property="is_shared", type="boolean", example=true),
    *                 @OA\Property(property="note", type="string", example="This is a sample note."),
    *                 @OA\Property(property="approval_date", type="string", format="date-time", example="2025-07-06T12:00:00Z"),
    *                 @OA\Property(property="patient_id", type="integer", example=101),
    *                 @OA\Property(property="patient_uuid", type="string", example="uuid-1234"),
    *                 @OA\Property(property="patient_name", type="string", example="John Doe"),
    *                 @OA\Property(property="doctor_id", type="integer", example=22),
    *                 @OA\Property(property="doctor_uuid", type="string", example="uuid-5678"),
    *                 @OA\Property(property="doctor_name", type="string", example="Dr. Smith"),
    *                 @OA\Property(property="ai_env_id", type="integer", example=3),
    *                 @OA\Property(property="ai_env_name", type="string", example="KemeAI Env"),
    *                 @OA\Property(property="ai_model_id", type="integer", example=7),
    *                 @OA\Property(property="ai_model_name", type="string", example="Keme-GPT"),
    *                 @OA\Property(property="approved_by", type="integer", example=4),
    *                 @OA\Property(property="approved_by_name", type="string", example="Admin User"),
    *                 @OA\Property(property="ai_diagnosis", type="string", example="AI Diagnosis Summary"),
    *                 @OA\Property(property="context_id", type="integer", example=11),
    *                 @OA\Property(property="context2_id", type="integer", example=13),
    *                 @OA\Property(property="context_name", type="string", example="Initial Context"),
    *                 @OA\Property(property="context2_name", type="string", example="Secondary Context"),
    *                 @OA\Property(property="current_status", type="string", example="approved"),
    *                 @OA\Property(property="keme_direct", type="boolean", example=false),
    *                 @OA\Property(property="spoken_languages", type="string", example="en, ar")
    *             )
    *         )
    *     ),
    *
    *     @OA\Response(
    *         response=404,
    *         description="Not Found / Model Not Found",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Model not found."),
    *             @OA\Property(property="status_code", type="integer", example=404),
    *             @OA\Property(property="data", type="array", @OA\Items())
    *         )
    *     ),
    *
    *     @OA\Response(
    *         response=500,
    *         description="Server error",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Failed to retrieve on demand smart note"),
    *             @OA\Property(property="status_code", type="integer", example=500),
    *             @OA\Property(property="data", type="object",
    *                 @OA\Property(property="error", type="string", example="Something went wrong...")
    *             )
    *         )
    *     )
    * )
     */
    public function show($id): JsonResponse
    {
        return $this->onDemandSmartNoteService->show($id);
    }

    /**
     * Store a newly created on-demand smart note.
     *
     * This method handles the HTTP POST request to create a new on-demand smart note.
     * It delegates the request to the `store` method of the `OnDemandSmartNoteService`.
     *
     * @param Request $request The HTTP request object containing the data for the new note.
     * @return mixed The response returned by the service.
     *
     *
     * @OA\Post(
    *     path="/v4/on-demand-smart-notes/create",
    *     summary="Create a new On-Demand Smart Note",
    *     description="Creates a new smart note and dispatches processing jobs based on context. If context2 is provided, a secondary processing queue is triggered.",
    *     operationId="createOnDemandSmartNote",
    *     tags={"On-Demand Smart Notes"},
    *     security={{"bearerAuth":{}}},
    *
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(
    *             required={"note", "context_id", "spoken_languages"},
    *             @OA\Property(property="doctor_id", type="integer", example=1),
    *             @OA\Property(property="approved_by", type="integer", example=2),
    *             @OA\Property(property="approved", type="boolean", example=true),
    *             @OA\Property(property="is_shared", type="boolean", example=true),
    *             @OA\Property(property="approval_date", type="string", format="date", example="2025-07-06"),
    *             @OA\Property(property="note", type="string", example="Patient has shown signs of improvement."),
    *             @OA\Property(property="context_id", type="integer", example=5),
    *             @OA\Property(property="context2_id", type="integer", example=7),
    *             @OA\Property(property="ai_env_id", type="integer", example=1),
    *             @OA\Property(property="ai_model_id", type="integer", example=3),
    *             @OA\Property(property="keme_direct", type="boolean", example=true),
    *             @OA\Property(property="spoken_languages", type="string", example="en, ar"),
    *             @OA\Property(property="patient_id", type="string", example="none")
    *         )
    *     ),
    *
    *     @OA\Response(
    *         response=200,
    *         description="Smart note created and processing started",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=true),
    *             @OA\Property(property="message", type="string", example="On Demand Smart Note is being processed."),
    *             @OA\Property(property="status_code", type="integer", example=200),
    *             @OA\Property(property="data", type="object",
    *                 @OA\Property(property="id", type="integer", example=101),
    *                 @OA\Property(property="note", type="string", example="Patient has shown signs of improvement."),
    *                 @OA\Property(property="approved", type="boolean", example=true),
    *                 @OA\Property(property="is_shared", type="boolean", example=true),
    *                 @OA\Property(property="spoken_languages", type="string", example="en, ar"),
    *                 @OA\Property(property="context_id", type="integer", example=5),
    *                 @OA\Property(property="ai_env_id", type="integer", example=1),
    *                 @OA\Property(property="ai_model_id", type="integer", example=3)
    *             )
    *         )
    *     ),
    *
    *     @OA\Response(
    *         response=422,
    *         description="Validation error",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="The given data was invalid."),
    *             @OA\Property(property="status_code", type="integer", example=422),
    *             @OA\Property(property="data", type="object")
    *         )
    *     ),
    *
    *     @OA\Response(
    *         response=500,
    *         description="Server error",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Failed to process On Demand Smart note"),
    *             @OA\Property(property="status_code", type="integer", example=500),
    *             @OA\Property(property="data", type="object",
    *                 @OA\Property(property="error", type="string", example="SQLSTATE[23000]: Integrity constraint violation...")
    *             )
    *         )
    *     )
    * )
     */
    public function store(CreateOnDemandSmartNoteRequest $request): JsonResponse
    {
        return $this->onDemandSmartNoteService->store($request);
    }

    /**
     * Update the specified on-demand smart note.
     *
     * This method handles the HTTP PUT/PATCH request to update an existing on-demand smart note by its ID.
     * It delegates the request to the `update` method of the `OnDemandSmartNoteService`.
     *
     * @param Request $request The HTTP request object containing the updated data.
     * @param int $id The ID of the on-demand smart note to update.
     * @return mixed The response returned by the service.
     *
     * @OA\Put(
    *     path="/v4/on-demand-smart-notes/update/{id}",
    *     summary="Update an existing On-Demand Smart Note",
    *     description="Updates an On-Demand Smart Note using validated input data. Automatically updates AI-related fields based on context if applicable.",
    *     operationId="updateOnDemandSmartNote",
    *     tags={"On-Demand Smart Notes"},
    *     security={{"bearerAuth":{}}},
    *
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         required=true,
    *         description="ID of the note to update",
    *         @OA\Schema(type="integer", example=123)
    *     ),
    *
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(
    *             required={"note", "context_id"},
    *             @OA\Property(property="doctor_id", type="integer", example=12),
    *             @OA\Property(property="approved_by", type="integer", example=3),
    *             @OA\Property(property="approved", type="boolean", example=true),
    *             @OA\Property(property="is_shared", type="boolean", example=false),
    *             @OA\Property(property="approval_date", type="string", format="date", example="2025-07-01"),
    *             @OA\Property(property="note", type="string", example="Updated note text."),
    *             @OA\Property(property="context_id", type="integer", example=5),
    *             @OA\Property(property="context2_id", type="integer", example=6),
    *             @OA\Property(property="ai_env_id", type="integer", example=1),
    *             @OA\Property(property="spoken_languages", type="string", example="en, ar"),
    *             @OA\Property(property="keme_direct", type="boolean", example=true),
    *             @OA\Property(property="patient_id", type="integer", example=101)
    *         )
    *     ),
    *
    *     @OA\Response(
    *         response=200,
    *         description="Note updated successfully",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=true),
    *             @OA\Property(property="message", type="string", example="Updated Successfully"),
    *             @OA\Property(property="status_code", type="integer", example=200),
    *             @OA\Property(property="data", type="object",
    *                 @OA\Property(property="id", type="integer", example=123),
    *                 @OA\Property(property="note", type="string", example="Updated note text."),
    *                 @OA\Property(property="context_id", type="integer", example=5)
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Note not found",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Model not found."),
    *             @OA\Property(property="status_code", type="integer", example=404),
    *             @OA\Property(property="data", type="array", @OA\Items())
    *         )
    *     ),
    *     @OA\Response(
    *         response=500,
    *         description="Internal server error",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Failed to retrieve on demand smart notes"),
    *             @OA\Property(property="status_code", type="integer", example=500),
    *             @OA\Property(property="data", type="array", @OA\Items())
    *         )
    *     )
    * )
    */
    public function update(UpdateOnDemandSmartNoteRequest $request, $id): JsonResponse
    {
        return $this->onDemandSmartNoteService->update($request, $id);
    }

    /**
     * Remove the specified on-demand smart note.
     *
     * This method handles the HTTP DELETE request to delete an on-demand smart note by its ID.
     * It delegates the request to the `destroy` method of the `OnDemandSmartNoteService`.
     *
     * @param int $id The ID of the on-demand smart note to delete.
     * @return mixed The response returned by the service.
     *
     *
     * @OA\Delete(
    *     path="/v4/on-demand-smart-notes/delete/{id}",
    *     summary="Delete an On-Demand Smart Note",
    *     description="Deletes the specified On-Demand Smart Note and its associated queue records.",
    *     operationId="deleteOnDemandSmartNote",
    *     tags={"On-Demand Smart Notes"},
    *     security={{"bearerAuth":{}}},
    *
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         required=true,
    *         description="ID of the On-Demand Smart Note to delete",
    *         @OA\Schema(type="integer", example=123)
    *     ),
    *
    *     @OA\Response(
    *         response=200,
    *         description="Note deleted successfully",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=true),
    *             @OA\Property(property="message", type="string", example="Note Deleted Successfully"),
    *             @OA\Property(property="status_code", type="integer", example=200),
    *             @OA\Property(property="data", type="array", @OA\Items())
    *         )
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Note not found",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Model not found."),
    *             @OA\Property(property="status_code", type="integer", example=404),
    *             @OA\Property(property="data", type="array", @OA\Items())
    *         )
    *     ),
    *     @OA\Response(
    *         response=500,
    *         description="Internal server error",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="An error occurred"),
    *             @OA\Property(property="status_code", type="integer", example=500),
    *             @OA\Property(property="data", type="array", @OA\Items())
    *         )
    *     )
    * )
     */
    public function destroy($id): JsonResponse
    {
        return $this->onDemandSmartNoteService->destroy($id);
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
     *
     * @OA\Delete(
     *     path="/v4/on-demand-smart-notes/delete-queue-list/{id}",
     *     summary="Delete a queue list item",
     *     description="Deletes a queue list record by its ID if it exists.",
     *     operationId="deleteQueueList",
     *     tags={"On-Demand Smart Notes"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the queue list item to delete",
     *         @OA\Schema(type="integer", example=21)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Queue deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Queue Deleted Successfully"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Queue not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="ID not found"),
     *             @OA\Property(property="status_code", type="integer", example=400),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     )
     * )
     */
    public function deleteQueueList($id): JsonResponse
    {
        return $this->onDemandSmartNoteService->deleteQueueList($id);
    }

    /**
     * Delegate retrieval of on-demand smart notes for a given patient to the service.
     *
     * @param int $patient_id The ID of the patient whose notes are to be retrieved.
     * @return \Illuminate\Http\JsonResponse JSON response containing the notes or an error message.
     *
     *
     * @OA\Get(
    *     path="/v4/on-demand-smart-notes/get-notes-by-patient/{patient_id}",
    *     operationId="getAllNotesByPatient",
    *     tags={"On-Demand Smart Notes"},
    *     summary="Get On-Demand Smart Notes by Patient",
    *     description="Returns a paginated list of on-demand smart notes filtered by patient ID and optionally by the authenticated doctor.",
    *     security={{"bearerAuth":{}}},
    *
    *     @OA\Parameter(
    *         name="patient_id",
    *         in="path",
    *         description="The ID of the patient whose notes should be retrieved.",
    *         required=true,
    *         @OA\Schema(type="integer", example=12)
    *     ),
    *     @OA\Parameter(
    *         name="per_page",
    *         in="query",
    *         description="Number of items per page",
    *         required=false,
    *         @OA\Schema(type="integer", default=10)
    *     ),
    *     @OA\Parameter(
    *         name="sort_by",
    *         in="query",
    *         description="Field to sort by (e.g., id, created_at)",
    *         required=false,
    *         @OA\Schema(type="string", default="id")
    *     ),
    *     @OA\Parameter(
    *         name="sort_dir",
    *         in="query",
    *         description="Sort direction ('asc' or 'desc')",
    *         required=false,
    *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
    *     ),
    *
    *     @OA\Response(
    *         response=200,
    *         description="Successfully retrieved patient notes",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=true),
    *             @OA\Property(property="message", type="string", example="On demand Notes returned Successfully"),
    *             @OA\Property(property="status_code", type="integer", example=200),
    *             @OA\Property(property="data", type="object",
    *                 @OA\Property(property="notes", type="array",
    *                     @OA\Items(ref="#/components/schemas/OnDemandSmartNoteResource")
    *                 ),
    *                 @OA\Property(property="path", type="string", example="http://localhost:8000/api/v4/on-demand-smart-notes/get-notes-by-patient/12"),
    *                 @OA\Property(property="per_page", type="integer", example=10),
    *                 @OA\Property(property="next_cursor", type="string", nullable=true),
    *                 @OA\Property(property="next_page_url", type="string", nullable=true),
    *                 @OA\Property(property="prev_cursor", type="string", nullable=true),
    *                 @OA\Property(property="prev_page_url", type="string", nullable=true),
    *                 @OA\Property(property="total", type="integer", example=3)
    *             )
    *         )
    *     ),
    *
    *     @OA\Response(
    *         response=404,
    *         description="No notes found for this patient",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="No notes found for this patient"),
    *             @OA\Property(property="status_code", type="integer", example=404),
    *             @OA\Property(property="data", type="array", @OA\Items(type="string"))
    *         )
    *     ),
    *
    *     @OA\Response(
    *         response=500,
    *         description="Server error while fetching notes",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="An error occurred while fetching notes"),
    *             @OA\Property(property="status_code", type="integer", example=500),
    *             @OA\Property(property="data", type="object",
    *                 @OA\Property(property="error", type="string", example="Exception message")
    *             )
    *         )
    *     )
    * )
     */
    public function getNotesByPatient(Request $request, $patient_id): JsonResponse
    {
        return $this->onDemandSmartNoteService->getNotesByPatient($request,$patient_id);
    }

    /**
     * Delegate the approval of an on-demand smart note to the service.
     *
     * @param \Illuminate\Http\Request $request The request containing approval details.
     * @param int $id The ID of the on-demand smart note to be approved.
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure.
     *
     * @OA\Put(
    *     path="/api/v4/on-demand-smart-notes/note-approve/{id}",
    *     summary="Approve an on-demand smart note",
    *     description="Approve a smart note, update its approval details and process AI diagnosis.",
    *     operationId="noteApprove",
    *     tags={"OnDemand Smart Notes"},
    *     security={{"bearerAuth":{}}},
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         description="ID of the smart note to approve",
    *         required=true,
    *         @OA\Schema(type="integer", example=42)
    *     ),
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(
    *             required={"ai_diagnosis"},
    *             @OA\Property(property="ai_diagnosis", type="string", example="The patient shows signs of improvement."),
    *             @OA\Property(property="approval_date", type="string", format="date", example="2025-07-09")
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Smart note approved successfully",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=true),
    *             @OA\Property(property="message", type="string", example="Approved Successfully"),
    *             @OA\Property(property="data", type="object", ref="#/components/schemas/OnDemandSmartNoteResource")
    *         )
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Smart note not found",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Failed to retrieve on demand smart notes"),
    *             @OA\Property(property="data", type="object", example={"error": "Model not found"})
    *         )
    *     ),
    *     @OA\Response(
    *         response=500,
    *         description="Internal server error",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Failed to retrieve on demand smart notes"),
    *             @OA\Property(property="data", type="object", example={"error": "Unexpected exception message"})
    *         )
    *     )
    * )
     */
    public function noteApprove(NoteApproveRequest $request, $id): JsonResponse
    {
        return $this->onDemandSmartNoteService->noteApprove($request,$id);
    }

    /**
     * Delegates the regeneration of an On-Demand Smart Note to the service layer.
     *
     * @param int $id The ID of the On-Demand Smart Note to regenerate.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response from the service.
     *
     *     * @OA\Post(
    *     path="/v4/on-demand-smart-notes/regenerate/{id}",
    *     summary="Regenerate an On-Demand Smart Note",
    *     description="Dispatches a processing job to regenerate the specified On-Demand Smart Note based on its context.",
    *     operationId="regenerateSmartNote",
    *     tags={"OnDemand Smart Notes"},
    *     security={{"bearerAuth":{}}},
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         required=true,
    *         description="The ID of the On-Demand Smart Note to regenerate",
    *         @OA\Schema(type="integer", example=42)
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Note regenerated successfully",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=true),
    *             @OA\Property(property="message", type="string", example="Note Regenerated Successfully"),
    *             @OA\Property(property="data", type="object", ref="#/components/schemas/OnDemandSmartNoteResource")
    *         )
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Smart note not found",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Failed to regenerate Demand Smart note"),
    *             @OA\Property(property="data", type="object", example={"error": "Model not found"})
    *         )
    *     ),
    *     @OA\Response(
    *         response=500,
    *         description="Internal server error",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Failed to regenerate Demand Smart note"),
    *             @OA\Property(property="data", type="object", example={"error": "Unexpected error message"})
    *         )
    *     )
    * )
     */
    public function regenerate($id): JsonResponse
    {
        return $this->onDemandSmartNoteService->regenerate($id);
    }

    /**
     * Retrieve the authenticated user's current queue orders.
     *
     * This method gathers queue items belonging to the authenticated user, including:
     * - The associated note ID and note name (from either OnDemandSmartNote or PublicAppointmentSummary)
     * - The current queue status (e.g., QUEUED or IN_PROGRESS)
     * - The user's position/order in the full active queue list
     * - The total number of active queues
     * - The ID of the queue entry itself
     *
     * The queue order is calculated based on the user's queue position relative to all active queues.
     *
     * @return \Illuminate\Http\JsonResponse JSON response containing user-specific queue order data.
     *
     * @OA\Get(
    *     path="/v4/queue/user-orders",
    *     summary="Get authenticated user's queue orders",
    *     description="Returns the authenticated user's queue orders with position, status, and note details.",
    *     tags={"OnDemand Smart Notes"},
    *     security={{"bearerAuth":{}}},
    *     @OA\Response(
    *         response=200,
    *         description="User queue orders returned successfully",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=true),
    *             @OA\Property(property="message", type="string", example="success"),
    *             @OA\Property(
    *                 property="data",
    *                 type="array",
    *                 @OA\Items(
    *                     type="object",
    *                     @OA\Property(
    *                         property="note",
    *                         type="object",
    *                         @OA\Property(property="id", type="integer", example=5),
    *                         @OA\Property(property="name", type="string", example="Patient note sample")
    *                     ),
    *                     @OA\Property(property="status", type="string", example="QUEUED"),
    *                     @OA\Property(property="order", type="integer", example=2),
    *                     @OA\Property(property="total", type="integer", example=10),
    *                     @OA\Property(property="queue_list_id", type="integer", example=55)
    *                 )
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response=403,
    *         description="Unauthorized request",
    *         @OA\JsonContent(
    *             @OA\Property(property="message", type="string", example="Unauthenticated.")
    *         )
    *     ),
    *     @OA\Response(
    *         response=500,
    *         description="Unexpected server error",
    *         @OA\JsonContent(
    *             @OA\Property(property="message", type="string", example="Server error occurred.")
    *         )
    *     )
    * )
    */
    public function getUserQueueOrders(): JsonResponse
    {
        return $this->onDemandSmartNoteService->getUserQueueOrders();
    }
}