<?php

namespace App\Http\Resources\StaffNote;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffNoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'body' => $this->body,
            'user_id' => $this->user_id,
            'patient_id' => $this->patient_id,
            'edited' => $this->edited,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'created_from' => $this->updated_at ? $this->updated_at->diffForHumans() : null,
        ];
    }
}