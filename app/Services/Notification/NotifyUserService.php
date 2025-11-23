<?php

namespace App\Services\Notification;

use App\Notifications\MentionNotification;
use App\Traits\ApiResponseTrait;

/**
 * Class NotifyUserService
 *
 * Service class responsible for notifying users about certain events, such as mentions in comments.
 * It triggers both a notification and an event to notify the user.
 *
 * @package App\Services\Notification
 */

class NotifyUserService
{
    use ApiResponseTrait;

    /**
     * Send a notification and trigger an event for the user.
     *
     * This method notifies a user about an event (e.g., a mention in a comment) by sending
     * a notification and triggering an event for further processing or broadcasting.
     *
     * @param  \App\Models\User  $user  The user to be notified.
     * @param  int  $senderId  The ID of the user sending the notification.
     * @param  string  $title  The title of the notification.
     * @param  string  $message  The content or message of the notification.
     * @param  string  $type  The type of notification (e.g., 'note').
     * @param  int  $userId  The ID of the user associated with the notification (e.g., patient or staff).
     * @param  int  $noteId  The ID of the note associated with the notification.
     * @param  string|null  $uuid  The UUID of the user or patient (optional).
     *
     * @return void
     */
    public function notifyUser(
        $user,
        $senderId,
        $title,
        $message,
        $type,
        $userId,
        $noteId,
        $uuid = null
    ) {
        // Send Notification
        $user->notify(new MentionNotification(
            $senderId,
            $title,
            $message,
            $type,
            $userId,
            $noteId,
            $uuid
        ));

        // Trigger Event
        event(new MentionNotification(
            $user->id,
            $senderId,
            $title,
            $message,
            $type,
            $userId,
            $noteId
        ));
    }
}