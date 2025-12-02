<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Services\ProviderRequest\ProviderRequestCommentService;
use App\Traits\ApiResponseTrait;


class ProviderRequestCommentHistoryController extends Controller
{
    use ApiResponseTrait;

    protected $providerRequestCommentService;

    public function __construct(ProviderRequestCommentService $providerRequestCommentService)
    {
        $this->providerRequestCommentService = $providerRequestCommentService;
    }

    /**
     * @OA\Get(
     *     path="/api/v4/provider-request-comments/history/{commentId}",
     *     operationId="getProviderRequestCommentHistory",
     *     tags={"Provider Request Comments"},
     *     summary="View provider request comment edit history",
     *     description="Returns all historical versions of a specific provider request comment, including the user who edited it and the timestamps.",
     *     @OA\Parameter(
     *         name="commentId",
     *         in="path",
     *         required=true,
     *         description="UUID of the comment to retrieve edit history for",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of historical versions of the comment",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="body", type="string", example="Previous comment text"),
     *                     @OA\Property(property="user", type="object",
     *                         @OA\Property(property="id", type="integer", example=14),
     *                         @OA\Property(property="name", type="string", example="Eman Emad")
     *                     ),
     *                     @OA\Property(property="edited_by", type="integer", example=14),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-08-27T12:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Comment not found or no edit history available"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Unexpected server error"
     *     )
     * )
     */
    public function __invoke($commentId)
    {
        return $this->providerRequestCommentService->getCommentEditHistory($commentId);
    }
}