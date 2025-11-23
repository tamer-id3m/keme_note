<?php

namespace App\Services\InternalNote;

use App\Models\User;
use App\Models\v3\Patient;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use App\Services\Notification\NotifyUserService;

class NotifyInternalNoteService
{
    protected $notifyUserService;

    public function __construct(NotifyUserService $notifyUserService)
    {
        $this->notifyUserService = $notifyUserService;
    }

    use ApiResponseTrait;

    /**
     * Notify users mentioned in the note body.
     *
     * This method scans the body of the note for mentions (usernames prefixed with '@'),
     * finds the corresponding users, and sends them a notification about being mentioned in the note.
     * It also sends notifications to users with specific permissions (excluding admins)
     * about the note's creation or update.
     *
     * @param  string  $body  The body of the note which may contain mentioned usernames.
     * @param  \App\Models\InternalNote  $note  The internal note that the users are being notified about.
     *
     * @return void
     */
    public function notifyMentionedUsers($body, $note)
    {
        $patient = Patient::where('id', $note->patient_id)->first();

        // Find mentioned usernames in the body
        preg_match_all('/@([a-zA-Z0-9_]+)/', $body, $matches);
        $usernames = $matches[1];

        // Find users by their usernames
        $users = User::whereIn('username', $usernames)->get();

        $usersWithPermission = User::permission('internalnote-create')
        ->whereDoesntHave('roles', function ($query) {
            $query->where('name', 'Admin');
        })
        ->where('id', '!=', Auth::id())
        ->where(function ($query) use ($patient) {
            $query
                ->WhereHas('staffDoctors', function ($q) use ($patient) {
                    $q->where('staff_doctors.doctor_id', $patient->doctor_id);
                })
                ->orWhereHas('pcmDoctors', function ($q) use ($patient) {
                    $q->where('pcm_doctor.doctor_id', $patient->doctor_id);
                })
                ->orWhere('doctor_id',$patient->doctor_id);

        })
        ->get();

        $authUser = Auth::user();
        $fullName = $authUser->name . ' ' . ($authUser->last_name ?? '');
        $patientUuid = $patient->uuid ?? null;

        foreach ($users as $user) {
            $senderId = $authUser->id;
            $title = 'You were mentioned!';
            $message = $fullName . ' mentioned you in a new note.';
            $type = 'note';
            $patientId = $note->patient_id;
            $noteId = $note->id;


            $this->notifyUserService->notifyUser(
                $user,
                $senderId,
                $title,
                $message,
                $type,
                $patientId,
                $noteId,
                $patientUuid
            );
        }
        foreach ($usersWithPermission as $user) {
            $senderId = $authUser->id;
            $title = 'Someone mentioned in Staff note!';
            $message = $fullName . ' mentioned other in the note';
            $type = 'note';
            $patientId = $note->patient_id;
            $noteId = $note->id;
            $uuid = $note->uuid ? $note->uuid : null;

            $this->notifyUserService->notifyUser(
                $user,
                $senderId,
                $title,
                $message,
                $type,
                $patientId,
                $noteId,
                $patientUuid
            );
        }
    }
    /**
     * Notify users with a specific permission about a note update or creation.
     *
     * This method sends notifications to users who have a specific permission and are not admins.
     * The notifications are related to a note, such as being informed of a new note or an update to an existing note.
     *
     * @param  string  $permission  The permission that the users must have in order to receive the notification.
     * @param  string  $title  The title of the notification to be sent.
     * @param  string  $message  The message body of the notification.
     * @param  \App\Models\InternalNote  $note  The internal note that the users are being notified about.
     *
     * @return void
     */
    public function notifyUsersWithPermission($permission, $title, $message, $note)
    {
        $patient = Patient::where('id', $note->patient_id)->first();
        $usersWithPermission = User::permission($permission)
         ->whereDoesntHave('roles', function ($query) {
            $query->where('name', 'Admin');
        })
        ->where('id', '!=', Auth::id())
        ->where(function ($query) use ($patient) {
            $query
                  ->WhereHas('staffDoctors', function ($q) use ($patient) {
                    $q->where('staff_doctors.doctor_id', $patient->doctor_id);
                })
                ->orWhereHas('pcmDoctors', function ($q) use ($patient) {
                    $q->where('pcm_doctor.doctor_id', $patient->doctor_id);
                })
                ->orWhere('doctor_id',$patient->doctor_id);
        })
        ->get();

        $authUser = Auth::user();

        $patientUuid = $patient->uuid ?? null;

        foreach ($usersWithPermission as $user) {
            $this->notifyUserService->notifyUser(
                $user,
                $authUser->id,
                $title,
                $message,
                'note',
                $note->patient_id,
                $note->id,
                $patientUuid
            );
        }
    }
}