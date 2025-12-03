<?php

namespace App\Services\ProviderNote;

use App\Enums\RoleType;
use App\Helpers\Helpers;
use App\Http\Requests\ProviderNote\ProviderNote\StoreProviderNoteRequest;
use App\Http\Requests\ProviderNote\ProviderNote\UpdateProviderNoteRequest;
use App\Http\Resources\ProviderNote\ProviderNoteHistoryResource;
use App\Http\Resources\ProviderNote\ProviderNoteResource;
use App\Models\ProviderNote;
use App\Models\ProviderNoteHistory;
use App\Services\Notification\NotifyUserService;
use App\Http\Clients\UserClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProviderNoteService
{
    use \App\Traits\ApiResponseTrait;

    protected NotifyUserService $notifyUserService;
    protected UserClient $userClient;

    public function __construct(NotifyUserService $notifyUserService, UserClient $userClient)
    {
        $this->notifyUserService = $notifyUserService;
        $this->userClient = $userClient;
    }

    public function index(Request $request, $patient_id)
    {
        $authUser = $this->userClient->authUser();
        if (! $authUser) {
            return $this->ApiResponse('Unauthorized', 401);
        }

        $cacheKey = Helpers::generateCacheKey($request, 'provider_notes_' . $patient_id);
        $sort = $request->input('sort', 'desc');
        $sortBy = $request->input('sortBy', 'id');
        $perPage = $request->input('per_page', 10);
        $page = max(1, (int) $request->input('page', 1));

        $notes = Cache::tags(['provider_notes'])->remember($cacheKey, 3600, function () use (
            $request, $authUser, $patient_id, $sort, $sortBy, $perPage, $page
        ) {
            $notesQuery = ProviderNote::with(['comments'])
                ->where('patient_id', $patient_id);

            // Role-based filter
            if ($authUser->role === RoleType::Admin->value) {
                // no extra filter
            } elseif ($authUser->role === 'Pcm') {
                $doctorIds = $this->userClient->getDoctors([$authUser->id])->pluck('id')->toArray();
                $notesQuery->whereIn('doctor_id', $doctorIds);
            } elseif ($authUser->role === 'Doctor') {
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

        $data = ProviderNoteResource::collection($notes)->response()->getData(true);
        return $this->ApiResponse('success', 200, $data);
    }

    public function show($noteId)
    {
        $note = ProviderNote::findOrFail($noteId);
        return $this->ApiResponse('success', 200, new ProviderNoteResource($note));
    }

    public function store(StoreProviderNoteRequest $request)
    {
        $validData = $request->validated();
        $authUser = $this->userClient->authUser();
        if (! $authUser) {
            return $this->ApiResponse('Unauthorized', 401);
        }
        if (! in_array($authUser->role, ['Admin', 'Pcm', 'Doctor'])) {
            return $this->ApiResponse('Unauthorized to create a provider note.', 403);
        }

        $doctor = $this->userClient->getUserById($validData['doctor_id']);
        if (! $doctor || $doctor->role !== 'Doctor') {
            return $this->ApiResponse('The selected doctor ID is invalid.', 404);
        }

        try {
            $note = ProviderNote::create([
                'doctor_id' => $validData['doctor_id'],
                'patient_id' => $validData['patient_id'],
                'body' => $validData['body'],
                'user_id' => $authUser->id,
                'updated_at' => now(),
            ]);

            $patient = $this->userClient->getPatientById($validData['patient_id']);

            if ($authUser->role === 'Doctor') {
                $pcms = $this->userClient->getDoctors([$authUser->id]);
                foreach ($pcms as $pcm) {
                    $fullName = trim($authUser->name . ' ' . ($authUser->last_name ?? ''));
                    $this->notifyUserService->notifyUser(
                        $pcm,
                        $authUser->id,
                        'You have been mentioned in a note',
                        "$fullName added a New Provider Request",
                        'request',
                        $note->user_id,
                        $note->id,
                        $patient->uuid ?? null
                    );
                }
            }

            if ($authUser->role === 'Pcm') {
                $doctorIds = $this->userClient->getDoctors([$authUser->id])->pluck('id')->toArray();
                if (in_array($validData['doctor_id'], $doctorIds)) {
                    $fullName = trim($authUser->name . ' ' . ($authUser->last_name ?? ''));
                    $this->notifyUserService->notifyUser(
                        $doctor,
                        $authUser->id,
                        'You have been mentioned in a note',
                        "$fullName added a New Provider Request",
                        'request',
                        $note->user_id,
                        $note->id,
                        $patient->uuid ?? null
                    );
                }
            }

            $this->notifyMentionedUsers($validData['body'], $note);

            Cache::tags(['provider_notes'])->flush();
            return $this->ApiResponse('Added Successfully', 201, new ProviderNoteResource($note));

        } catch (\Exception $e) {
            return $this->ApiResponse('An error occurred while creating the note', 500, [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function update(UpdateProviderNoteRequest $request, $id)
    {
        $validData = $request->validated();
        $authUser = $this->userClient->authUser();
        if (! $authUser) {
            return $this->ApiResponse('Unauthorized', 401);
        }

        $oldNote = ProviderNote::findOrFail($id);

        ProviderNoteHistory::create([
            'body' => $oldNote->body,
            'user_id' => $oldNote->user_id,
            'doctor_id' => $oldNote->doctor_id,
            'provider_note_id' => $oldNote->id,
            'edited_by' => $authUser->id,
        ]);

        $oldNote->update([
            'body' => $validData['body'],
            'edited' => true,
            'updated_at' => now(),
        ]);

        $patient = $this->userClient->getPatientById($oldNote->patient_id);

        if ($authUser->role === 'Pcm' && $oldNote->doctor_id) {
            $doctor = $this->userClient->getUserById($oldNote->doctor_id);
            if ($doctor && $doctor->role === 'Doctor') {
                $this->notifyUserService->notifyUser(
                    $doctor,
                    $authUser->id,
                    'You have been mentioned in a note',
                    $authUser->name . ' has updated a provider note.',
                    'request',
                    $oldNote->user_id,
                    $oldNote->id,
                    $patient->uuid ?? null
                );
            }
        }

        $admins = $this->userClient->getUsersByRole(RoleType::Admin->value);
        foreach ($admins as $admin) {
            $this->notifyUserService->notifyUser(
                $admin,
                $authUser->id,
                'You have been mentioned in a note',
                $authUser->name . ' has updated a provider note.',
                'request',
                $oldNote->user_id,
                $oldNote->id,
                $patient->uuid ?? null
            );
        }

        $this->notifyMentionedUsers($validData['body'], $oldNote);

        return $this->ApiResponse('Updated Successfully', 200, new ProviderNoteResource($oldNote));
    }

    public function destroy($id)
    {
        $authUser = $this->userClient->authUser();
        if (! $authUser) {
            return $this->ApiResponse('Unauthorized', 401);
        }

        $note = ProviderNote::findOrFail($id);
        $note->delete();
        Cache::tags(['provider_notes'])->flush();

        return $this->ApiResponse('Deleted Successfully', 200);
    }

    protected function notifyMentionedUsers(string $body, ProviderNote $note): void
    {
        preg_match_all('/@([a-zA-Z0-9_]+)/', $body, $matches);
        $usernames = $matches[1] ?? [];
        if (empty($usernames)) {
            return;
        }

        $mentionedUsers = $this->userClient->getUsersByIds($usernames);
        $authUser = $this->userClient->authUser();
        $fullName = trim($authUser->name . ' ' . ($authUser->last_name ?? ''));
        $patient = $this->userClient->getPatientById($note->patient_id);

        foreach ($mentionedUsers as $user) {
            $this->notifyUserService->notifyUser(
                $user,
                $authUser->id,
                'You have been mentioned in a note',
                "$fullName mentioned you in a note.",
                'request',
                $note->user_id,
                $note->id,
                $patient->uuid ?? null
            );
        }
    }

    public function getProviderNoteHistory($providerNoteID)
    {
        $authUser = $this->userClient->authUser();
        if (! $authUser) {
            return $this->ApiResponse('Unauthorized', 401);
        }

        $notes = ProviderNoteHistory::where('provider_note_id', $providerNoteID)
            ->orderBy('updated_at', 'ASC')
            ->get();

        return $this->ApiResponse('success', 200, ProviderNoteHistoryResource::collection($notes));
    }
}
