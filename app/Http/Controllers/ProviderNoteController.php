<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProviderNote\ProviderNote\StoreProviderNoteRequest;
use App\Http\Requests\ProviderNote\ProviderNote\UpdateProviderNoteRequest;
use App\Services\V4\ProviderNote\ProviderNoteService;
use Illuminate\Http\Request;
/**
 * @OA\Schema(
 *     schema="ProviderNote",
 *     type="object",
 *     required={"id", "patient_id", "body"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="patient_id", type="integer", example=123),
 *     @OA\Property(property="provider_id", type="integer", example=456),
 *     @OA\Property(property="body", type="string", example="Patient is responding well to treatment"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class ProviderNoteController extends Controller
{
    protected $providerNoteService;

    public function __construct(providerNoteService $providerNoteService)
    {
        $this->middleware('permission:provider-note-create', ['only' => ['store']]);
        $this->middleware('permission:provider-note-list', ['only' => ['index']]);
        $this->middleware('permission:provider-note-show', ['only' => ['show']]);
        $this->middleware('permission:provider-note-edit', ['only' => ['update']]);
        $this->middleware('permission:provider-note-delete', ['only' => ['destroy']]);
        $this->middleware('permission:provider-note-mention', ['only' => ['noteMention']]);

        $this->providerNoteService = $providerNoteService;
    }

    /**
     * @OA\Get(
     *     path="/api/v4/provider-notes/index/{patient_id}",
     *     summary="List provider notes for a patient",
     *     tags={"Provider Notes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="patient_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items()
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="This action is unauthorized."
     *             )
     *         )
     *     )
     * )
     */

    public function index(Request $request, $patient_id)
    {
        return $this->providerNoteService->index($request, $patient_id);
    }

    /**
     * @OA\Post(
     *     path="/api/v4/provider-notes/create",
     *     summary="Create new provider note",
     *     tags={"Provider Notes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"doctor_id", "patient_id", "body"},
     *             @OA\Property(property="doctor_id", type="integer", example=3, description="Doctor's user ID (must exist in users table)"),
     *             @OA\Property(property="patient_id", type="integer", example=5, description="Patient's user ID (must exist in users table)"),
     *             @OA\Property(property="body", type="string", example="Follow-up note for the patient")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created Successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=12),
     *             @OA\Property(property="doctor_id", type="integer", example=3),
     *             @OA\Property(property="patient_id", type="integer", example=5),
     *             @OA\Property(property="body", type="string", example="Follow-up note for the patient"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-07-02T10:30:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-07-02T10:30:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Please fill in required fields."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="doctor_id", type="array", @OA\Items(type="string", example="The selected doctor_id is invalid.")),
     *                 @OA\Property(property="patient_id", type="array", @OA\Items(type="string", example="The patient_id field is required.")),
     *                 @OA\Property(property="body", type="array", @OA\Items(type="string", example="The body field is required."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     )
     * )
     */
    public function store(StoreProviderNoteRequest $request)
    {
        return $this->providerNoteService->store($request);
    }

    /**
     * @OA\Get(
     *     path="/api/v4/provider-notes/show/{id}",
     *     summary="Show a specific provider note",
     *     tags={"Provider Notes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function show($note)
    {
        return $this->providerNoteService->show($note);
    }

    /**
     * @OA\Post(
     *     path="/api/v4/provider-notes/edit/{id}",
     *     summary="Update an existing provider note",
     *     tags={"Provider Notes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Provider note ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"body"},
     *             @OA\Property(property="body", type="string", example="Updated note content.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Updated Successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=12),
     *             @OA\Property(property="body", type="string", example="Updated note content."),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-07-02T11:00:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Please fill in required fields."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="body", type="array", @OA\Items(type="string", example="The body field is required."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     )
     * )
     */
    public function update(UpdateProviderNoteRequest $request, $id)
    {
        return $this->providerNoteService->update($request, $id);
    }


    /**
     * @OA\Post(
     *     path="/api/v4/provider-notes/delete/{id}",
     *     summary="Delete a provider note",
     *     tags={"Provider Notes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Deleted Successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Note deleted successfully.")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        return $this->providerNoteService->destroy($id);
    }

    /**
     * @OA\Get(
     *     path="/api/v4/provider-notes/mention/{clinicId}",
     *     summary="Get users available for mentions",
     *     tags={"Provider Notes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="clinicId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="mentionedUserId", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="mentionedUsername", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=12),
     *                 @OA\Property(property="name", type="string", example="Dr. John Smith"),
     *                 @OA\Property(property="email", type="string", example="dr.john@example.com")
     *             )
     *         )
     *     )
     * )
     */
    public function noteMention($id)
    {
        return $this->providerNoteService->noteMention($id);
    }
}