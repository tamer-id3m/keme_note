<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Services\ProviderRequest\ProviderRequestService;
use App\Traits\ApiResponseTrait;

class ProviderRequestHistoryController extends Controller
{
    use ApiResponseTrait;

    protected $providerRequestService;

    public function __construct(ProviderRequestService $providerRequestService)
    {
        $this->providerRequestService = $providerRequestService;
    }

    /**
     * @OA\Get(
     *     path="/api/v4/provider-requests/history/{providerRequestID}",
     *     tags={"Provider Requests"},
     *     summary="Get the history of a specific provider request",
     *     description="Returns previous versions of a provider request including editor, body, timestamps, etc.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="providerRequestID",
     *         in="path",
     *         required=true,
     *         description="The ID or UUID of the provider request",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="History retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="uuid", type="string", example="abc123"),
     *                     @OA\Property(property="edited_by", type="string", example="Dr. Ayman"),
     *                     @OA\Property(property="patient_id", type="integer", example=5),
     *                     @OA\Property(property="patient_name", type="string", example="Sarah"),
     *                     @OA\Property(property="patient_photo", type="string", example="https://domain.com/photos/sarah.jpg"),
     *                     @OA\Property(
     *                         property="message",
     *                         type="object",
     *                         @OA\Property(property="message", type="string", example="Please follow up next week"),
     *                         @OA\Property(property="updated_at", type="string", example="2024-08-25 14:35:00")
     *                     ),
     *                     @OA\Property(property="doctor_id", type="integer", example=3),
     *                     @OA\Property(property="doctor_name", type="string", example="Dr. Mostafa")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Provider request not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Provider request not found")
     *         )
     *     )
     * )
     */
    public function __invoke($providerRequestID)
    {
        return $this->providerRequestService->getProviderRequestHistory($providerRequestID);
    }
    
}