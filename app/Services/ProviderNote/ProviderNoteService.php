<?php

namespace App\Services\V4\ProviderNote;

use App\Enums\RoleType;
use App\Helpers\Helpers;
use App\Http\Requests\ProviderNote\ProviderNote\StoreProviderNoteRequest;
use App\Http\Requests\ProviderNote\ProviderNote\UpdateProviderNoteRequest;
use App\Http\Resources\ProviderNote\ProviderNoteHistoryResource;
use App\Http\Resources\ProviderNote\ProviderNoteResource;
use App\Models\ProviderNoteHistory;
use App\Models\User;
use App\Models\v3\Patient;
use App\Models\ProviderNote;
use App\Services\Notification\NotifyUserService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Elastic\ScoutDriverPlus\Builders\BoolQueryBuilder;
use Elastic\ScoutDriverPlus\Support\Query;


/**
 * Class ProviderNoteService
 *
 * This service manages the creation, retrieval, updating, and deletion of provider notes.
 * It includes role-based access control to ensure that only authorized users can manage notes.
 * It also handles note mentions and retrieves associated data such as comments and patient information.
 */
class ProviderNoteService
{
    use ApiResponseTrait;

    protected $notifyUserService;

    public function __construct(NotifyUserService $notifyUserService)
    {

        $this->notifyUserService = $notifyUserService;
    }

    /**
     * Retrieve a list of provider notes for a specific patient.
     *
     * This method supports filtering and sorting based on user role and other parameters.
     *
     * @param  \Illuminate\Http\Request  $request  The HTTP request instance.
     * @param  int  $patient_id  The ID of the patient for which notes are being fetched.
     * @return \Illuminate\Http\Response The response containing the notes data.
     */
    public function index(Request $request, $patient_id)
    {
        $authUser = Auth::user();
        $cacheKey = Helpers::generateCacheKey($request, 'provider_notes_' . $patient_id);

        $sort = $request->input('sort', 'desc');
        $sortBy = $request->input('sortBy', 'id');
        $perPage = $request->input('per_page', 10);
        $page = max(1, (int) $request->input('page', 1));

        $notes = Cache::tags(['provider_notes'])->remember($cacheKey, 3600, function () use ($request, $authUser, $patient_id, $sort, $sortBy, $perPage, $page) {
            $query = new BoolQueryBuilder();
            $query->must(Query::term()->field('patient_id')->value($patient_id));

            if ($search = strtolower($request->input('search', ''))) {
                $query->should(Query::wildcard()->field('body')->value("*$search*"));
                $query->minimumShouldMatch(1);
            }

            $result = ProviderNote::searchQuery($query)
                ->size(Helpers::getElasticQuerySize(ProviderNote::class, $query))
                ->execute()
                ->raw();

            $ids = collect($result['hits']['hits'])->pluck('_id')->all();

            $notesQuery = ProviderNote::with(['patient', 'user', 'comments'])->whereIn('id', $ids);

            if ($authUser->hasRole('Admin') || $authUser->roles->first()->type == RoleType::Admin) {
            } elseif ($authUser->hasRole('Pcm')) {
                $doctorIds = $authUser->pcmDoctors->pluck('id')->toArray();
                $notesQuery->whereIn('doctor_id', $doctorIds);
            } elseif ($authUser->hasRole('Doctor')) {
                $notesQuery->where('doctor_id', $authUser->id);
            } else {
                abort(403, 'Unauthorized');
            }

            if ($request->filled('id')) {
                $notesQuery->where('id', $request->id);
            }

            return $notesQuery->orderBy($sortBy, $sort)
                ->paginate($perPage, ['*'], 'page', $page);
        });

        return $this->ApiResponse('success', 200, ProviderNoteResource::collection($notes)->response()->getData(true));
    }



    /**
     * Retrieve a specific provider note by its ID.
     *
     * This method fetches the note along with its associated patient, user, and comments.
     *
     * @param  int  $note  The ID of the note to retrieve.
     * @return \Illuminate\Http\Response The response containing the note data.
     */
    public function show($note)
    {
        $note = ProviderNote::with(['patient', 'user', 'comments'])->findOrFail($note);

        return $this->ApiResponse('success', 200, new ProviderNoteResource($note));
    }

    /**
     * Create a new provider note.
     *
     * This method validates the input data and creates a new provider note for the patient.
     *
     * @param  \App\Http\Requests\ProviderNpte\ProviderNote\StoreProviderNoteRequest  $request  The validated request data.
     * @return \Illuminate\Http\Response The response containing the created note data.
     */
    public function store(StoreProviderNoteRequest $request)
    {
        $validData = $request->validated();
        $user = Auth::user();
        if (!$user->hasAnyRole(['Admin', 'Pcm', 'Doctor'])) {
            return $this->ApiResponse('Unauthorized to create a provider note.', 403);
        }
        if (! User::role('Doctor')->where('id', $validData["doctor_id"])->exists()) {
            return $this->ApiResponse('The selected doctor ID is invalid.', 404);
        }
        try {
            // Create the new provider note
            $note = ProviderNote::create([
                'doctor_id' => $validData['doctor_id'],
                'patient_id' => $validData['patient_id'],
                'body' => $validData['body'],
                'user_id' => Auth::id(),
                'updated_at' => now(),
            ]);

            if (! $note) {
                return $this->ApiResponse('Failed to create note', 500);
            }
            if ($user->hasRole('Doctor')) {
                $doctor_id = $user->id;
                $fullName = $user->name . ' ' . ($user->last_name ?? '');
                $pcms = User::role('Pcm')->whereRelation("pcmDoctors", "pcm_doctor.doctor_id", $doctor_id)->get();
                $patient = Patient::where('id', $validData['patient_id'])->first();
                foreach ($pcms as $pcm) {
                    $this->notifyUserService->notifyUser(
                        $pcm,
                        $user->id,
                        'You have been mentioned in a note',
                        "$fullName added a New Provider Request",
                        'request',
                        $note->user_id,
                        $note->id,
                        $patient->uuid
                    );
                }
            }
            if ($user->hasRole('Pcm')) {
                $doctorIds = $user->pcmDoctors->pluck('id')->toArray();
                if (in_array($validData['doctor_id'], $doctorIds)) {
                    $fullName = $user->name . ' ' . ($user->last_name ?? '');
                    $doctor = User::role('Doctor')->where('id', $validData["doctor_id"])->first();
                    $patient = Patient::where('id', $validData['patient_id'])->first();
                    $this->notifyUserService->notifyUser(
                        $doctor,
                        $user->id,
                        'You have been mentioned in a note',
                        "$fullName added a New Provider Request",
                        'request',
                        $note->user_id,
                        $note->id,
                        $patient->uuid
                    );
                }
            }
            $this->notifyMentionedUsers($validData['body'], $note);
            Cache::tags(['provider_notes'])->flush();
            return $this->ApiResponse('Added Successfully', 201, new ProviderNoteResource($note));
        } catch (\Exception $e) {
            return $this->ApiResponse('An error occurred while creating the note', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Update an existing provider note.
     *
     * This method validates the input data and updates the specified note's body.
     *
     * @param  \App\Http\Requests\ProviderNote\ProviderNote\UpdateProviderNoteRequest  $request  The validated request data.
     * @param  int  $id  The ID of the note to update.
     * @return \Illuminate\Http\Response The response containing the updated note data.
     */

    public function update(UpdateProviderNoteRequest $request, $id)
    {
        $validData = $request->validated();
        $oldNote = ProviderNote::findOrFail($id);

        $commentHistory = ProviderNoteHistory::create([
            'body' => $oldNote->body,
            'user_id' => $oldNote->user_id,
            'doctor_id' => $oldNote->doctor_id,
            'provider_note_id' => $oldNote->id,
            'edited_by' => Auth::id(),
        ]);

        $oldNote->update([
            'body' => $validData['body'],
            'edited' => true,
            'updated_at' => now(),
        ]);

        $authUser = Auth::user();

        $patient = User::role('Patient')->where('id', $oldNote->patient_id)->first();

        if ($authUser->hasRole('Pcm') && $oldNote->doctor_id) {
            $doctor = User::role('Doctor')->find($oldNote->doctor_id);
            if ($doctor) {
                $this->notifyUserService->notifyUser(
                    $doctor,
                    $authUser->id,
                    'You have been mentioned in a note',
                    $authUser->name . ' has updated a provider note.',
                    'request',
                    $oldNote->user_id,
                    $oldNote->id,
                    $patient->uuid
                );
            }
        }
        $admins = User::role('Admin')->get();
        foreach ($admins as $admin) {
            $this->notifyUserService->notifyUser(
                $admin,
                $authUser->id,
                'You have been mentioned in a note',
                $authUser->name . ' has updated a provider note.',
                'request',
                $oldNote->user_id,
                $oldNote->id,
                $patient->uuid
            );
        }
        $this->notifyMentionedUsers($validData['body'], $oldNote);
        return $this->ApiResponse('Updated Successfully', 200, new ProviderNoteResource($oldNote));
    }


    /**
     * Create a history record for a provider note.
     *
     * This method stores the old version of the note before it gets updated.
     *
     * @param  \App\Models\ProviderNote  $oldNote  The old note being updated.
     * @return void
     */
    protected function createNoteHistory(ProviderNote $oldNote)
    {
        ProviderNoteHistory::create([
            'body' => $oldNote->body,
            'user_id' => $oldNote->user_id,
            'doctor_id' => $oldNote->doctor_id,
            'provider_note_id' => $oldNote->id,
            'edited_by' => Auth::id(),
        ]);
    }

    /**
     * Delete a specific provider note.
     *
     * This method deletes the note from the database.
     *
     * @param  int  $id  The ID of the note to delete.
     * @return \Illuminate\Http\Response The response confirming the deletion.
     */
    public function destroy($id)
    {
        $note = ProviderNote::findOrfail($id);
        $note->delete();
        Cache::tags(['provider_notes'])->flush();

        return $this->ApiResponse('Deleted Successfully', 200);
    }

    /**
     * Filter notes based on user role.
     *
     * This method adjusts the notes query based on the role of the authenticated user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $notes  The notes query builder.
     * @param  \App\Models\User  $user  The authenticated user.
     * @return void
     */
    // protected function filterNotesByRole($notes, $user)
    // {
    //     if ($user->hasRole('Admin')) {
    //     } elseif ($user->hasRole('Pcm') || $user->hasRole('Doctor')) {
    //         $notes->where('clinic_id', $user->clinic_id);
    //     } else {
    //         throw new \Exception('Unauthorized');
    //     }
    // }

    /**
     * Handle note mentions for a specific user.
     *
     * This method filters users based on their role and retrieves the users mentioned in a note.
     *
     * @param  int  $id  The clinic ID associated with the note.
     * @return \Illuminate\Http\Response The response containing the filtered user data.
     */

    public function noteMention($id)
    {
        $authUser = Auth::user();

        if (! $authUser->hasAnyRole(['Doctor', 'Pcm', 'Admin'])) {
            return $this->ApiResponse('Sorry, You don\'t have permission', 403);
        }

        $query = $this->getMentionedUsersQuery($authUser, $id);

        $mentionedUserId = request()->input('mentionedUserId');
        $mentionedUsername = request()->input('mentionedUsername');

        if (! empty($mentionedUserId)) {
            $query->where('id', $mentionedUserId);
        }
        if (! empty($mentionedUsername)) {
            $query->where('username', $mentionedUsername);
        }

        $mentionedUsers = $query->get();

        $users = $query->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'last_seen' => $user->last_seen,
                'photo' => $user->photo,
                'role_name' => $user->roles->first()->name ?? null,
                'is_online' => $this->isUserOnline($user->last_seen),
            ];
        });

        return $this->ApiResponse('Success', 200, $users);
    }

    /**
     * Get the query for retrieving mentioned users based on the authenticated user's role and clinic.
     *
     * @param  \App\Models\User  $authUser  The authenticated user who is making the request.
     * @param  int  $clinicId  The ID of the clinic to filter users by.
     * @return \Illuminate\Database\Eloquent\Builder
     *
     * @throws \Exception  If no associated doctor is found for the authenticated PCM user.
     */



    private function getMentionedUsersQuery($authUser, $clinicId)
    {
        if ($authUser->hasRole('Doctor')) {
            return User::role(['Pcm', 'Doctor', 'Admin'])
                ->where('clinic_id', $clinicId)
                ->where(function ($query) use ($authUser) {
                    $query->where('doctor_id', $authUser->id)
                        ->orWhereRelation('roles', 'name', 'Admin')
                        ->whereNull('clinic_id')
                        ->whereNull('doctor_id');
                })
                ->select('id', 'uuid', 'name', 'username', 'email', 'last_seen', 'photo');
        }

        if ($authUser->hasRole('Pcm')) {
            $doctorIds = $authUser->pcmDoctors->pluck('id')->toArray();
            $count = User::whereRelation('roles', 'name', 'Doctor')
                ->where('clinic_id', $clinicId)
                ->whereIn('id', $doctorIds)
                ->count();

            if ($count==0) {
                throw new \Exception('No associated doctor found');
            }

            return User::role(['Pcm', 'Doctor', 'Admin'])
                ->where('clinic_id', $clinicId)
                ->where(function ($query) use ($doctorIds) {
                    $query->whereIn('doctor_id', $doctorIds)
                        ->orWhereIn('id', $doctorIds);
                })
                ->orWhereRelation('roles', 'name', 'Admin', function ($query) {
                    $query->whereNull('clinic_id')->whereNull('doctor_id');
                })
                ->select('id', 'uuid', 'name', 'username', 'email', 'last_seen', 'photo');
        }

        return User::role(['Doctor', 'Pcm', 'Admin'])
            ->where('clinic_id', $clinicId)
            ->select('id', 'uuid', 'name', 'username', 'email', 'last_seen', 'photo');
    }




    /**
     * Attach additional user data to the users.
     *
     * This method adds additional fields like role name and online status to the user data.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $users  The users collection.
     * @return void
     */
    private function attachAdditionalUserData($users)
    {
        foreach ($users as $user) {
            $user->role_name = $user->roles->first()->name ?? null;
            $user->is_online = $this->isUserOnline($user->last_seen);
        }
    }

    /**
     * Check if a user is online.
     *
     * This method checks if a user was last seen within the last 5 minutes.
     *
     * @param  string  $lastSeen  The timestamp of the last time the user was seen.
     * @return bool Whether the user is online or not.
     */
    private function isUserOnline($lastSeen)
    {
        return now()->diffInMinutes($lastSeen) <= 5;
    }

    /**
     * Retrieve provider note history.
     *
     * Fetches historical records of provider notes based on the specified provider note ID.
     * The history includes details about the user who made the changes and the timestamps.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming request containing optional filters or parameters.
     * @param  int  $providerNoteID  The ID of the provider note to fetch its history.
     * @return \Illuminate\Http\JsonResponse
     *                                       - Success: Returns a list of provider note history records with user details.
     *                                       - Unauthorized: Returns a 403 response if the user lacks the necessary permissions.
     *                                       - Not Found: Returns a 404 response if no history is found for the given provider note ID.
     *                                       - Error: Returns a 500 response for any unexpected errors during processing.
     */
    public function getProviderNoteHistory($providerNoteID)
    {
        $notes = ProviderNoteHistory::with('user')
            ->where('provider_note_id', $providerNoteID)
            ->orderBy('updated_at', 'ASC')
            ->get();

        return $this->ApiResponse('success', 200, ProviderNoteHistoryResource::collection($notes));
    }

    /**
     * Validate if the mentioned doctor and patient belong to the same clinic.
     *
     * @param  \App\Models\User  $authUser  The authenticated user who is making the request.
     * @param  int  $mentionedDoctorId  The ID of the doctor being mentioned.
     * @param  int  $patientId  The ID of the patient.
     * @return bool  Returns true if the mentioned doctor and patient belong to the same clinic, false otherwise.
     */
    private function validateUserInSameClinic($authUser, $mentionedDoctorId, $patientId)
    {
        $mentionedDoctor = User::find($mentionedDoctorId);
        $patient = Patient::find($patientId);

        return $mentionedDoctor && $patient && $mentionedDoctor->clinic_id === $patient->clinic_id;
    }

    /**
     * Notify the mentioned users in a note.
     *
     * This method finds all mentioned users in the provided note body, checks if they exist in the system,
     * and sends notifications to those users. It also handles additional logic for notifying admins differently.
     *
     * @param  string  $body  The body of the note, which may contain mentions of users.
     * @param  \App\Models\Note  $note  The note object that the mentions are associated with.
     * @return void
     */
    public function notifyMentionedUsers($body, $note)
    {
        // Find mentioned usernames in the body
        preg_match_all('/@([a-zA-Z0-9_]+)/', $body, $matches);
        $usernames = $matches[1];

        // Find users by their usernames
        $users = User::whereIn('username', $usernames)->get();

        $authUser = Auth::user();
        $fullName = $authUser->name . ' ' . ($authUser->last_name ?? '');
        $userId = $authUser->id;
        $patient = Patient::where('id', $note->patient_id)->first();
        $patientUuid = $patient->uuid ?? null;

        foreach ($users as $user) {
            $senderId = $authUser->id;
            $title = 'You have been mentioned in a note';
            $message = $fullName . ' mentioned you in a note.';
            $type = 'request';
            $userId = $note->user_id;
            $noteId = $note->id;


            $this->notifyUserService->notifyUser(
                $user,
                $senderId,
                $title,
                $message,
                $type,
                $userId,
                $noteId,
                $patientUuid
            );
            if ($user->hasRole('Admin')) {
                $this->notifyUserService->notifyUser(
                    $user,
                    $authUser->id,
                    'You have been mentioned in a note as Admin',
                    $fullName . ' mentioned you as an Admin in a note.',
                    'mention_admin',
                    $userId,
                    $noteId,
                    $patientUuid
                );
            }
        }
    }
}