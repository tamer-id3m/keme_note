<?php

namespace App\Http\Resources\ProviderNote;

use App\Models\User;
use App\Services\Timezone\TimezoneService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderCommentHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $timezoneService = new TimezoneService();

        $editedUser = User::findOrFail($this->edited_by);

        $message = [
            'message' => $this->body,
            'updated_at' => $this->updated_at ? $timezoneService->convertFromUTCToUserTimezone($this->updated_at) : null,
        ];

        return [
            'id' => $this->id,
            'edited_by' => $editedUser->name,
            'user_id' => $this->user_id,
            'name' => $this->user ? $this->user->name : null,
            'photo' => $this->user ? $this->user->photo : null,
            'message' => $message,
            'patient_id' => $this->patient_id,
        ];
    }
}