<?php

namespace App\Services\V4\PatientNote;

use App\Models\User;
use App\Enums\RoleType;
use App\Helpers\Helpers;
use App\Models\v3\ClinicalNote;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\V4\ClinicalNote\ClinicalNoteResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class PatientNoteService
{

use ApiResponseTrait;
/**
 * Retrieve and paginate clinical notes for a specific patient user.
 *
 * This method:
 * - Validates and retrieves the target user by UUID.
 * - Determines the authenticated user's role and administrative privileges.
 * - Applies sorting, pagination, and caching for efficient retrieval.
 * - Uses the getNotesQuery() method to build and execute the query.
 * - Wraps results in the ClinicalNoteResource for consistent API formatting.
 *
 * Sorting defaults:
 * - sortBy: "id"
 * - sort: "desc"
 *
 * Caching:
 * - Results are cached for 1 hour using a composite cache key based on
 *   target user UUID, authenticated user ID, pagination, and sort parameters.
 * - Cache tags: ['clinical_notes']
 *
 * Transactions:
 * - Method starts a database transaction and rolls back on any exception.
 *
 * @param  \Illuminate\Http\Request|mixed  $request   The incoming HTTP request instance containing query parameters.
 * @param  string                           $userUuid The UUID of the patient whose notes are being requested.
 *
 * @return \Illuminate\Http\JsonResponse
 *         A JSON response containing:
 *         - message: Status message
 *         - status_code: HTTP status code
 *         - data: Paginated clinical notes with meta and links
 *
 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the target user is not found.
 * @throws \Exception If any unexpected error occurs during retrieval.
 */
 public function patientNotes($request, string $userUuid)
    {
        DB::beginTransaction();

        try {
            $authUser = auth()->user();
            $sortBy = $request->input('sortBy', 'id');
            $sort = $request->input('sort', 'desc');
            $perPage = (int) $request->input('per_page', Helpers::getPagination());
            $page = max(1, (int) $request->input('page', 1));

            $targetUser = $this->getUserByUuid($userUuid);

            if (!$targetUser) {
                return $this->apiResponse('User not found', 404);
            }

            $userRole = $authUser->roles()->first()?->name;
            $isAdminType = $authUser->roles()->first()?->type === RoleType::Admin;

            $cacheKey = "clinical_notes_index_{$userUuid}_{$authUser->id}_{$perPage}_{$page}_{$sortBy}_{$sort}";

            $notes = Cache::tags(['clinical_notes'])->remember($cacheKey, 3600, function () use (
                $targetUser, $userRole, $isAdminType, $sortBy, $sort, $perPage, $page
            ) {
                return $this->getNotesQuery($targetUser, $userRole, $isAdminType, $sortBy, $sort, $perPage, $page);
            });

            $data = ClinicalNoteResource::collection($notes)->response()->getData(true);

            return $this->apiResponse('Notes retrieved successfully', 200, $data);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->apiResponse('Resource not found', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return $this->apiResponse('Failed to retrieve notes', 500, [], $e->getMessage());
        }
    }
/**
 * Retrieve a user by UUID with a valid role (Patient, Parent, Staff, Pcm, or Doctor).
 *
 * @param string $uuid The UUID of the user.
 *
 * @return \App\Models\User|null Returns the user instance if found and has the expected role; otherwise null.
 */
    private function getUserByUuid(string $uuid): ?User
    {
        return User::where('uuid', $uuid)
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['Patient', 'Parent', 'Staff', 'Pcm', 'Doctor']))
            ->first();
    }
    /**
     * Build and execute a paginated query to retrieve clinical notes with related data.
     *
     * This method fetches clinical notes with their associated AI notes, doctors,
     * on-demand smart notes, and appointment summaries, applying role-based access
     * filters and pagination. Sorting can be customized through request parameters.
     *
     * Relationships loaded:
     * - aiNote
     * - aiNote.approver
     * - doctor
     * - onDemandSmartNote
     * - onDemandSmartNote.approver
     * - appointmentSummary
     * - appointmentSummary.approver
     *
     * Columns selected include core note details such as patient, doctor, subjective/objective
     * information, medications, assessments, care plan, follow-up details, and timestamps.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|mixed  $user       The authenticated user instance.
     * @param  string                                             $role       The role of the user (e.g., "doctor", "nurse").
     * @param  bool                                               $isAdmin    Whether the user has administrative privileges.
     * @param  string                                             $sortBy     Column name to sort the results by.
     * @param  string                                             $sort       Sort direction ("asc" or "desc").
     * @param  int                                                $perPage    Number of results per page for pagination.
     * @param  int                                                $page       The current page number.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     *         A paginated collection of clinical notes with related models loaded.
     */
    private function getNotesQuery($user, string $role, bool $isAdmin, string $sortBy, string $sort, int $perPage, $page)
    {
        $query = ClinicalNote::with([
            'aiNote',
            'aiNote.approver',
            'doctor',
            'onDemandSmartNote',
            'onDemandSmartNote.approver',
            'appointmentSummary',
            'appointmentSummary.approver'
        ])->select([
            'id',
            'patient_id',
            'doctor_id',
            'subjective',
            'chief_complaint',
            'history_of_present_illness',
            'current_medications',
            'diagnosis',
            'assessments',
            'plan',
            'procedures',
            'medications',
            'risks_benefits_discussion',
            'care_plan',
            'next_follow_up',
            'next_follow_up_value',
            'next_follow_up_timeframe',
            'date',
            'is_shared',
            'resource',
            'created_at',
            'note_id',
            'on_demand_smart_note_id',
            'appointment_summary_id'
        ])->applyRoleFilter($user);



        if (!$this->canViewAllNotes($role, $isAdmin, auth()->user())) {
            $query->where('is_shared', 1);
        }

        return $query->orderBy($sortBy, $sort)
                     ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Determine if the user role or type allows access to all notes, including private ones.
     *
     * @param string $role The name of the authenticated user's role.
     * @param bool $isAdmin True if the user is an admin or has an admin role type.
     *
     * @return bool True if full access is allowed; false if only shared notes should be shown.
     */
    private function canViewAllNotes(string $role, bool $isAdmin, $authUser): bool
    {
        return in_array($role, ['Doctor', 'Admin', 'Staff','Pcm']) || $isAdmin ;
    }

}