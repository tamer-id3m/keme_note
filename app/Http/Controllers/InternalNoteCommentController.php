<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\InternalNoteComment\InternalNoteCommentCrudService;
use App\Http\Requests\InternalNoteComment\CreateNoteCommentRequest;
use App\Http\Requests\InternalNoteComment\UpdateNoteCommentRequest;

/**
 * Class InternalNoteCommentController
 *
 * This controller handles operations for internal note comments, including
 * retrieving, creating, updating, and deleting comments, as well as handling
 * user mentions in comments. It ensures proper role-based access and manages
 * comment history during updates.
 * 
 *  @OA\Tag(
 *    name="Internal Note Comments",
 *    description="Endpoints for managing internal note data"
 * )
 */
class InternalNoteCommentController extends Controller
{
    use ApiResponseTrait;

    protected $internalNoteCommentCrudService; 

    /**
     * Constructor method to inject the necessary service and apply middleware.
     *
     * This constructor ensures that the user has permission to access staff note comments
     * and injects the InternalNoteCommentCrudService for performing the required operations.
     *
     * @param \App\Services\InternalNoteComment\InternalNoteCommentCrudService $internalNoteCommentCrudService The service instance for CRUD operations on internal note comments.
     */
    public function __construct(InternalNoteCommentCrudService $internalNoteCommentCrudService)
    {
        $this->middleware('permission:staff-note-comment');
        $this->internalNoteCommentCrudService = $internalNoteCommentCrudService;
    }

    /**
     * Retrieve all comments for a specific patient.
     *
     * @param  int  $id  The ID of the patient whose comments are to be fetched.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns a list of comments for the specified patient.
     *                                       - User Not Found: Returns a 404 response if the specified patient ID is not found.
     *                                       - Error: Returns a 500 response for unexpected exceptions during processing.
     * 
     *  @OA\Get(
    *     path="/v4/note-comments/index/{id}",
    *     summary="Get internal note comments for a specific patient",
    *     description="Returns a list of internal note comments for the specified patient, including user info and comment timestamps converted to the clinic timezone.",
    *     operationId="getInternalNoteComments",
    *     tags={"Internal Note Comments"},
    *
    *     @OA\Parameter(
    *         name="patient_id",
    *         in="path",
    *         required=true,
    *         description="ID of the patient",
    *         @OA\Schema(type="integer")
    *     ),
    *
    *     @OA\Response(
    *         response=200,
    *         description="List of internal note comments",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="status", type="string", example="success"),
    *             @OA\Property(property="code", type="integer", example=200),
    *             @OA\Property(
    *                 property="data",
    *                 type="array",
    *                 @OA\Items(ref="#/components/schemas/InternalNoteCommentResource")
    *             )
    *         )
    *     ),
    *
    *     @OA\Response(
    *         response=404,
    *         description="Patient not found"
    *     )
    * )
    * @OA\Schema(
    *     schema="InternalNoteCommentResource",
    *     type="object",
    *     title="Internal Note Comment Resource",
    *     description="Represents an internal comment made by a user on a patient.",
    * 
    *     @OA\Property(property="id", type="integer", example=101),
    *     @OA\Property(property="user_id", type="integer", example=34),
    *     @OA\Property(property="name", type="string", nullable=true, example="Dr. Sarah Connor"),
    *     @OA\Property(property="photo", type="string", format="uri", nullable=true, example="https://example.com/images/user.png"),
    * 
    *     @OA\Property(
    *         property="message",
    *         type="object",
    *         @OA\Property(property="message", type="string", example="Patient condition improving."),
    *         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-06-25 14:35:00")
    *     ),
    *     @OA\Property(property="patient_id", type="integer", example=78)
    * )
    */
    public function index($id): JsonResponse
    {
        return $this->internalNoteCommentCrudService->index($id);
    }

    /**
     * Store a new internal note comment.
     *
     * @param  \App\Http\Requests\InternalNoteComment\CreateNoteCommentRequest  $request  The request containing the comment details.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns the newly created comment.
     *                                       - Error: Returns a 500 response if the comment creation fails.
     * 
     * @OA\Post(
    *     path="/v4/note-comments/create",
    *     summary="Create a new internal note comment",
    *     description="Creates a comment for a specific internal note and sends notifications to Admin users (excluding the author).",
    *     operationId="storeInternalNoteComment",
    *     tags={"Internal Note Comments"},
    *
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(
    *             required={"body", "internal_note_id", "user_id", "patient_id"},
    *             @OA\Property(property="body", type="string", example="Patient needs further monitoring."),
    *             @OA\Property(property="internal_note_id", type="integer", example=5),
    *             @OA\Property(property="user_id", type="integer", example=2),
    *             @OA\Property(property="patient_id", type="integer", example=78)
    *         )
    *     ),
    *
    *     @OA\Response(
    *         response=201,
    *         description="Comment created successfully",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="status", type="string", example="Added Successfully"),
    *             @OA\Property(property="code", type="integer", example=201),
    *             @OA\Property(property="data", ref="#/components/schemas/InternalNoteCommentResource")
    *         )
    *     ),
    *
    *     @OA\Response(
    *         response=500,
    *         description="Error occurred during retrieval",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="status", type="string", example="Error occurred during retrieval"),
    *             @OA\Property(property="code", type="integer", example=500),
    *             @OA\Property(
    *                 property="data",
    *                 type="object",
    *                 @OA\Property(property="error", type="string", example="SQLSTATE[23000]: Integrity constraint violation ...")
    *             )
    *         )
    *     )
    * )
    */
    public function store(CreateNoteCommentRequest $request): JsonResponse
    {
        return $this->internalNoteCommentCrudService->store($request);
    }

    /**
     * Retrieve a specific comment by ID.
     *
     * @param  int  $id  The ID of the comment to retrieve.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns the comment details.
     *                                       - Comment Not Found: Returns a 404 response if the comment does not exist.
     * @OA\Get(
    *     path="/v4/note-comments/{id}",
    *     summary="Get a single internal note comment",
    *     description="Returns a specific internal note comment with user information.",
    *     operationId="getInternalNoteComment",
    *     tags={"Internal Note Comments"},
    *
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         required=true,
    *         description="ID of the internal note comment",
    *         @OA\Schema(type="integer", example=101)
    *     ),
    *
    *     @OA\Response(
    *         response=200,
    *         description="Comment retrieved successfully",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="status", type="string", example="success"),
    *             @OA\Property(property="code", type="integer", example=200),
    *             @OA\Property(property="data", ref="#/components/schemas/InternalNoteCommentResource")
    *         )
    *     ),
    *
    *     @OA\Response(
    *         response=404,
    *         description="Comment not found",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="status", type="string", example="not found"),
    *             @OA\Property(property="code", type="integer", example=404),
    *             @OA\Property(property="data", type="null", example=null)
    *         )
    *     )
    * )
     */
    public function show($id): JsonResponse
    {
        return $this->internalNoteCommentCrudService->show($id);
    }

    /**
     * Update an existing comment.
     *
     * @param  \App\Http\Requests\InternalNoteComment\UpdateNoteCommentRequest  $request  The request containing the updated comment details.
     * @param  int  $noteId  The ID of the note the comment belongs to.
     * @param  int  $commentId  The ID of the comment to update.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns the updated comment.
     *                                       - Comment Not Found: Returns a 404 response if the comment does not exist.
     * 
     *  @OA\Put(
    *     path="/v4/note-comments/edit/{note_id}/{comment_id}",
    *     summary="Update an internal note comment",
    *     description="Updates the body of an internal note comment and stores the previous version in comment history. Also notifies mentioned users.",
    *     operationId="updateInternalNoteComment",
    *     tags={"Internal Note Comments"},
    *
    *     @OA\Parameter(
    *         name="note_id",
    *         in="path",
    *         required=true,
    *         description="ID of the internal note",
    *         @OA\Schema(type="integer", example=5)
    *     ),
    *
    *     @OA\Parameter(
    *         name="comment_id",
    *         in="path",
    *         required=true,
    *         description="ID of the comment to be updated",
    *         @OA\Schema(type="integer", example=101)
    *     ),
    *
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(
    *             required={"body"},
    *             @OA\Property(property="body", type="string", example="Updated comment with new observation.")
    *         )
    *     ),
    *
    *     @OA\Response(
    *         response=200,
    *         description="Comment updated successfully",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="status", type="string", example="Updated Successfully"),
    *             @OA\Property(property="code", type="integer", example=200),
    *             @OA\Property(property="data", ref="#/components/schemas/InternalNoteCommentResource")
    *         )
    *     ),
    *
    *     @OA\Response(
    *         response=404,
    *         description="Comment or note not found",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="status", type="string", example="not found"),
    *             @OA\Property(property="code", type="integer", example=404),
    *             @OA\Property(property="data", type="null", example=null)
    *         )
    *     ),
    *
    *     @OA\Response(
    *         response=500,
    *         description="Update failed due to server error",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="status", type="string", example="Error occurred during retrieval"),
    *             @OA\Property(property="code", type="integer", example=500),
    *             @OA\Property(
    *                 property="data",
    *                 type="object",
    *                 @OA\Property(property="error", type="string", example="SQLSTATE[HY000]: General error ...")
    *             )
    *         )
    *     )
    * )
     */
    public function update(UpdateNoteCommentRequest $request, $noteId, $commentId): JsonResponse
    {
        return $this->internalNoteCommentCrudService->update($request, $noteId, $commentId);
    }

    /**
     * Delete an existing comment.
     *
     * @param  int  $noteId  The ID of the note the comment belongs to.
     * @param  int  $commentId  The ID of the comment to delete.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns a success message upon deletion.
     *                                       - Comment Not Found: Returns a 404 response if the comment does not exist.
     * 
     * @OA\Delete(
    *     path="/v4/note-comments/{note_id}/{comment_id}",
    *     summary="Delete an internal note comment",
    *     description="Deletes a comment associated with a specific internal note.",
    *     operationId="deleteInternalNoteComment",
    *     tags={"Internal Note Comments"},
    *
    *     @OA\Parameter(
    *         name="note_id",
    *         in="path",
    *         required=true,
    *         description="ID of the internal note",
    *         @OA\Schema(type="integer", example=5)
    *     ),
    *
    *     @OA\Parameter(
    *         name="comment_id",
    *         in="path",
    *         required=true,
    *         description="ID of the comment to be deleted",
    *         @OA\Schema(type="integer", example=101)
    *     ),
    *
    *     @OA\Response(
    *         response=200,
    *         description="Comment deleted successfully",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="status", type="string", example="Deleted Successfully"),
    *             @OA\Property(property="code", type="integer", example=200),
    *             @OA\Property(property="data", type="string", example="Deleted Successfully")
    *         )
    *     ),
    *
    *     @OA\Response(
    *         response=404,
    *         description="Comment not found",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="status", type="string", example="not found"),
    *             @OA\Property(property="code", type="integer", example=404),
    *             @OA\Property(property="data", type="null", example=null)
    *         )
    *     )
    * )
     */
    public function destroy($noteId, $commentId): JsonResponse
    {
        return $this->internalNoteCommentCrudService->destroy($noteId, $commentId);
    }
}