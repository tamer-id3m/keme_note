<?php

namespace App\Http\Resources\InternalNoteComment;

use App\Services\Timezone\TimezoneService;
use Illuminate\Http\Resources\Json\JsonResource;

class InternalNoteCommentResource extends JsonResource
{
    public function toArray($request): array
    {
        $timezoneService = new TimezoneService();

        $message = [
            'message' => $this->body,
            'updated_at' => $timezoneService->convertFromUTCToUserTimezone($this->updated_at),
        ];

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->user ? $this->user->name : null,
            'photo' => $this->user ? $this->user->photo : null,
            'message' => $message,
            'patient_id' => $this->patient_id,
        ];
    }
 
    public function withTimeZone()
    {
        return $this;
    }
}