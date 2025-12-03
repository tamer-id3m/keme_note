<?php

namespace App\Http\Resources\ProviderNote;

use App\Services\Timezone\TimezoneService;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderNoteResource extends JsonResource
{
    public function toArray($request)
    {
        $timezoneService = new TimezoneService();
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'message' => $this->body,
            'user_id' => $this->user_id,
            'full_name' => $this->user ? $this->user->name . ' ' . $this->user->last_name:'',
            'username' => $this->user?$this->user->username:null,
            'photo' => $this->user->photo,
            'doctor_id' => $this->doctor_id,
            'doctor_name' => $this->doctor ? $this->doctor->name . ' ' . $this->doctor->last_name : '',
            'patient_id' => $this->patient_id,
            'patient_name' => $this->patient
                ? trim($this->patient->name . ' ' . $this->patient->last_name)
                : null,
            'edited' => $this->edited,
            'comments' => ProviderNoteCommentResource::collection($this->whenLoaded('comments')),
            'updated_at' => $timezoneService->convertFromUTCToUserTimezone($this->updated_at),
            'created_from' => $this->updated_at ? $this->updated_at->diffForHumans() : null,
        ];
    }
}