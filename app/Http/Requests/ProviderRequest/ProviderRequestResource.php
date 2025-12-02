<?php

namespace App\Http\Resources\ProviderRequest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderRequestResource extends JsonResource
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
            'user_id' => $this->user_id,
            'message' => $this->body,
            'patient_id' => $this->patient_id,
            'patient_name' => $this->patient?->name,
            'patient_username' => $this->patient?->username,
            'patient_photo' => $this->patient?->photo,
            'doctor_id' => $this->doctor_id,
            'doctor_name' => $this->doctor?->name,
            'comments' => ProviderRequestCommentResource::collection($this->whenLoaded('providerRequestComments')),
            'edited' => $this->edited,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'created_from' => $this->updated_at ? $this->updated_at->diffForHumans() : null,
        ];
    }
}