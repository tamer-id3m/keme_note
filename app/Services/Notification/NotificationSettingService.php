<?php

namespace App\Services\Notification;

use Exception;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class NotificationSettingService
{
    use ApiResponseTrait;

/**
 * Retrieve the notification settings for a specific user.
 *
 * @param int $id The ID of the user whose notification settings are to be retrieved.
 * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the notification settings or an error message.
 */
    public function getNotificationSettings(int $id)
{
    try {
        $settings = User::query()
            ->where('id', $id)
            ->select([
                'is_send_emails',
                'is_leave_voice',
                'is_leave_text',
            ])
            ->first();

        if (!$settings) {
            return $this->apiResponse(
               'User Not Found',
                404,
            );
        }

        return $this->apiResponse(
            'Success',
             200,
             $settings
        );
    } catch (Exception $exception) {
        return $this->apiResponse(
           'Error retrieving notification settings',
            500,
           ['error' => $exception->getMessage()]
        );
    }
}
/**
 * Update the notification settings for a specific user.
 *
 * @param \Illuminate\Http\Request $request The request containing optional notification settings.
 * @param int $id The ID of the user whose notification settings are to be updated.
 * @return \Illuminate\Http\JsonResponse Returns a JSON response indicating success or failure of the update operation.
 */

public function updateNotificationSettings($request, int $id)
{
    DB::beginTransaction();

    try {
        $validatedData = $request->validate([
            'is_send_emails' => 'sometimes|boolean',
            'is_leave_voice' => 'sometimes|boolean',
            'is_leave_text' => 'sometimes|boolean',
        ]);

        $user = User::findOrFail($id);
        $user->update($validatedData);

        DB::commit();

        return $this->apiResponse(
            'Notification settings updated successfully',
             200,
             $user->only([
                'is_send_emails',
                'is_leave_voice',
                'is_leave_text'
            ])
        );
    } catch (ModelNotFoundException $modelException) {
        DB::rollBack();
        return $this->apiResponse(
          'User not found',
           404,
           []
        );

    } catch (Exception $exception) {
        DB::rollBack();
        return $this->apiResponse(
            'Failed to update notification settings',
            500,
            [],
            ['error' => $exception->getMessage()]
        );
    }
}
}