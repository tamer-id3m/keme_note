<?php

namespace App\Http\Resources\V3;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderRequestCommentHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $editor = User::findOrFail($this->edited_by);

        $message = [
            'message' => $this->body,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];

        return [
            'uuid' => $this->uuid,
            'edited_by' => $editor->name,
            'user_id' => $this->user_id,
            'name' => $this->doctor ? $this->doctor->name : null,
            'photo' => $this->doctor ? $this->doctor->photo : null,
            'message' => $message,
            'patient_id' => $this->patient_id,
        ];
    }
}