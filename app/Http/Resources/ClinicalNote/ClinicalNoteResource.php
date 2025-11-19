<?php

namespace App\Http\Resources\ClinicalNote;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ClinicalNoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $approver = match ($this->resource->resource) {
            'on_demand' => $this->onDemandSmartNote->approver ?? null,
            'smart_note' => $this->aiNote->approver ?? null,
            'appointment_summary' => $this->appointmentSummary->approver ?? null,
            default => null,
        };

        return [
            'id' => $this->id,
            'subjective' => $this->subjective,
            'chief_complaint' => $this->chief_complaint,
            'history_of_present_illness' => $this->history_of_present_illness,
            'current_medications' => $this->current_medications,
            'diagnosis' => $this->diagnosis,
            'assessments' => $this->assessments,
            'plan' => $this->plan,
            'procedures' => $this->procedures,
            'medications' => $this->medications,
            'risks_benefits_discussion' => $this->risks_benefits_discussion,
            'care_plan' => $this->care_plan,
            'next_follow_up' => $this->next_follow_up,
            'next_follow_up_value' => $this->next_follow_up_value,
            'next_follow_up_timeframe' => $this->next_follow_up_timeframe,
            'date' => $this->date ? Carbon::createFromFormat('m-d-y', $this->date)->format('m-d-Y'): null,
            'doctor_id' => $this->doctor_id,
            'user_id' => $this->doctor_id,
            'patient_id' => $this->patient_id,
            
            // Use the enriched data that comes from ClinicalNotesService
            'labs1' => $this->labs1 ?? [], // Will be set by service
            'lap' => $this->lap ?? [], // Will be set by service

            // Patient data from User Service (set by ClinicalNotesService)
            'patient_full_name' => $this->patient_full_name ?? '',
            'patient_photo' => $this->patient_photo ?? '',
            'patient_clinic' => $this->patient_clinic ?? null,
            'patient_doctor' => $this->patient_doctor ?? null,
            'pat_id' => $this->pat_id ?? '',
            'pat_date' => $this->pat_date ?? '',

            // User/Doctor data from User Service (set by ClinicalNotesService)
            'user_full_name' => $this->user_full_name ?? '',
            'user_photo' => $this->user_photo ?? null,

            // View data (set by ClinicalNotesService)
            'labsView' => $this->labsView ?? '',
            'medicationsView' => $this->medicationsView ?? '',
            'medication' => $this->medication ?? [],

            'is_shared' => $this->is_shared,
            'approved_by' => $approver?->full_name,
            'approved_photo' => $approver?->photo,
            'resource' => $this->resource->resource,
        ];
    }
}