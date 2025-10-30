<?php

namespace App\Http\Resources\ProviderNote;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderNoteCommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = User::where('id', $this->user_id)->first();

        return [
            'id' => $this->id,
            'body' => $this->body,
            'patient_id' => $this->patient_id,
            'user_id' => $this->user_id,
            'provider_note_id' => $this->provider_note_id,
            'role' => $user->getRoleNames()[0] ?? '',
            'user' => [
               'full_name' => trim("{$user->name} {$user->last_name}"),
                // 'last_name' => $user->last_name,
                'photo' => $user->photo,
                'username' => $user->username,
            ],
            'edited' => $this->edited,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,

            'created_from' => $this->updated_at ? $this->updated_at->diffForHumans() : null,
        ];
    }
}