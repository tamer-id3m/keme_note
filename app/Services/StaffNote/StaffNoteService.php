<?php

namespace App\Services\StaffNote;

use App\Events\Notification;
use App\Helpers\Helpers;
use App\Http\Resources\V3\StaffNote\StaffNoteResource;
use App\Models\User;
use App\Models\v3\StaffNote;
use App\Notifications\MentionNotification;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Class StaffNoteService
 *
 * Service layer for managing staffNotes.
 * This service provides methods related to staff note operations,
 * utilizing common response handling from the ApiResponseTrait.
 */
class StaffNoteService
{
    use ApiResponseTrait;

    /**
     * Retrieves a paginated list of staff notes for a specific patient.
     *
     * This method fetches staff notes related to a given patient (identified by the `$id` parameter),
     * applies sorting based on the provided `sortBy` and `sort` parameters (defaulting to `id` and `desc`),
     * and paginates the results based on the `perPage` parameter (defaulting to a value from a helper function).
     * The results are transformed using the `StaffNoteResource` before being returned in the API response.
     *
     * @param  \Illuminate\Http\Request  $request  The HTTP request instance containing the sorting and pagination parameters.
     * @param  int  $id  The patient ID for which to fetch the staff notes.
     * @return \Illuminate\Http\JsonResponse JSON response containing the paginated list of staff notes.
     */
    public function index($request, $id)
    {

        $sortBy = $request->input('sortBy', 'id');
        $sortOrder = $request->input('sort', 'desc');
        $perPage = $request->input('perPage', Helpers::getPagination());

        $query = StaffNote::query()->where('patient_id', $id);

        $sstaffNote = $query->orderBy($sortBy, $sortOrder)->paginate($perPage);

        $data = StaffNoteResource::collection($sstaffNote)->response()->getData(true);

        return $this->ApiResponse('success', 200, $data);
    }

    /**
     * Stores a new staff note.
     *
     * This method handles the creation of a new staff note. It first checks if the authenticated user has
     * the role 'Patient' and denies access if true. Then, it validates and prepares the data, including setting
     * a default value for the `edited` field. The staff note is created and a notification is sent to users
     * mentioned within the body of the note.
     *
     * The method uses a database transaction to ensure that if any error occurs while creating the note
     * or sending notifications, the changes are rolled back to maintain data integrity.
     *
     * @param  \Illuminate\Http\Request  $request  The HTTP request instance containing the validated data for the note.
     * @return \Illuminate\Http\JsonResponse JSON response with success or failure message.
     */
    public function store($request)
    {
        DB::beginTransaction();

        try {
            $user = Auth::user();

            if ($user->hasRole('Patient')) {
                return $this->ApiResponse('You don’t have permission.', 403);
            }

            $data = $request->validated();
            $data['edited'] = false;

            $staffNote = StaffNote::create($data + ['updated_at' => now()]);
            if (! $staffNote) {
                DB::rollBack();

                return $this->ApiResponse('Failed to create note', 500);
            }
            $body = $data['body'];
            preg_match_all('/@([a-zA-Z0-9_]+)/', $body, $matches);
            $usernames = $matches[1];
            $users = User::whereIn('username', $usernames)->get();

            $fullName = $user->name . ' ' . ($user->last_name ?? '');
            $userId = $user->id;

            foreach ($users as $user) {
                $user->notify(new MentionNotification($userId, 'You were mentioned!', $fullName . ' mentioned you in a new note.', 'note', $staffNote->patient_id, $staffNote->id, $staffNote->uuid ? $staffNote->uuid : null));
                event(
                    new Notification(
                        $user->id,
                        $userId,
                        'You were mentioned!',
                        $fullName . ' mentioned you in a new note',
                        'note',
                        $staffNote->patient_id,
                        $staffNote->id
                    )
                );
            }
            DB::commit();

            return $this->ApiResponse(__('Added Successfully'), 200, new StaffNoteResource($staffNote));
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->ApiResponse('An error occurred', 500);
        }
    }

    /**
     * Retrieves a specific staff note by its UUID for the authenticated user.
     *
     * This method checks if the authenticated user has the 'Patient' role. If the user does not have the 'Patient' role,
     * it attempts to retrieve a staff note by its UUID and ensures that the note belongs to the authenticated user by checking
     * the `user_id`. If the note is found, it returns the note data wrapped in a `StaffNoteResource`.
     * If the note is not found, a 'not found' response is returned.
     * If the user has the 'Patient' role, an error message is returned as they do not have permission to view staff notes.
     *
     * @param  string  $uuid  The UUID of the staff note to retrieve.
     * @return \Illuminate\Http\JsonResponse JSON response containing the staff note data or an error message.
     */
    public function show($uuid)
    {
        $user = Auth::user();
        if (! $user->hasRole('Patient')) {
            $staffNote = StaffNote::where('uuid', $uuid)->where('user_id', $user->id)->first();
            if (! $staffNote) {
                return $this->ApiResponse(__('main.IdNotFound'), 400, []);
            }

            $data = new StaffNoteResource($staffNote);

            return $this->ApiResponse('succes', 200, $data);
        } else {
            return $this->ApiResponse('you don`t have permission ', 400);
        }
    }

    /**
     * Updates a staff note by its UUID for the authenticated user, wrapped in a database transaction.
     *
     * This method begins a database transaction to ensure that all updates and related operations are handled atomically.
     * It checks if the authenticated user is not a 'Patient'.
     * If the user has the 'Admin' role, it retrieves the staff note by its UUID without restriction.
     * If the user has any other role, it ensures that the staff note belongs to the authenticated user by checking the `user_id`.
     * Once the note is found, it updates the note's content and sends notifications to any users mentioned within the note's body.
     * If any error occurs during the transaction, the changes are rolled back.
     * If the note is not found, a 'not found' response is returned.
     * If the user has the 'Patient' role, an error message is returned as they do not have permission to update the note.
     *
     * @param  \Illuminate\Http\Request  $request  The request containing the updated data for the staff note.
     * @param  string  $uuid  The UUID of the staff note to update.
     * @return \Illuminate\Http\JsonResponse JSON response with the updated staff note data or an error message.
     */
    public function update($request, $uuid)
    {
        DB::beginTransaction();

        try {
            $user = Auth::user();
            if (! $user->hasRole('Patient')) {
                if ($user->hasRole('Admin')) {
                    $staffNote = StaffNote::where('uuid', $uuid)->first();
                } else {
                    $staffNote = StaffNote::where('uuid', $uuid)->where('user_id', $user->id)->first();
                }
                if (! $staffNote) {
                    DB::rollBack();

                    return $this->ApiResponse(__('main.IdNotFound'), 400, []);
                }
                $data = [
                    'body' => $request->body ?? $staffNote->body,

                    'edited' => true,
                ];

                $staffNote->update($data);

                $body = $data['body'];
                preg_match_all('/@([a-zA-Z0-9_]+)/', $body, $matches);
                $usernames = $matches[1];
                $users = User::whereIn('username', $usernames)->get();
                $fullName = $user->name . ' ' . ($user->last_name ?? '');
                $userId = $user->id;

                foreach ($users as $user) {
                    $user->notify(new MentionNotification($userId, 'You were mentioned!', $fullName . ' mentioned you in a new note.', 'note', $staffNote->patient_id, $staffNote->id, $staffNote->uuid ? $staffNote->uuid : null));
                    event(
                        new Notification(
                            $user->id,
                            $userId,
                            'You were mentioned!',
                            $fullName . ' mentioned you in a new note',
                            'note',
                            $staffNote->patient_id,
                            $staffNote->id
                        )
                    );
                }
                DB::commit();
                $data = new StaffNoteResource($staffNote);

                return $this->ApiResponse(__('Updated Successfully'), 200, $data);
            } else {
                DB::rollBack();

                return $this->ApiResponse('you don`t have permission ', 400);
            }
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->ApiResponse('An error occurred, please try again later', 500);
        }
    }

    /**
     * Deletes a staff note by its UUID, ensuring the authenticated user has appropriate permissions.
     *
     * This method checks if the authenticated user has the necessary role to delete the staff note.
     * It allows 'Admin' users to delete any staff note, while other users can only delete their own notes.
     * The method ensures that the staff note exists by checking the UUID and associated user ID.
     * If the note exists, it is deleted, and a success response is returned. If the note is not found,
     * a 'not found' response is returned. If the user has the 'Patient' role, an error message is returned,
     * indicating insufficient permissions.
     *
     * @param  string  $uuid  The UUID of the staff note to delete.
     * @return \Illuminate\Http\JsonResponse JSON response indicating the result of the deletion operation (success or error).
     */
    public function destroy($uuid)
    {
        $user = Auth::user();
        if (! $user->hasRole('Patient')) {
            if ($user->hasRole('Admin')) {
                $staffNote = StaffNote::where('uuid', $uuid)->first();
            } else {
                $staffNote = StaffNote::where('uuid', $uuid)->where('user_id', $user->id)->first();
            }
            if (! $staffNote) {
                return $this->ApiResponse(__('main.IdNotFound'), 400, []);
            }
            $staffNote->delete();

            return $this->ApiResponse(__('Deleted Sccessfully'), 200);
        } else {
            return $this->ApiResponse('you don`t have permission ', 400);
        }
    }
}