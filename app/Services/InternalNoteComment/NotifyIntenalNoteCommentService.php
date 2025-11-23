<?php

namespace App\Services\InternalNoteComment;

use App\Models\User;
use App\Models\NoteComment;
use App\Notifications\MentionNotification;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class NotifyIntenalNoteCommentService
{
    use ApiResponseTrait;

    /**
     * Handle mentions in a comment body and notify mentioned users.
     *
     * @param  string  $body  The comment body containing mentions.
     * @param  \App\Models\NoteComment  $comment  The comment object for which mentions are handled.
     * @return void
     */
    public function handleMentions(string $body, NoteComment $comment, $patientUuid)
    {
        preg_match_all('/@([a-zA-Z0-9_]+)/', $body, $matches);
        $usernames = $matches[1] ?? [];

        if (empty($usernames)) {
            return;
        }

        $users = User::whereIn('username', array_unique($usernames))->get();
        $authUser = Auth::user();
        $fullName = "{$authUser->name} {$authUser->last_name}";


        foreach ($users as $user) {
            $user->notify(new MentionNotification($authUser->id, 'You were mentioned!', "$fullName mentioned you in a comment.", 'note', $comment->patient_id, $comment->internal_note_id, $patientUuid));
            event(new Notification($user->id, $authUser->id, 'You were mentioned!', "$fullName mentioned you in a comment.", 'note', $comment->patient_id, $comment->internal_note_id));
        }
    }
}