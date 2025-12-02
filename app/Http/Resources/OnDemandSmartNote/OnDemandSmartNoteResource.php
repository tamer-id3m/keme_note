<?php

namespace App\Http\Resources\OnDemandSmartNote;

use App\Models\Context;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OnDemandSmartNoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lastQueue = $this->queueLists->last();

        $patient = optional($this->patient);
        $doctor = optional($this->doctor);
        $approver = optional($this->approver);

        $context = Context::where('type', 'on_demand')->first();

        return [
            'id'                => $this->id,
            'approved'          => $this->approved,
            'is_shared'         => $this->is_shared,
            'note'              => $this->note,
            'approval_date'     => $this->approval_date,
            'patient_id'        => $this->patient_id,
            'patient_uuid'      => $this->patient_uuid ?? $patient->uuid,
            'patient_name'      => trim($patient->name . ' ' . $patient->last_name),
            'patient_photo'      => $patient->photo,
            'doctor_id'         => $this->doctor_id,
            'doctor_uuid'       => $this->doctor_uuid ?? $doctor->uuid,
            'doctor_name'       => trim($doctor->name . ' ' . $doctor->last_name),
            'doctor_photo'      => $doctor->photo,
            'approved_by'       => $this->approved_by,
            'approved_by_name'  => $approver->full_name,
            'approved_by_photo'  => $approver->photo,
            'ai_diagnosis'      => $this->ai_diagnosis,

            // 'context_id'        => $context->id,
            // 'context_name'      => $context->name,
            // 'ai_env_id'         => $context->aienv_id,
            // 'ai_env_name'       => $context->aiEnv ? $context->aiEnv->name : null,
            // 'ai_model_id'       => $context->model_id,
            // 'ai_model_name'     => $context->aiModel ? $context->aiModel->name : null,
            // 'keme_direct'       => $context->keme_direct,

            'current_status'    => $lastQueue?->type,
            'spoken_languages'  => $this->spoken_languages,
        ];;
    }
}