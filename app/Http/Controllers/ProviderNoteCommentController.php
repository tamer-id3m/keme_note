<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProviderNote\ProviderNoteComment\CreateProviderNoteCommentRequest;
use App\Http\Requests\ProviderNote\ProviderNoteComment\UpdateProviderNoteCommentRequest;
use App\Services\V4\ProviderNote\ProviderNoteCommentService;
use App\Traits\ApiResponseTrait;

class ProviderNoteCommentController extends Controller
{
    use ApiResponseTrait;

    protected $providerNoteCommentService;

    public function __construct(ProviderNoteCommentService $providerNoteCommentService)
    {
        $this->providerNoteCommentService = $providerNoteCommentService;

        $this->middleware('permission:provider-note-create', ['only' => ['store']]);
        $this->middleware('permission:provider-note-list', ['only' => ['index']]);
        $this->middleware('permission:provider-note-show', ['only' => ['show']]);
        $this->middleware('permission:provider-note-edit', ['only' => ['update']]);
        $this->middleware('permission:provider-note-delete', ['only' => ['destroy']]);
    }

    /**
     * @OA\Get(
     *     path="/api/v4/provider-note-comment/index",
     *     summary="List all provider note comments",
     *     tags={"Provider Note Comments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function index()
    {
        return $this->providerNoteCommentService->index();
    }

    /**
     * @OA\Get(
     *     path="/api/v4/provider-note-comment/show/{id}",
     *     summary="Show specific provider note comment",
     *     tags={"Provider Note Comments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Comment not found")
     * )
     */
    public function show($id)
    {
        return $this->providerNoteCommentService->show($id);
    }

    /**
     * @OA\Post(
     *     path="/api/v4/provider-note-comment/create",
     *     summary="Create a new provider note comment",
     *     tags={"Provider Note Comments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"body", "user_id", "provider_note_id", "patient_id"},
     *             @OA\Property(property="body", type="string", example="This is a comment"),
     *             @OA\Property(property="user_id", type="integer", example=5),
     *             @OA\Property(property="provider_note_id", type="integer", example=12),
     *             @OA\Property(property="patient_id", type="integer", example=8)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(CreateProviderNoteCommentRequest $request)
    {
        return $this->providerNoteCommentService->store($request);
    }

    /**
     * @OA\Post(
     *     path="/api/v4/provider-note-comment/edit/{id}",
     *     summary="Update an existing provider note comment",
     *     tags={"Provider Note Comments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="body", type="string", example="Updated comment text"),
     *             @OA\Property(property="user_id", type="integer", example=5),
     *             @OA\Property(property="provider_note_id", type="integer", example=12),
     *             @OA\Property(property="patient_id", type="integer", example=8)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated successfully"),
     *     @OA\Response(response=404, description="Comment not found")
     * )
     */
    public function update(UpdateProviderNoteCommentRequest $request, $id)
    {
        return $this->providerNoteCommentService->update($request, $id);
    }

    /**
     * @OA\Post(
     *     path="/api/v4/provider-note-comment/delete/{id}",
     *     summary="Delete a provider note comment",
     *     tags={"Provider Note Comments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Deleted successfully"),
     *     @OA\Response(response=404, description="Comment not found")
     * )
     */
    public function destroy($id)
    {
        return $this->providerNoteCommentService->destroy($id);
    }
}