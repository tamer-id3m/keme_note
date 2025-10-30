<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProviderRequest\ProviderRequestComments\StoreProviderRequestComment;
use App\Http\Requests\ProviderRequest\ProviderRequestComments\UpdateProviderRequestComment;
use App\Services\V4\ProviderRequest\ProviderRequestCommentService;
use App\Traits\ApiResponseTrait;


class ProviderRequestCommentController extends Controller
{
    use ApiResponseTrait;

    protected $providerRequestCommentService;

    public function __construct(ProviderRequestCommentService $providerRequestCommentService)
    {
        $this->providerRequestCommentService = $providerRequestCommentService;
    }

    /**
     * @OA\Get(
     *     path="/api/v4/provider-request-comments/index",
     *     tags={"Provider Request Comments"},
     *     summary="List all provider request comments",
     *     description="Returns a list of all provider request comments with associated user roles.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of comments",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="body", type="string", example="This is a comment"),
     *                     @OA\Property(property="user", type="object",
     *                         @OA\Property(property="id", type="integer", example=5),
     *                         @OA\Property(property="name", type="string", example="Dr. Eman"),
     *                         @OA\Property(property="roles", type="array",
     *                             @OA\Items(
     *                                 @OA\Property(property="name", type="string", example="Doctor")
     *                             )
     *                         )
     *                     ),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-07-01T10:30:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="You Don't Have The Permission")
     * )
     */
    public function index()
    {
        return $this->providerRequestCommentService->index();
    }

    /**
     * @OA\Get(
     *     path="/api/v4/provider-request-comments/show/{id}",
     *     tags={"Provider Request Comments"},
     *     summary="Show provider request comment by UUID",
     *     description="Get a specific provider request comment with user roles.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="UUID of the comment",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comment details",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="body", type="string", example="This is a comment"),
     *                 @OA\Property(property="provider_note_id", type="integer", example=12),
     *                 @OA\Property(property="patient_id", type="integer", example=34),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=5),
     *                     @OA\Property(property="name", type="string", example="Dr. Eman"),
     *                     @OA\Property(property="roles", type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="name", type="string", example="Doctor")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-07-01T10:30:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="You Don't Have The Permission"),
     *     @OA\Response(response=404, description="ID Not Found")
     * )
     */
    public function show($id)
    {
        return $this->providerRequestCommentService->show($id);
    }

    /**
     * @OA\Post(
     *     path="/api/v4/provider-request-comments/create",
     *     tags={"Provider Request Comments"},
     *     summary="Create a new provider request comment",
     *     description="Adds a new comment to a provider request.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"body", "user_id", "provider_note_id", "patient_id"},
     *             @OA\Property(property="body", type="string", example="This is a new comment"),
     *             @OA\Property(property="user_id", type="integer", example=2),
     *             @OA\Property(property="provider_note_id", type="integer", example=12),
     *             @OA\Property(property="patient_id", type="integer", example=34)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comment created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Added Successfully"),
     *             @OA\Property(property="status_code", type="integer", example=201),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=15),
     *                 @OA\Property(property="body", type="string", example="This is a new comment"),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="Dr. Ahmed"),
     *                     @OA\Property(property="roles", type="array",
     *                         @OA\Items(@OA\Property(property="name", type="string", example="Doctor"))
     *                     )
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-07-01T10:30:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="You Don't Have The Permission"),
     *     @OA\Response(response=500, description="Creation Failed")
     * )
     */
    public function store(StoreProviderRequestComment $request)
    {
        return $this->providerRequestCommentService->store($request);
    }

    /**
     * @OA\Patch(
     *     path="/api/v4/provider-request-comments/edit/{id}",
     *     tags={"Provider Request Comments"},
     *     summary="Update a comment",
     *     description="Update the body of a comment and log its history.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="UUID of the comment to be updated",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="body", type="string", example="Updated comment content"),
     *             @OA\Property(property="user_id", type="integer", example=2),
     *             @OA\Property(property="provider_note_id", type="integer", example=12),
     *             @OA\Property(property="patient_id", type="integer", example=34)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comment updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Updated Successfully"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=15),
     *                 @OA\Property(property="body", type="string", example="Updated comment content"),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="Dr. Ahmed"),
     *                     @OA\Property(property="roles", type="array",
     *                         @OA\Items(@OA\Property(property="name", type="string", example="Doctor"))
     *                     )
     *                 ),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-07-02T11:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="ID Not Found")
     * )
     */
    public function update(UpdateProviderRequestComment $request, $id)
    {
        return $this->providerRequestCommentService->update($request, $id);
    }

    /**
     * @OA\Delete(
     *     path="/api/v4/provider-request-comments/delete/{id}",
     *     tags={"Provider Request Comments"},
     *     summary="Delete a comment by UUID",
     *     description="Deletes the specified provider request comment.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="UUID of the comment to be deleted",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Deleted Successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Deleted Successfully"),
     *             @OA\Property(property="status_code", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(response=404, description="ID Not Found")
     * )
     */
    public function destroy($id)
    {
        return $this->providerRequestCommentService->destroy($id);
    }
}