<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Services\V4\ProviderNote\ProviderNoteCommentService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class ProviderNoteCommentHistoryController extends Controller
{
    use ApiResponseTrait;

    protected $providerNoteCommentService;

    public function __construct(ProviderNoteCommentService $providerNoteCommentService)
    {
        $this->providerNoteCommentService = $providerNoteCommentService;
    }

    /**
     * @OA\Get(
     *     path="/api/v4/provider-note-comment/history/{commentId}",
     *     summary="Get the edit history for a provider note comment",
     *     tags={"Provider Note Comments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="commentId",
     *         in="path",
     *         required=true,
     *         description="ID of the comment to get history for",
     *         @OA\Schema(type="integer", example=7)
     *     ),
     *     @OA\Response(response=200, description="Success - List of edit history"),
     *     @OA\Response(response=404, description="Comment not found"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function __invoke(Request $request, $commentId)
    {
        return $this->providerNoteCommentService->getCommentHistory($commentId);
    }
}