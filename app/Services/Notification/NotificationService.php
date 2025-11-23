<?php

namespace App\Services\V4\Notification;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\v3\Notification;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * Class NotificationService
 *
 * Handles business logic for managing notifications, including fetching, updating,
 * and deleting notifications for authenticated users.
 * Provides a consistent API response for controller actions.
 */
class NotificationService
{
    use ApiResponseTrait;

    /**
     * Retrieve notifications for the authenticated user.
     *
     * Retrieves all notifications or paginated notifications based on the 'perPage' parameter.
     *
     * @param  int|null  $perPage  Optional number of items per page. If null, retrieves all notifications.
     * @return \Illuminate\Http\JsonResponse Paginated notifications or all notifications in API response format.
     */
    public function getNotifications(Request $request)
    {
        $userId = Auth::id();
        $notificationQuery = Notification::where('notifiable_id',  $userId);

        if ($request->has('date')) {
            try {
                $convertedDate = Carbon::createFromFormat('m-d-Y', $request->input('date'))->format('Y-m-d');

                $notificationQuery->whereDate('created_at', '<', $convertedDate);
            } catch (\Exception $e) {
                return $this->ApiResponse('Invalid date format', 400);
            }
        }

        if ($request->has('per_page')) {
            $perPage = $request->input('per_page');
            $notifications = $notificationQuery->orderBy('created_at', 'desc')->paginate($perPage);
        } else {
            $notifications = $notificationQuery->orderBy('created_at', 'desc')->get();
        }

        return $this->ApiResponse('success', 200, $notifications);
    }

    /**
     * Retrieve the latest 10 notifications for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse Latest 10 notifications in API response format.
     */
    public function getUserLatestNotifications($request)
    {
        $user = Auth::user();
        $perPage    =  $request->input('per_page', 10);
        $page       = $request->input('page', 1);
        $notifications = Notification::where('notifiable_id', $user->id)
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);
        $unreadNotifications = Notification::where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();

        $data = ["notifications" => $notifications
        , "unreadNotifications" => $unreadNotifications];
        if ($user->hasRole("Parent")) {
            $data["minors_uuids"] = $user->minors->pluck('uuid')->toArray();
        }
        return $this->ApiResponse('success', 200, $data);
    }

    /**
     * Mark a specific notification as read.
     *
     * Sets the 'isSeen' field in the notification's data to true.
     *
     * @param  int  $id  The ID of the notification to mark as read.
     * @return \Illuminate\Http\JsonResponse API response indicating success or notification not found.
     */
    public function markNotificationAsRead($id)
    {
        $notification = Notification::find($id);
        if (! $notification) {
            return $this->ApiResponse('Notification not found', 404);
        }

        $data = json_decode($notification->data, true);
        if (is_array($data)) {
            $data['isSeen'] = true;
            $notification->data = json_encode($data);
            $notification->read_at = Carbon::now();
            $notification->save();
        }

        return $this->ApiResponse('success', 200, $notification);
    }

    public function markAllNotificationsAsRead()
    {
        $user = Auth::user();
        $notifications = $user->notifications;

        if ($notifications->isEmpty()) {
            return $this->ApiResponse('No notifications found', 404);
        }

        foreach ($notifications as $notification) {
            $data = is_string($notification->data)
                ? json_decode($notification->data, true)
                : $notification->data;

            if (is_array($data)) {
                $data['isSeen'] = true;
                $notification->data = $data;
                $notification->read_at = now();
                $notification->save();
            }
        }

        return $this->ApiResponse('All notifications marked as read successfully', 200);
    }

    /**
     * Mark a specific notification as unread.
     *
     * Sets the 'isSeen' field in the notification's data to false.
     *
     * @param  int  $id  The ID of the notification to mark as unread.
     * @return \Illuminate\Http\JsonResponse API response indicating success or notification not found.
     */
    public function markNotificationAsUnread($id)
    {
        $notification = Notification::find($id);
        if (! $notification) {
            return $this->ApiResponse('Notification not found', 404);
        }

        $data = json_decode($notification->data, true);
        if (is_array($data)) {
            $data['isSeen'] = false;
            $notification->data = json_encode($data);
            $notification->read_at =null;
            $notification->save();
        }

        return $this->ApiResponse('success', 200, $notification);
    }

    /**
     * Mark all notifications of the authenticated user as unread.
     *
     * Loops through all notifications belonging to the logged-in user
     * and updates each one by:
     * - Setting the 'isSeen' field in the notification's data to false.
     * - Resetting the 'read_at' field to null.
     *
     * @return \Illuminate\Http\JsonResponse  API response indicating success or that no notifications were found.
     */
    public function markAllNotificationsAsUnread()
    {
        $user = Auth::user();
        $notifications = $user->notifications;

        if ($notifications->isEmpty()) {
            return $this->ApiResponse('No notifications found', 404);
        }

        foreach ($notifications as $notification) {
            $data = is_string($notification->data)
                ? json_decode($notification->data, true)
                : $notification->data;

            if (is_array($data)) {
                $data['isSeen'] = false;
                $notification->data = $data;
                $notification->read_at = null;
                $notification->save();
            }
        }

        return $this->ApiResponse('All notifications marked as unread successfully', 200);
    }


    /**
     * Delete a specific notification.
     *
     * Removes the notification from the database.
     *
     * @param  int  $id  The ID of the notification to delete.
     * @return \Illuminate\Http\JsonResponse API response indicating success or notification not found.
     */
    public function deleteNotification($id)
    {
        $notification = Notification::find($id);
        if (! $notification) {
            return $this->ApiResponse('Notification not found', 404);
        }

        $notification->delete();

        return $this->ApiResponse('Deleted Successfully', 200);
    }

    /**
 * Delete all notifications for the authenticated user
 *
 * This method permanently removes all notifications associated with the currently
 * authenticated user. The operation is irreversible and will delete all notification
 * records from the database.
 *
 * ### Endpoint
 * `DELETE /api/notifications/delete-all`
 *
 * ### Response
 * - Success (200):
 *   ```json
 *   {
 *     "status": "success",
 *     "message": "Deleted Successfully",
 *     "code": 200
 *   }
 *   ```
 * - Error (401 - Unauthenticated):
 *   ```json
 *   {
 *     "message": "Unauthenticated"
 *   }
 *   ```
 *
 * ### Example Usage
 * ```javascript
 * // Using fetch API
 * fetch('/api/notifications', {
 *   method: 'DELETE',
 *   headers: {
 *     'Authorization': 'Bearer your_token_here',
 *     'Content-Type': 'application/json'
 *   }
 * })
 * .then(response => response.json())
 * .then(data => console.log(data));
 * ```
 *
 * @return \Illuminate\Http\JsonResponse Returns JSON response with operation status
 * @throws \Illuminate\Auth\AuthenticationException If user is not authenticated
 * @since 1.0.0
 */
    public function deleteAllNotifications()
    {
        Notification::where('notifiable_id', Auth::id())->delete();

        return $this->ApiResponse('Deleted Successfully', 200);
    }
}