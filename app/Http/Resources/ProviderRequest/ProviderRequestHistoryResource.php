<?php

namespace App\Http\Resources\ProviderRequest;

use App\Models\v3\Doctor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderRequestHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $editor = Doctor::findOrFail($this->edited_by);

        $message = [
            'message' => $this->body,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];

        return [
            'uuid' => $this->uuid,
            'edited_by' => $editor->name,
            'patient_id' => $this->user_id,
            'patient_name' => $this->patient ? $this->patient->name : null,
            'patient_photo' => $this->patient ? $this->patient->photo : null,
            'message' => $message,
            'doctor_id' => $this->doctor_id,
            'doctor_name' => $this->doctor->name,
        ];
    }
}