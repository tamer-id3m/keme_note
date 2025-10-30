<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Services\V4\ProviderNote\ProviderNoteService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class ProviderNoteHistoryController extends Controller
{
    use ApiResponseTrait;

    protected $providerNoteHistoryService;

    public function __construct(ProviderNoteService $providerNoteHistoryService)
    {
        $this->providerNoteHistoryService = $providerNoteHistoryService;
    }
/**
 * @OA\Get(
 *     path="/api/v4/provider-notes/history/{providerNoteID}",
 *     summary="Get history of a specific provider note",
 *     tags={"Provider Notes"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="providerNoteID",
 *         in="path",
 *         required=true,
 *         description="ID of the provider note",
 *         @OA\Schema(type="integer", example=15)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="provider_note_id", type="integer", example=15),
 *                 @OA\Property(property="body", type="string", example="Initial note content"),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-07-02T11:15:00Z"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-07-02T11:15:00Z"),
 *                 @OA\Property(
 *                     property="type",
 *                     type="string",
 *                     example="edited",
 *                     description="Optional note type"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Unauthorized",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Provider note not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Provider note not found.")
 *         )
 *     )
 * )
 */
    public function __invoke(Request $request, $providerNoteID)
    {
        return $this->providerNoteHistoryService->getProviderNoteHistory($providerNoteID);
    }
}