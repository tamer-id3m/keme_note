<?php

namespace App\Http\Resources\ProviderRequest;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderRequestCommentHistoryResource extends JsonResource
{
    public $loadedEditor;
    public $loadedDoctor;

    public function withUsers($editor, $doctor)
    {
        $this->loadedEditor = $editor;
        $this->loadedDoctor = $doctor;
        return $this;
    }

    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'edited_by' => $this->loadedEditor?->name,
            'user_id' => $this->user_id,

            'name' => $this->loadedDoctor?->name,
            'photo' => $this->loadedDoctor?->photo,

            'message' => [
                'message' => $this->body,
                'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            ],

            'patient_id' => $this->patient_id,
        ];
    }
}
