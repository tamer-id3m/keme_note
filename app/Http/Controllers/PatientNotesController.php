<?php

namespace App\Http\Controllers\V4;

use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use App\Services\V4\PatientNote\PatientNoteService;

class PatientNotesController extends Controller
{
    use ApiResponseTrait;

    protected $patientNoteService;
    /**
     * Class constructor.
     *
     * Initializes the service with the given PatientNoteService and applies middleware for permission checks.
     * The middleware ensures that only users with the 'clinicalNote-list' permission can access the 'patientNotes' method.
     *
     * @param \App\Services\V4\PatientNote\PatientNoteService $patientNoteService The service responsible for handling patient notes.
     *
     * @return void
     */
    public function __construct(PatientNoteService $patientNoteService)
    {
        $this->patientNoteService = $patientNoteService;
        $this->middleware('permission:clinicalNote-list', ['only' => ['patientNotes']]);
    }

    /**
     * Handles the request to retrieve notes for a specific patient or user.
     *
     * This method delegates the logic to the `patientNoteService` to fetch the notes based
     * on the user's role and permissions, sorting, filtering, and pagination as per the request.
     *
     * @param  \Illuminate\Http\Request  $request  The HTTP request object containing query parameters like 'sortby', 'sort', and 'per_page'.
     * @param  string  $user_uuid  The UUID of the user whose notes are to be retrieved.
     * @return \Illuminate\Http\JsonResponse JSON response with the requested notes or an error message.
     */
     /**
 * @OA\Get(
 *     path="/api/v4/patient-notes/{userUuid}",
 *     summary="Get patient clinical notes",
 *     description="Retrieve paginated clinical notes for a specific user (Patient, Parent, Doctor, Staff, PCM) by UUID. Supports role-based filtering and offset pagination.",
 *     operationId="getPatientNotes",
 *     tags={"Patient Notes"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(
 *         name="userUuid",
 *         in="path",
 *         description="UUID of the user whose clinical notes are to be retrieved",
 *         required=true,
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Parameter(
 *         name="sortBy",
 *         in="query",
 *         description="Column to sort results by (default: id)",
 *         required=false,
 *         @OA\Schema(type="string", example="id")
 *     ),
 *     @OA\Parameter(
 *         name="sort",
 *         in="query",
 *         description="Sort direction (asc or desc, default: desc)",
 *         required=false,
 *         @OA\Schema(type="string", enum={"asc","desc"}, example="desc")
 *     ),
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         description="Number of results per page (default: from system pagination settings)",
 *         required=false,
 *         @OA\Schema(type="integer", example=10)
 *     ),
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Current page number (default: 1)",
 *         required=false,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Notes retrieved successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Notes retrieved successfully"),
 *             @OA\Property(property="status_code", type="integer", example=200),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(
 *                     property="data",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=115),
 *                         @OA\Property(property="subjective", type="string", example=""),
 *                         @OA\Property(property="chief_complaint", type="string", example="hhhhh"),
 *                         @OA\Property(property="history_of_present_illness", type="string", example=""),
 *                         @OA\Property(property="current_medications", type="string", example=""),
 *                         @OA\Property(property="diagnosis", type="string", example=""),
 *                         @OA\Property(property="assessments", type="string", example=""),
 *                         @OA\Property(property="plan", type="string", example=""),
 *                         @OA\Property(property="procedures", type="string", example=""),
 *                         @OA\Property(property="medications", type="string", example=""),
 *                         @OA\Property(property="risks_benefits_discussion", type="string", example=""),
 *                         @OA\Property(property="care_plan", type="string", example=""),
 *                         @OA\Property(property="next_follow_up", type="string", example=""),
 *                         @OA\Property(property="next_follow_up_value", type="string", nullable=true, example=null),
 *                         @OA\Property(property="next_follow_up_timeframe", type="string", nullable=true, example=null),
 *                         @OA\Property(property="date", type="string", format="date-time", example="2025-08-11T12:39:11+00:00"),
 *                         @OA\Property(property="doctor_id", type="integer", example=760),
 *                         @OA\Property(property="user_id", type="integer", example=760),
 *                         @OA\Property(property="patient_id", type="integer", example=980),
 *                         @OA\Property(
 *                             property="labs1",
 *                             type="array",
 *                             @OA\Items(
 *                                 type="object",
 *                                 @OA\Property(property="id", type="integer", example=1),
 *                                 @OA\Property(property="name", type="string", example="Lab name"),
 *                                 @OA\Property(property="active", type="integer", example=1),
 *                                 @OA\Property(property="uuid", type="string", example=""),
 *                                 @OA\Property(property="created_at", type="string", nullable=true, example=null),
 *                                 @OA\Property(property="updated_at", type="string", nullable=true, example=null),
 *                                 @OA\Property(property="deleted_at", type="string", nullable=true, example="2024-10-24 07:55:11")
 *                             )
 *                         ),
 *                         @OA\Property(property="patient_full_name", type="string", example="patientName patientLAst"),
 *                         @OA\Property(property="patient_photo", type="string", example="storage/uploads/userImages/1748335185.png"),
 *                         @OA\Property(property="patient_clinic", type="string", example="november_clinic"),
 *                         @OA\Property(property="patient_doctor", type="string", example="henddoc shaabann"),
 *                         @OA\Property(property="pat_id", type="string", example="28792"),
 *                         @OA\Property(property="pat_date", type="string", example="01/01/1999"),
 *                         @OA\Property(property="user_full_name", type="string", example="Administrator Keme"),
 *                         @OA\Property(property="user_photo", type="string", example="img/users/1723450672.PNG"),
 *                         @OA\Property(property="lap", type="array", @OA\Items(type="integer", example=1)),
 *                         @OA\Property(property="labsView", type="string", example=" -qqqqqqqqqqqq"),
 *                         @OA\Property(property="medicationsView", type="string", example=" -Ahmed medication [{...}]"),
 *                         @OA\Property(
 *                             property="medication",
 *                             type="array",
 *                             @OA\Items(
 *                                 type="object",
 *                                 @OA\Property(property="id", type="integer", example=28),
 *                                 @OA\Property(property="name", type="string", example="Ahmed medication => gggggggggg"),
 *                                 @OA\Property(property="directions", type="string", example="hhhhhhhhhhhhh")
 *                             )
 *                         ),
 *                         @OA\Property(property="is_shared", type="integer", example=0),
 *                         @OA\Property(property="approved_by", type="string", nullable=true, example="Administrator Keme"),
 *                         @OA\Property(property="approved_photo", type="string", nullable=true, example="cHnkLuIaRYPaF4N.png"),
 *                         @OA\Property(property="resource", type="string", example="clinical_note")
 *                     )
 *                 ),
 *                 @OA\Property(
 *                     property="links",
 *                     type="object",
 *                     @OA\Property(property="first", type="string", example="http://backend.test/api/v4/patient-notes/{uuid}?page=1"),
 *                     @OA\Property(property="last", type="string", example="http://backend.test/api/v4/patient-notes/{uuid}?page=2"),
 *                     @OA\Property(property="prev", type="string", nullable=true, example=null),
 *                     @OA\Property(property="next", type="string", nullable=true, example="http://backend.test/api/v4/patient-notes/{uuid}?page=2")
 *                 ),
 *                 @OA\Property(
 *                     property="meta",
 *                     type="object",
 *                     @OA\Property(property="current_page", type="integer", example=1),
 *                     @OA\Property(property="from", type="integer", example=1),
 *                     @OA\Property(property="last_page", type="integer", example=2),
 *                     @OA\Property(property="path", type="string", example="http://backend.test/api/v4/patient-notes/{uuid}"),
 *                     @OA\Property(property="per_page", type="integer", example=10),
 *                     @OA\Property(property="to", type="integer", example=10),
 *                     @OA\Property(property="total", type="integer", example=15)
 *                 )
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=404,
 *         description="User not found or resource not available",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="User not found"),
 *             @OA\Property(property="status_code", type="integer", example=404)
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Failed to retrieve notes",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Failed to retrieve notes"),
 *             @OA\Property(property="status_code", type="integer", example=500),
 *             @OA\Property(property="errors", type="string", nullable=true, example="Error details here")
 *         )
 *     )
 * )
 */


    public function patientNotes(Request $request, $user_uuid)
    {
        return $this->patientNoteService->patientNotes($request, $user_uuid);
    }
}