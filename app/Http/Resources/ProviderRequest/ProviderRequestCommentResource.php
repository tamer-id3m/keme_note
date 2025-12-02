<?php

namespace App\Http\Resources\ProviderRequest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderRequestCommentResource extends JsonResource
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
            'patient_id' => $this->patient_id,
            'user_id' => $this->user_id,
            'provider_note_id' => $this->provider_note_id,
            'edited' => $this->edited,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'created_from' => $this->updated_at ? $this->updated_at->diffForHumans() : null,
            'role' => $this->user->getRoleNames()[0] ?? '',
            'user' => [
                'name' => $this->user->name,
                'last_name' => $this->user->last_name,
                'photo' => $this->user->photo,
                'username' => $this->user->username,
            ],
        ];
    }
}