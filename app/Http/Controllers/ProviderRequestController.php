<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProviderRequest\ProviderRequest\StoreProviderRequest;
use App\Http\Requests\ProviderRequest\ProviderRequest\UpdateProviderRequest;
use App\Services\ProviderRequest\ProviderRequestService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class ProviderRequestController extends Controller
{
    use ApiResponseTrait;

    protected $providerRequestService;

    public function __construct(ProviderRequestService $providerRequestService)
    {
        $this->middleware('permission:provider-note-create', ['only' => ['store']]);
        $this->middleware('permission:provider-note-list', ['only' => ['index']]);
        $this->middleware('permission:provider-note-show', ['only' => ['show']]);
        $this->middleware('permission:provider-note-edit', ['only' => ['update']]);
        $this->middleware('permission:provider-note-delete', ['only' => ['destroy']]);
        $this->middleware('permission:provider-note-mention', ['only' => ['noteMention']]);

        $this->providerRequestService = $providerRequestService;
    }

    /**
     * @OA\Get(
 *     path="/api/v4/provider-request-comments/index/{patientId}",
     *     tags={"Provider Request Comments"},
     *     summary="List all provider request comments",
     *     description="Returns a list of all provider request comments with associated user roles.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of comments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="Success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=101),
     *                     @OA\Property(property="body", type="string", example="Please check this note."),
     *                     @OA\Property(property="user_id", type="integer", example=5),
     *                     @OA\Property(property="patient_id", type="integer", example=8),
     *                     @OA\Property(property="provider_request_id", type="integer", example=12),
     *                     @OA\Property(property="edited", type="boolean", example=false),
     *                     @OA\Property(property="created_at", type="string", example="2024-08-01 09:15:00"),
     *                     @OA\Property(property="updated_at", type="string", example="2024-08-01 10:20:00"),
     *                     @OA\Property(
     *                         property="user",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=5),
     *                         @OA\Property(property="name", type="string", example="Dr. Ahmed"),
     *                         @OA\Property(property="username", type="string", example="dr.ahmed"),
     *                         @OA\Property(property="photo", type="string", example="https://example.com/user.png")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="You Don't Have The Permission")
     * )
     */

    public function index(Request $request, $patientId)
    {
        return $this->providerRequestService->index($request, $patientId);
    }

    /**
     * @OA\Get(
     *     path="/api/v4/provider-requests/show/{id}",
     *     tags={"Provider Requests"},
     *     summary="Get a specific provider request by UUID",
     *     description="Retrieve detailed information about a specific provider note using its UUID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="UUID of the provider request",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Provider request details",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="uuid", type="string", example="a1b2c3"),
     *                 @OA\Property(property="user_id", type="integer", example=10),
     *                 @OA\Property(property="message", type="string", example="Patient needs medication refill"),
     *                 @OA\Property(property="patient_id", type="integer", example=7),
     *                 @OA\Property(property="patient_name", type="string", example="Sarah"),
     *                 @OA\Property(property="patient_username", type="string", example="sarah2020"),
     *                 @OA\Property(property="patient_photo", type="string", example="https://domain.com/images/patient.jpg"),
     *                 @OA\Property(property="doctor_id", type="integer", example=3),
     *                 @OA\Property(property="doctor_name", type="string", example="Dr. Ahmed"),
     *                 @OA\Property(property="edited", type="boolean", example=false),
     *                 @OA\Property(property="updated_at", type="string", example="2024-07-30 12:00:00"),
     *                 @OA\Property(property="created_from", type="string", example="2 hours ago")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show($id)
    {
        return $this->providerRequestService->show($id);
    }

    /**
     * @OA\Post(
     *     path="/api/v4/provider-requests/create",
     *     tags={"Provider Requests"},
     *     summary="Create a new provider request",
     *     description="Allows PCM, Admin, or Staff to create a new provider request for a patient.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id", "body", "patient_id", "doctor_id"},
     *             @OA\Property(property="user_id", type="integer", example=10),
     *             @OA\Property(property="body", type="string", example="Follow-up request"),
     *             @OA\Property(property="patient_id", type="integer", example=5),
     *             @OA\Property(property="doctor_id", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Provider request created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="Added Successfully"),
     *             @OA\Property(property="code", type="integer", example=201),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function store(StoreProviderRequest $request)
    {
        return $this->providerRequestService->store($request);
    }

    /**
     * @OA\Patch(
     *     path="/api/v4/provider-requests/edit/{id}",
     *     tags={"Provider Requests"},
     *     summary="Update an existing provider request",
     *     description="Allows authorized users to update an existing provider note using its UUID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="UUID of the provider request",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="body", type="string", example="Updated request message"),
     *             @OA\Property(property="doctor_id", type="integer", example=4)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Provider request updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="Updated Successfully"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function update(UpdateProviderRequest $request, $id)
    {
        return $this->providerRequestService->update($request, $id);
    }

    /**
     * @OA\Delete(
     *     path="/api/v4/provider-requests/delete/{id}",
     *     tags={"Provider Requests"},
     *     summary="Delete a provider request",
     *     description="Soft deletes the provider request by UUID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="UUID of the provider request",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="Deleted Successfully"),
     *             @OA\Property(property="code", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy($id)
    {
        return $this->providerRequestService->destroy($id);
    }

    /**
     * @OA\Get(
     *     path="/api/v4/provider-requests/mention/{clinicId}",
     *     tags={"Provider Requests"},
     *     summary="Get mentionable users in a clinic",
     *     description="Returns users with roles Doctor or PCM in the clinic.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="clinicId",
     *         in="path",
     *         required=true,
     *         description="UUID of the clinic",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="mentionedUserId",
     *         in="query",
     *         required=false,
     *         description="ID of a specific user to mention",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="mentionedUsername",
     *         in="query",
     *         required=false,
     *         description="Username to filter",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mentionable users returned",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=4),
     *                     @OA\Property(property="name", type="string", example="Dr. Osama"),
     *                     @OA\Property(property="username", type="string", example="osama_doc"),
     *                     @OA\Property(property="role", type="string", example="Doctor")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function noteMention($id)
    {
        return $this->providerRequestService->noteMention($id);
    }
}