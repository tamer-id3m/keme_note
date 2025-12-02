<?php

namespace App\Services\ProviderRequest;

use App\Helpers\Helpers;
use App\Http\Clients\ClinicClient;
use App\Http\Clients\UserClient;
use App\Http\Resources\ProviderRequest\ProviderRequestHistoryResource;
use App\Http\Resources\ProviderRequest\ProviderRequestResource;
use App\Models\Clinics;
use App\Models\User;
use App\Models\ProviderRequest;
use App\Models\ProviderRequestHistory;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class ProviderRequestService
 *
 * This service handles the logic for managing provider requests including:
 * - Listing provider requests for patients
 * - Retrieving specific provider request by UUID
 * - Creating new provider requests
 * - Updating existing provider requests
 * - Managing note mentions for Doctors, PCMs, and Admins within clinics
 */
class ProviderRequestService
{
    use ApiResponseTrait;

    private function filterByRole($query, $user)
    {
        if ($user->hasRole('Pcm')) {
            $doctorIds = $user->pcmDoctors->pluck('id')->toArray();
            $query->whereIn('doctor_id', $doctorIds);
        } elseif ($user->hasRole('Staff')) {
            $doctorIds = $user->staffDoctors->pluck('id')->toArray();
            $query->whereIn('doctor_id', $doctorIds);
        } elseif ($user->hasRole('Doctor')) {
            $query->where('doctor_id', $user->id);
        } elseif (!empty($user->doctor_id)) {
            $query->where('doctor_id', $user->doctor_id);
        } elseif ($user->hasRole('Patient')) {
            $query->where('id', $user->id);
        }

        return $query;
    }

    /**
     * Retrieve a paginated list of provider requests for a specific patient.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming request containing optional filters such as UUID, sorting details, and pagination.
     * @param  string  $patientId  The ID of the patient whose provider requests are being retrieved.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns a paginated list of provider requests with meta data.
     *                                       - Unauthorized: Returns a 403 response if the user lacks the appropriate role to view the requests.
     *                                       - Error: Returns a 500 response for unexpected exceptions during processing.
     */
    public function index(Request $request, $patientId)
    {
        $user = Auth::user();
        $query = ProviderRequest::query()->where('patient_id', $patientId);

        $this->filterByRole($query, $user);

        if ($request->filled('uuid')) {
            $query->where('uuid', $request->uuid);
        }

        $requests = $query->with(['patient', 'providerRequestComments'])
            ->orderBy($request->input('sortBy', 'id'), $request->input('sort', 'desc'))
            ->latest()
            ->paginate($request->input('per_page', Helpers::getPagination()));

        return $this->apiResponse('success', 200, ProviderRequestResource::collection($requests)->response()->getData(true));
    }

    /**
     * Retrieve a specific provider request by its UUID.
     *
     * @param  string  $id  The UUID of the provider request.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns the provider request details.
     *                                       - Unauthorized: Returns a 403 response if the user lacks permission to view the request.
     *                                       - Not Found: Returns a 404 response if the request with the given UUID does not exist.
     */
    public function show($id)
    {
        $user = Auth::user();
        $query = ProviderRequest::with(['patient', 'providerRequestComments'])->where('uuid', $id);

        $this->filterByRole($query, $user);

        $providerRequest = $query->first();

        if (! $providerRequest) {
            return $this->apiResponse('Invalid ID or insufficient permissions', 404);
        }

        return $this->apiResponse('success', 200, new ProviderRequestResource($providerRequest));
    }

    /**
     * Create a new provider request.
     *
     * @param  \App\Http\Requests\StoreProviderRequest  $request  The validated request containing the provider request data.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns the created provider request.
     *                                       - Unauthorized: Returns a 403 response if the user lacks permission to create the request.
     *                                       - Error: Returns a 500 response if the creation fails.
     */
    public function store($request)
    {
        $user = Auth::user();

        if (! $user->hasAnyRole(['Admin', 'Pcm', 'Staff'])) {
            return $this->apiResponse('You do not have the right permissions.', 403);
        }

        $validData = $request->validated();
        $validData['user_id'] = $user->id;
        $providerRequest = ProviderRequest::create($validData + ['updated_at' => now()]);

        return $this->apiResponse('Added Successfully', 200, new ProviderRequestResource($providerRequest));
    }

    /**
     * Update an existing provider request.
     *
     * @param  \App\Http\Requests\UpdateProviderRequest  $request  The validated request containing the updated provider request data.
     * @param  string  $id  The UUID of the provider request to be updated.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns the updated provider request.
     *                                       - Unauthorized: Returns a 403 response if the user lacks permission to update the request.
     *                                       - Not Found: Returns a 404 response if the provider request does not exist.
     *                                       - Error: Returns a 500 response if the update fails.
     */

    public function update($request, $id)
    {
        $user = Auth::user();

        if (! $user->hasAnyRole(['Admin', 'Pcm', 'Staff'])) {
            return $this->apiResponse('You do not have the right permissions.', 403);
        }

        $oldProviderRequest = ProviderRequest::where('uuid', $id)->firstOrFail();
        $this->storeRequestHistory($oldProviderRequest, $user->id);
        $oldProviderRequest->update($request->validated() + ['edited' => true, 'updated_at' => now()]);

        return $this->apiResponse('Updated Successfully', 200, new ProviderRequestResource($oldProviderRequest));
    }

    /**
     * Store the history of an updated provider request.
     *
     * @param  \App\Models\v3\ProviderRequest  $oldRequest  The previous provider request data.
     * @param  int  $editorId  The ID of the user who edited the request.
     * @return void
     */
    private function storeRequestHistory($oldRequest, $editorId)
    {
        ProviderRequestHistory::create([
            'body' => $oldRequest->body,
            'user_id' => $oldRequest->user_id,
            'doctor_id' => $oldRequest->doctor_id,
            'provider_note_id' => $oldRequest->id,
            'edited_by' => $editorId,
        ]);
    }

    /**
     * Delete a provider request.
     *
     * This method handles the deletion of a provider request using its UUID.
     * It performs a soft delete on the provider request if found, and returns a
     * success message. If the provider request is not found, it returns an error message.
     *
     * @param  string  $id  The UUID of the provider request to be deleted.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns a JSON response with a status code 200 indicating that
     *                                       the request was successfully deleted.
     *                                       - Not Found: Returns a 404 response with a message 'Invalid ID' if the provider
     *                                       request with the given UUID does not exist.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     *                                                              - If the UUID does not correspond to a valid provider request, the method
     *                                                              will return a 404 error response with a descriptive message.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $query = ProviderRequest::where('uuid', $id);

        $this->filterByRole($query, $user);

        $providerRequest = $query->first();

        if (! $providerRequest) {
            return $this->apiResponse('Invalid ID', 404);
        }

        $providerRequest->delete();

        return $this->apiResponse('Deleted Successfully', 200);
    }

    /**
     * Retrieve users from a clinic and check for mentions.
     *
     * @param  string  $id  The UUID of the clinic.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns a list of users with role details and online status.
     *                                       - Unauthorized: Returns a 403 response if the user lacks permission to view the users.
     *                                       - Not Found: Returns a 404 response if the clinic with the given UUID does not exist.
     */
public function noteMention($uuid, UserClient $userClient, ClinicClient $clinicClient)
{
    $authUser = $userClient->authUser();
    if (!$authUser) {
        return $this->apiResponse("Unauthorized", 401);
    }

    $clinic = $clinicClient->getClinicByUuid($uuid);
    if (!$clinic) {
        return $this->apiResponse("Clinic not found", 404);
    }

    $mentionedUserId = request('mentionedUserId');
    $mentionedUsername = request('mentionedUsername');

    $staff = $userClient->getUsersByClinicId($clinic->id);

    if ($staff->isEmpty()) {
        return $this->apiResponse("No users found", 200, []);
    }

    if ($mentionedUserId) {
        $staff = $staff->where('id', $mentionedUserId);
    } elseif ($mentionedUsername) {
        $staff = $staff->where('username', $mentionedUsername);
    } else {
        $staff = $this->filterByRoleMicroservice($staff, $authUser);
    }

    if ($staff->isEmpty()) {
        return $this->apiResponse("No matching users", 200, []);
    }

    $roleResponse = $userClient->getUsersRoles($staff->pluck('id')->toArray());
    $rolesById = collect($roleResponse)->keyBy('user_id');

    $data = $staff->map(function ($user) use ($rolesById) {
        $role = $rolesById[$user->id]['roles'][0] ?? null;

        return [
            'uuid' => $user->uuid,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'last_seen' => $user->last_seen ?? null,
            'photo' => $user->photo ?? null,
            'role_name' => $role,
            'is_online' => $this->isUserOnline($user->last_seen ?? null),
        ];
    });

    return $this->apiResponse('success', 200, $data);
}

private function filterByRoleMicroservice($collection, $authUser)
{
    if (in_array("Doctor", $authUser->roles)) {
        return $collection->filter(fn ($u) => in_array("Pcm", $u->roles ?? []));
    }

    if (in_array("Pcm", $authUser->roles)) {
        return $collection->filter(fn ($u) => in_array("Doctor", $u->roles ?? []));
    }

    return $collection; 
}


    /**
     * Check if a user is online based on their last seen timestamp.
     *
     * @param  string|null  $lastSeen  The last seen timestamp of the user.
     * @return bool True if the user is online, false otherwise.
     */
    private function isUserOnline($lastSeen)
    {
        return !empty($lastSeen) && now()->diffInMinutes($lastSeen) <= 5;
    }

    /**
     * Retrieve the history of provider requests.
     *
     * Fetches a list of provider request history records associated with a specific
     * provider note, including patient details. The history is ordered by the
     * 'updated_at' timestamp in ascending order to show changes over time.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming request containing necessary parameters.
     * @param  int|string  $providerRequestID  The ID or UUID of the provider note to fetch its history.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns the list of provider request history records with associated patient data.
     *                                       - Unauthorized: Returns a 403 response if the user lacks the appropriate role.
     *                                       - Provider Request Not Found: Returns a 404 response if no history records are found for the specified provider note ID.
     *                                       - Error: Returns a 500 response if any unexpected exception occurs during the retrieval process.
     */
    public function getProviderRequestHistory($providerRequestID)
    {
        $providerRequests = ProviderRequestHistory::where('provider_note_id', $providerRequestID)
            ->orderBy('updated_at', 'asc')
            ->get();

        return $this->apiResponse('success', 200, ProviderRequestHistoryResource::collection($providerRequests));
    }
}