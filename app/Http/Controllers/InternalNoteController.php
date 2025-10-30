<?php

namespace App\Http\Controllers\V4;

use App\Models\InternalNote;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\InternalNote\IntenalNoteCrudOperationsService;
use App\Http\Requests\InternalNoteRequest\StoreInternalNoteRequest;
use App\Http\Requests\InternalNoteRequest\UpdateInternalNoteRequest;

/**
 * Class InternalNoteController
 *
 * This controller handles operations related to internal notes for patients.
 * It includes methods for listing, creating, showing, updating, and deleting internal notes,
 * as well as handling user mentions within note bodies.
 * 
 *
 *  @OA\Tag(
 *     name="InternalNote",
 *     description="Endpoints for managing internal note data"
 * )
 */
class InternalNoteController extends Controller
{
    use ApiResponseTrait;

    protected $intenalNoteCrudOperationsService;

    /**
     * InternalNoteController constructor.
     *
     * Sets up the middleware for access control based on user roles and permissions.
     * Ensures that only authorized users can interact with internal notes.
     *
     * @return void
     */
    public function __construct(IntenalNoteCrudOperationsService $intenalNoteCrudOperationsService)
    {

        $this->middleware('permission:internalnote-list', ['only' => ['index']]);
        $this->middleware('permission:internalnote-show', ['only' => ['show']]);
        $this->middleware('permission:internalnote-create', ['only' => ['store']]);
        $this->middleware('permission:internalnote-edit', ['only' => ['update']]);
        $this->middleware('permission:internalnote-delete', ['only' => ['destroy']]);
        $this->intenalNoteCrudOperationsService = $intenalNoteCrudOperationsService;
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
     * 
    *  @OA\Get(
    *     path="/v4/internal-notes/index/{id}",
    *     operationId="getPatientInternalNotes",
    *     tags={"Internal Notes"},
    *     summary="Retrieve internal notes for a patient",
    *     description="Fetches internal notes for a given patient ID with optional sorting, pagination, and filtering.",
    *     security={{"bearerAuth":{}}},
    *
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         required=true,
    *         description="The ID of the patient",
    *         @OA\Schema(type="integer", example=123)
    *     ),
    *     @OA\Parameter(
    *         name="sort",
    *         in="query",
    *         required=false,
    *         description="Sort direction",
    *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
    *     ),
    *     @OA\Parameter(
    *         name="sortBy",
    *         in="query",
    *         required=false,
    *         description="Field to sort by",
    *         @OA\Schema(type="string", example="id")
    *     ),
    *     @OA\Parameter(
    *         name="per_page",
    *         in="query",
    *         required=false,
    *         description="Items per page",
    *         @OA\Schema(type="integer", default=10)
    *     ),
    *     @OA\Parameter(
    *         name="note_id", 
    *         in="query",
    *         required=false,
    *         description="Search filter for internal note ID(s)",
    *         @OA\Schema(type="string", example="10,11")
    *     ),
    *
    *     @OA\Response(
    *         response=200,
    *         description="Internal notes retrieved successfully",
    *         @OA\JsonContent(
    *             @OA\Property(property="message", type="string", example="Show All Internal Notes Successfully"),
    *             @OA\Property(property="data", type="object",
    *                 @OA\Property(property="data", type="array",
    *                     @OA\Items(ref="#/components/schemas/InternalNote")
    *                 ),
    *                 @OA\Property(property="path", type="string", example="http://yourdomain.com/patients/1/internal-notes"),
    *                 @OA\Property(property="per_page", type="integer", example=10),
    *                 @OA\Property(property="next_cursor", type="string", nullable=true, example="cursor123"),
    *                 @OA\Property(property="next_page_url", type="string", nullable=true, example="http://..."),
    *                 @OA\Property(property="prev_cursor", type="string", nullable=true),
    *                 @OA\Property(property="prev_page_url", type="string", nullable=true),
    *                 @OA\Property(property="total", type="integer", example=100)
    *             ),
    *             @OA\Property(property="status_code", type="integer", example=200)
    *         )
    *     ),
    *     @OA\Response(
    *         response=403,
    *         description="Unauthorized access"
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Patient not found"
    *     ),
    *     @OA\Response(
    *         response=500,
    *         description="Unexpected server error"
    *     )
    * )
    *
    * @OA\Schema(
    *     schema="InternalNote",
    *     type="object",
    *     title="Internal Note",
    *     @OA\Property(property="id", type="integer", example=1),
    *     @OA\Property(property="comment", type="string", example="Follow-up required"),
    *     @OA\Property(property="created_by", type="string", example="Dr. John"),
    *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-06-16T12:00:00Z")
    * )
    */
    public function index(Request $request, $id): JsonResponse
    {
        return $this->intenalNoteCrudOperationsService->index($request, $id);
    }

    /**
     * Store a newly created internal note.
     *
     * Creates a new internal note for a patient and optionally notifies users mentioned in the note's body.
     *
     * @param  \App\Http\Requests\InternalNoteRequest\StoreInternalNoteRequest  $request  The validated request object containing note details.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns the newly created internal note.
     *                                       - Unauthorized: Returns a 403 response if the user lacks permission to create a note.
     *                                       - Error: Returns a 500 response if the note could not be created.
     * 
     *  @OA\Post(
    *     path="/v4/internal-notes/store",
    *     operationId="storeInternalNote",
    *     tags={"Internal Notes"},
    *     summary="Create a new internal note",
    *     description="Store a new internal note and notify relevant users. Requires valid body and patient ID.",
    *     security={{"bearerAuth":{}}},
    *
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(
    *             required={"body", "patient_id"},
    *             @OA\Property(property="body", type="string", example="Patient requires daily follow-up."),
    *             @OA\Property(property="patient_id", type="integer", example=45)
    *         )
    *     ),
    *
    *     @OA\Response(
    *         response=201,
    *         description="Internal note created successfully",
    *         @OA\JsonContent(
    *             @OA\Property(property="message", type="string", example="Added Successfully"),
    *             @OA\Property(property="data", ref="#/components/schemas/InternalNote"),
    *             @OA\Property(property="status_code", type="integer", example=201)
    *         )
    *     ),
    *     @OA\Response(
    *         response=500,
    *         description="Error occurred while creating note",
    *         @OA\JsonContent(
    *             @OA\Property(property="message", type="string", example="Error occurred while creating note"),
    *             @OA\Property(property="data", type="object", example={"error": "SQL error or validation failure"}),
    *             @OA\Property(property="status_code", type="integer", example=500)
    *         )
    *     )
    * )
     */
    public function store(StoreInternalNoteRequest $request): JsonResponse
    {
        return $this->intenalNoteCrudOperationsService->store($request);
    }

    /**
     * Display the specified internal note.
     *
     * Retrieves a single internal note by its ID, along with the associated user and comments.
     *
     * @param  int  $id  The ID of the internal note to be retrieved.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns the internal note with associated data.
     *                                       - Unauthorized: Returns a 403 response if the user lacks permission to view the note.
     *                                       - Note Not Found: Returns a 404 response if the specified note is not found.
     *                                       - Error: Returns a 500 response for unexpected errors during retrieval.
     * 
     *  @OA\Get(
    *     path="/v4/internal-notes/show/{id}",
    *     operationId="getInternalNoteById",
    *     tags={"Internal Notes"},
    *     summary="Get a specific internal note by ID",
    *     description="Retrieves a single internal note with associated user and comments.",
    *     security={{"bearerAuth":{}}},
    *
    *     @OA\Parameter(
    *         name="note",
    *         in="path",
    *         required=true,
    *         description="The ID of the internal note",
    *         @OA\Schema(type="integer", example=1)
    *     ),
    *
    *     @OA\Response(
    *         response=200,
    *         description="Internal note retrieved successfully",
    *         @OA\JsonContent(
    *             @OA\Property(property="message", type="string", example="success"),
    *             @OA\Property(property="data", ref="#/components/schemas/InternalNote"),
    *             @OA\Property(property="status_code", type="integer", example=200)
    *         )
    *     ),
    *
    *     @OA\Response(
    *         response=403,
    *         description="Unauthorized access"
    *     ),
    *
    *     @OA\Response(
    *         response=404,
    *         description="Internal note not found"
    *     ),
    *
    *     @OA\Response(
    *         response=500,
    *         description="Unexpected server error"
    *     )
    * )
    */
    public function show($id): JsonResponse
    {
        return $this->intenalNoteCrudOperationsService->show($id);
    }

    /**
     * Update the specified internal note.
     *
     * Updates an existing internal note and stores the previous version in the note history.
     * Optionally notifies mentioned users in the updated note's body.
     *
     * @param  \App\Http\Requests\InternalNoteRequest\UpdateInternalNoteRequest  $request  The validated request object containing updated note details.
     * @param  int  $id  The ID of the internal note to be updated.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns the updated internal note.
     *                                       - Unauthorized: Returns a 403 response if the user lacks permission to update the note.
     *                                       - Note Not Found: Returns a 404 response if the specified note is not found.
     *                                       - Error: Returns a 500 response for unexpected errors during the update process.
     *  @OA\Put(
    *     path="/v4/internal-notes/update/{id}",
    *     operationId="updateInternalNote",
    *     tags={"Internal Notes"},
    *     summary="Update an internal note",
    *     description="Updates an existing internal note, logs the change in history, and notifies mentioned users.",
    *     security={{"bearerAuth":{}}},
    *
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         required=true,
    *         description="The ID of the internal note to update",
    *         @OA\Schema(type="integer", example=1)
    *     ),
    *
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(
    *             required={"body"},
    *             @OA\Property(property="body", type="string", example="Updated note body with new information.")
    *         )
    *     ),
    *
    *     @OA\Response(
    *         response=200,
    *         description="Note updated successfully",
    *         @OA\JsonContent(
    *             @OA\Property(property="message", type="string", example="Updated Successfully"),
    *             @OA\Property(property="data", ref="#/components/schemas/InternalNote"),
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
    *         response=500,
    *         description="Error occurred while updating note"
    *     )
    * )
    */
    public function update(UpdateInternalNoteRequest $request, $id): JsonResponse
    {
        return $this->intenalNoteCrudOperationsService->update($request, $id);
    }

    /**
     * Soft delete the specified internal note by its ID.
     *
     * This method finds an internal note by its ID and performs a soft delete,
     * marking the record as deleted without permanently removing it from the database.
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
     * @OA\Get(
    *     path="/v4/internal-notes/history/{internal_note_id}",
    *     operationId="getInternalNoteHistory",
    *     tags={"Internal Notes"},
    *     summary="Get history of an internal note",
    *     description="Retrieves all history records for a specific internal note, ordered by update timestamp.",
    *     security={{"bearerAuth":{}}},
    *
    *     @OA\Parameter(
    *         name="internal_note_id",
    *         in="path",
    *         required=true,
    *         description="The ID of the internal note to fetch history for",
    *         @OA\Schema(type="integer", example=3)
    *     ),
    *
    *     @OA\Response(
    *         response=200,
    *         description="Internal note history retrieved successfully",
    *         @OA\JsonContent(
    *             @OA\Property(property="message", type="string", example="success"),
    *             @OA\Property(property="data", type="array",
    *                 @OA\Items(ref="#/components/schemas/InternalNoteHistory")
    *             ),
    *             @OA\Property(property="status_code", type="integer", example=200)
    *         )
    *     ),
    *
    *     @OA\Response(
    *         response=403,
    *         description="Unauthorized access"
    *     ),
    *
    *     @OA\Response(
    *         response=404,
    *         description="Internal note history not found"
    *     ),
    *
    *     @OA\Response(
    *         response=500,
    *         description="Unexpected server error"
    *     )
    * )
    * @OA\Schema(
    *     schema="InternalNoteHistory",
    *     type="object",
    *     title="Internal Note History",
    *     @OA\Property(property="id", type="integer", example=1),
    *     @OA\Property(property="internal_note_id", type="integer", example=3),
    *     @OA\Property(property="body", type="string", example="Initial note before edit."),
    *     @OA\Property(property="user_id", type="integer", example=12),
    *     @OA\Property(property="edited_by", type="integer", example=18),
    *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-06-10T12:00:00Z"),
    *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-06-10T12:05:00Z")
    * )
     */
    public function destroy($id): JsonResponse
    {
        return $this->intenalNoteCrudOperationsService->destroy($id);
    }
}