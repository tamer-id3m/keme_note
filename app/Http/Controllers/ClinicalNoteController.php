<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use App\Services\ClinicalNotes\ClinicalNotesService;
use App\Http\Requests\ClinicalNotes\ClinicalNotesRequest;
use App\Http\Requests\ClinicalNotes\UpdateClinicalNotesRequest;
use App\Http\Middleware\PermissionMiddleware;

class ClinicalNoteController extends Controller
{
    use ApiResponseTrait;

    protected $clinicalNoteService;

    public function __construct(ClinicalNotesService $clinicalNoteService)
    {

        $this->clinicalNoteService = $clinicalNoteService;
        $this->middleware('permission:clinicalNote-show', ['only' => ['show']]);
        $this->middleware('permission:clinicalNote-create', ['only' => ['store']]);
        $this->middleware('permission:clinicalNote-edit', ['only' => ['update']]);
        $this->middleware('permission:clinicalNote-delete', ['only' => ['destroy']]);
        $this->middleware('permission:clinicalNote-share-status', ['only' => ['shareStatus']]);
    }

    /**
     * Display the specified clinical note.
     *
     * @param  int  $id  The ID of the clinical note.
     * @return \Illuminate\Http\JsonResponse
     */
    /**
 * @OA\Get(
 *     path="/api/v4/clinical-notes/{id}",
 *     summary="Get a clinical note by ID",
 *     description="Returns a single clinical note with associated AI note, doctor, patient clinic, and other details.",
 *     operationId="getClinicalNoteById",
 *     tags={"Clinical Notes"},
 *
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Clinical Note ID",
 *         @OA\Schema(type="integer", example=91)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Clinical Note retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="success"),
 *             @OA\Property(property="status_code", type="integer", example=200),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=91),
 *                 @OA\Property(property="subjective", type="string", example=""),
 *                 @OA\Property(property="chief_complaint", type="string", example=""),
 *                 @OA\Property(property="history_of_present_illness", type="string", example=""),
 *                 @OA\Property(property="current_medications", type="string", example=""),
 *                 @OA\Property(property="diagnosis", type="string", example=""),
 *                 @OA\Property(property="assessments", type="string", example=""),
 *                 @OA\Property(property="plan", type="string", example=""),
 *                 @OA\Property(property="procedures", type="string", example=""),
 *                 @OA\Property(property="medications", type="string", example=""),
 *                 @OA\Property(property="risks_benefits_discussion", type="string", example=""),
 *                 @OA\Property(property="care_plan", type="string", example=""),
 *                 @OA\Property(property="next_follow_up", type="string", example=""),
 *                 @OA\Property(property="next_follow_up_value", type="string", nullable=true, example=null),
 *                 @OA\Property(property="next_follow_up_timeframe", type="string", nullable=true, example=null),
 *                 @OA\Property(property="date", type="string", format="date", example="2025-06-23"),
 *                 @OA\Property(property="doctor_id", type="integer", example=760),
 *                 @OA\Property(property="user_id", type="integer", example=760),
 *                 @OA\Property(property="patient_id", type="integer", example=980),
 *                 @OA\Property(property="labs1", type="array", @OA\Items(type="object")),
 *                 @OA\Property(property="patient_full_name", type="string", example="mmmm kkkkk"),
 *                 @OA\Property(property="patient_photo", type="string", example="storage/uploads/userImages/1748335185.png"),
 *                 @OA\Property(property="patient_clinic", type="string", example="new clinic"),
 *                 @OA\Property(property="patient_doctor", type="string", example="NesreenDoctor Rashed"),
 *                 @OA\Property(property="pat_id", type="string", example="342900"),
 *                 @OA\Property(property="pat_date", type="string", example="1/1/1999"),
 *                 @OA\Property(property="user_full_name", type="string", example="henddoc shaabann"),
 *                 @OA\Property(property="lap", type="array", @OA\Items(type="object")),
 *                 @OA\Property(property="labsView", type="string", example=""),
 *                 @OA\Property(property="medicationsView", type="string", example=""),
 *                 @OA\Property(property="medication", type="string", example=""),
 *                 @OA\Property(property="is_shared", type="boolean", example=false),
 *                 @OA\Property(property="approved_by", type="string", example="Administrator Keme"),
 *                 @OA\Property(property="approved_photo", type="string", example="cHnkLuIaRYPaF4N.png"),
 *                 @OA\Property(property="resource", type="string", example="smart_note")
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=404,
 *         description="Clinical Note not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Clinical Note not found"),
 *             @OA\Property(property="status_code", type="integer", example=404),
 *            @OA\Property(property="data", type="array", @OA\Items(), example={})
 *         )
 *     )
 * )
 */

    public function show($id)
    {
        return $this->clinicalNoteService->show($id);
    }

    /**
     * Store a newly created clinical note.
     *
     * @param  \App\Http\Requests\ClinicalNotesRequest  $request  The validated request instance.
     * @return \Illuminate\Http\JsonResponse
     */
    /**
 * @OA\Post(
 *     path="/api/v4/clinical-notes",
 *     summary="Create a new clinical note",
 *     description="Stores a new clinical note and attaches labs and medications to it.",
 *     operationId="storeClinicalNote",
 *     tags={"Clinical Notes"},
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"subjective", "chief_complaint", "patient_id"},
 *             @OA\Property(property="subjective", type="string", example="Helloiimmnjs"),
 *             @OA\Property(property="chief_complaint", type="string", example="hhhhh"),
 *             @OA\Property(property="history_of_present_illness", type="string", example=""),
 *             @OA\Property(property="current_medications", type="string", example=""),
 *             @OA\Property(property="diagnosis", type="string", example=""),
 *             @OA\Property(property="assessments", type="string", example=""),
 *             @OA\Property(property="plan", type="string", example=""),
 *             @OA\Property(property="procedures", type="string", example=""),
 *             @OA\Property(property="medications", type="string", example=""),
 *             @OA\Property(property="risks_benefits_discussion", type="string", example=""),
 *             @OA\Property(property="care_plan", type="string", example=""),
 *             @OA\Property(property="next_follow_up", type="string", example=""),
 *             @OA\Property(property="next_follow_up_value", type="string", example=null),
 *             @OA\Property(property="next_follow_up_timeframe", type="string", example=null),
 *             @OA\Property(property="patient_id", type="integer", example=980),
 *             @OA\Property(property="lab", type="array", @OA\Items(type="integer", example=1)),
 *             @OA\Property(property="medication", type="array", @OA\Items(type="integer", example=1))
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Clinical note created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="clinical_note.created_successfully"),
 *             @OA\Property(property="status_code", type="integer", example=201),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=112),
 *                 @OA\Property(property="subjective", type="string", example="Helloiimmnjs"),
 *                 @OA\Property(property="chief_complaint", type="string", example="hhhhh"),
 *                 @OA\Property(property="history_of_present_illness", type="string", example=""),
 *                 @OA\Property(property="current_medications", type="string", example=""),
 *                 @OA\Property(property="diagnosis", type="string", example=""),
 *                 @OA\Property(property="assessments", type="string", example=""),
 *                 @OA\Property(property="plan", type="string", example=""),
 *                 @OA\Property(property="procedures", type="string", example=""),
 *                 @OA\Property(property="medications", type="string", example=""),
 *                 @OA\Property(property="risks_benefits_discussion", type="string", example=""),
 *                 @OA\Property(property="care_plan", type="string", example=""),
 *                 @OA\Property(property="next_follow_up", type="string", example=""),
 *                 @OA\Property(property="next_follow_up_value", type="string", example=null),
 *                 @OA\Property(property="next_follow_up_timeframe", type="string", example=null),
 *                 @OA\Property(property="date", type="string", example="2025-07-07"),
 *                 @OA\Property(property="doctor_id", type="integer", example=1),
 *                 @OA\Property(property="user_id", type="integer", example=1),
 *                 @OA\Property(property="patient_id", type="integer", example=980),
 *                 @OA\Property(
 *                     property="labs1",
 *                     type="array",
 *                     @OA\Items(
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="name", type="string", example="qqqqqqqqqqqq"),
 *                         @OA\Property(property="active", type="integer", example=1),
 *                         @OA\Property(property="uuid", type="string", example=""),
 *                         @OA\Property(property="created_at", type="string", nullable=true, example=null),
 *                         @OA\Property(property="updated_at", type="string", nullable=true, example=null),
 *                         @OA\Property(property="deleted_at", type="string", example="2024-10-24 07:55:11")
 *                     )
 *                 ),
 *                 @OA\Property(property="patient_full_name", type="string", example="mmmm kkkkk"),
 *                 @OA\Property(property="patient_photo", type="string", example="storage/uploads/userImages/1748335185.png"),
 *                 @OA\Property(property="patient_clinic", type="string", example="new clinic"),
 *                 @OA\Property(property="patient_doctor", type="string", example="NesreenDoctor Rashed"),
 *                 @OA\Property(property="pat_id", type="string", example="342900"),
 *                 @OA\Property(property="pat_date", type="string", example="1/1/1999"),
 *                 @OA\Property(property="user_full_name", type="string", example="Administrator Keme"),
 *                 @OA\Property(property="lap", type="array", @OA\Items(type="integer", example=1)),
 *                 @OA\Property(property="labsView", type="string", example=" -qqqqqqqqqqqq"),
 *                 @OA\Property(property="medicationsView", type="string", example=" -Ahmed medication [...]"),
 *                 @OA\Property(
 *                     property="medication",
 *                     type="array",
 *                     @OA\Items(
 *                         @OA\Property(property="id", type="integer", example=28),
 *                         @OA\Property(property="name", type="string", example="Ahmed medication => gggggggggg"),
 *                         @OA\Property(property="directions", type="string", example="hhhhhhhhhhhhh")
 *                     )
 *                 ),
 *                 @OA\Property(property="is_shared", type="boolean", example=false),
 *                 @OA\Property(property="approved_by", type="string", nullable=true, example=null),
 *                 @OA\Property(property="approved_photo", type="string", nullable=true, example=null),
 *                 @OA\Property(property="resource", type="string", example="clinical_note")
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error while creating clinical note",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="An error occurred while creating the clinical note."),
 *             @OA\Property(property="status_code", type="integer", example=500),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="error", type="string", example="SQLSTATE[23000]: Integrity constraint violation ...")
 *             )
 *         )
 *     )
 * )
 */

    public function store(ClinicalNotesRequest $request)
    {
        return $this->clinicalNoteService->store($request);
    }

    /**
     * Update the specified clinical note.
     *
     * @param  \App\Http\Requests\UpdateClinicalNotesRequest  $request  The validated request instance.
     * @param  int  $id  The ID of the clinical note to update.
     * @return \Illuminate\Http\JsonResponse
     */
    /**
 * @OA\Put(
 *     path="/api/v4/clinical-notes/{id}",
 *     summary="Update an existing clinical note",
 *     description="Updates a clinical note by ID and synchronizes its labs and medications.",
 *     operationId="updateClinicalNote",
 *     tags={"Clinical Notes"},
 *
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the clinical note to update",
 *         @OA\Schema(type="integer", example=110)
 *     ),
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"subjective", "chief_complaint", "patient_id"},
 *             @OA\Property(property="subjective", type="string", example="gggggggg"),
 *             @OA\Property(property="chief_complaint", type="string", example="hhhhh"),
 *             @OA\Property(property="history_of_present_illness", type="string", example=""),
 *             @OA\Property(property="current_medications", type="string", example=""),
 *             @OA\Property(property="diagnosis", type="string", example="nnnnn"),
 *             @OA\Property(property="assessments", type="string", example=""),
 *             @OA\Property(property="plan", type="string", example=""),
 *             @OA\Property(property="procedures", type="string", example=""),
 *             @OA\Property(property="medications", type="string", example=""),
 *             @OA\Property(property="risks_benefits_discussion", type="string", example=""),
 *             @OA\Property(property="care_plan", type="string", example=""),
 *             @OA\Property(property="next_follow_up", type="string", example=""),
 *             @OA\Property(property="next_follow_up_value", type="string", example=null),
 *             @OA\Property(property="next_follow_up_timeframe", type="string", example=null),
 *             @OA\Property(property="patient_id", type="integer", example=922),
 *             @OA\Property(property="lab", type="array", @OA\Items(type="integer", example=5)),
 *             @OA\Property(property="medication", type="array", @OA\Items(type="integer", example=28))
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Clinical note updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Updated Successfully"),
 *             @OA\Property(property="status_code", type="integer", example=200),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=110),
 *                 @OA\Property(property="subjective", type="string", example="gggggggg"),
 *                 @OA\Property(property="chief_complaint", type="string", example="hhhhh"),
 *                 @OA\Property(property="history_of_present_illness", type="string", example=""),
 *                 @OA\Property(property="current_medications", type="string", example=""),
 *                 @OA\Property(property="diagnosis", type="string", example="nnnnn"),
 *                 @OA\Property(property="assessments", type="string", example=""),
 *                 @OA\Property(property="plan", type="string", example=""),
 *                 @OA\Property(property="procedures", type="string", example=""),
 *                 @OA\Property(property="medications", type="string", example=""),
 *                 @OA\Property(property="risks_benefits_discussion", type="string", example=""),
 *                 @OA\Property(property="care_plan", type="string", example=""),
 *                 @OA\Property(property="next_follow_up", type="string", example=""),
 *                 @OA\Property(property="next_follow_up_value", type="string", example=null),
 *                 @OA\Property(property="next_follow_up_timeframe", type="string", example=null),
 *                 @OA\Property(property="date", type="string", example="2025-07-06"),
 *                 @OA\Property(property="doctor_id", type="integer", example=1),
 *                 @OA\Property(property="user_id", type="integer", example=1),
 *                 @OA\Property(property="patient_id", type="integer", example=922),
 *                 @OA\Property(
 *                     property="labs1",
 *                     type="array",
 *                     @OA\Items(
 *                         @OA\Property(property="id", type="integer", example=5),
 *                         @OA\Property(property="name", type="string", example="Hend lab"),
 *                         @OA\Property(property="active", type="integer", example=0),
 *                         @OA\Property(property="uuid", type="string", example="b01e7f6b-4bd8-4c75-856c-81ec2bcd7959"),
 *                         @OA\Property(property="created_at", type="string", example="2025-03-30T11:31:13.000000Z"),
 *                         @OA\Property(property="updated_at", type="string", example="2025-03-30T11:46:32.000000Z"),
 *                         @OA\Property(property="deleted_at", type="string", example=null)
 *                     )
 *                 ),
 *                 @OA\Property(property="patient_full_name", type="string", example="Hend Shaaban"),
 *                 @OA\Property(property="patient_photo", type="string", example="storage/uploads/userImages/1736244009.PNG"),
 *                 @OA\Property(property="patient_clinic", type="string", example="new clinic"),
 *                 @OA\Property(property="patient_doctor", type="string", example="henddoc shaabann"),
 *                 @OA\Property(property="pat_id", type="string", example="507606"),
 *                 @OA\Property(property="pat_date", type="string", example=""),
 *                 @OA\Property(property="user_full_name", type="string", example="Administrator Keme"),
 *                 @OA\Property(property="lap", type="array", @OA\Items(type="integer", example=5)),
 *                 @OA\Property(property="labsView", type="string", example=" -Hend lab"),
 *                 @OA\Property(property="medicationsView", type="string", example=" -Ahmed medication [...] -Helloi []"),
 *                 @OA\Property(
 *                     property="medication",
 *                     type="array",
 *                     @OA\Items(
 *                         @OA\Property(property="id", type="integer", example=28),
 *                         @OA\Property(property="name", type="string", example="Ahmed medication => gggggggggg"),
 *                         @OA\Property(property="directions", type="string", example="hhhhhhhhhhhhh")
 *                     )
 *                 ),
 *                 @OA\Property(property="is_shared", type="boolean", example=true),
 *                 @OA\Property(property="approved_by", type="string", example=null),
 *                 @OA\Property(property="approved_photo", type="string", example=null),
 *                 @OA\Property(property="resource", type="string", example="clinical_note")
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=404,
 *         description="Clinical note not found",
 *         @OA\JsonContent(
 *        @OA\Property(property="message", type="string", example="Clinical Note not found"),
 *        @OA\Property(property="status_code", type="integer", example=404),
 *        @OA\Property(property="data", type="array", @OA\Items(), example={})
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=500,
 *         description="Server error while updating clinical note",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Something went wrong."),
 *             @OA\Property(property="status_code", type="integer", example=500),
 *             @OA\Property(property="data", type="string", example="SQL error or exception message...")
 *         )
 *     )
 * )
 */

    public function update(UpdateClinicalNotesRequest $request, $id)
    {
        return $this->clinicalNoteService->update($request, $id);
    }
    /**
     * Remove the specified clinical note.
     *
     * @param  int  $id  The ID of the clinical note to delete.
     * @return \Illuminate\Http\JsonResponse
     */
    /**
 * @OA\Delete(
 *     path="/api/v4/clinical-notes/{id}",
 *     summary="Delete a clinical note",
 *     description="Deletes a clinical note by ID, detaching related labs and medications, and clearing cached data.",
 *     operationId="deleteClinicalNote",
 *     tags={"Clinical Notes"},
 *
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the clinical note to delete",
 *         @OA\Schema(type="integer", example=110)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Clinical note deleted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="clinical note deleted successfully"),
 *             @OA\Property(property="status_code", type="integer", example=200),
 *             @OA\Property(property="data", type="array", @OA\Items(), example={})
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=404,
 *         description="Clinical note not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="clinical note not found"),
 *             @OA\Property(property="status_code", type="integer", example=404),
 *             @OA\Property(property="data", type="array", @OA\Items(), example={})
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=500,
 *         description="Server error while deleting clinical note",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to delete clinical note."),
 *             @OA\Property(property="status_code", type="integer", example=500),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="error", type="string", example="SQL error or exception message...")
 *             )
 *         )
 *     )
 * )
 */


    public function destroy($id)
    {
        return $this->clinicalNoteService->destroy($id);
    }

    /**
     * Toggle the shared status of the specified clinical note.
     *
     * @param  int  $id  The ID of the clinical note.
     * @return \Illuminate\Http\JsonResponse
     */
    /**
 * @OA\Post(
 *     path="/api/v4/clinical-notes/share-status/{id}",
 *     summary="Toggle share status of a clinical note",
 *     description="Toggles the `is_shared` status of a clinical note and updates the patient's type if needed.",
 *     operationId="shareClinicalNoteStatus",
 *     tags={"Clinical Notes"},
 *
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the clinical note",
 *         @OA\Schema(type="integer", example=91)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Share status toggled successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Status Activated Successfully"),
 *             @OA\Property(property="status_code", type="integer", example=200),
 *             @OA\Property(property="data", type="array", @OA\Items(), example={})
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=404,
 *         description="Clinical note not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Clinical Note not found"),
 *             @OA\Property(property="status_code", type="integer", example=404),
 *             @OA\Property(property="data", type="array", @OA\Items(), example={})
 *         )
 *     )
 * )
 */

    public function shareStatus($id)
    {
        return $this->clinicalNoteService->shareStatus($id);
    }


    public function showInternal($id)
    {
        $note = $this->clinicalNoteService->show($id);
        return response()->json([
            'data' => $note->getData()->data ?? null
        ]);
    }

    public function getByPatientId($patientId)
    {
        try {
            // You'll need to add this method to your ClinicalNotesService
            $notes = $this->clinicalNoteService->getByPatientId($patientId);
            return response()->json([
                'data' => $notes,
                'count' => $notes->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByIds(Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            // You'll need to add this method to your ClinicalNotesService
            $notes = $this->clinicalNoteService->getByIds($ids);
            return response()->json([
                'data' => $notes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

     public function deleteByPatientId($patientId)
    {
        try {
            // You'll need to add this method to your ClinicalNotesService
            $result = $this->clinicalNoteService->deleteByPatientId($patientId);
            return response()->json([
                'message' => 'Clinical notes deleted successfully',
                'deleted_count' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getCountByPatient($patientId)
    {
        try {
            // You'll need to add this method to your ClinicalNotesService
            $count = $this->clinicalNoteService->getCountByPatient($patientId);
            return response()->json([
                'data' => ['count' => $count]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}