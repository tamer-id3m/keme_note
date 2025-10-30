<?php

namespace App\Http\Resources\ClinicalNote;

use Carbon\Carbon;

use App\Models\User;
use App\Models\v3\Lab;

use App\Models\v3\Dose;
use App\Models\Medication;
use App\Models\v3\NoteLab;
use Illuminate\Support\Arr;
use App\Models\NoteMediction;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Medication\MedicationDoasageResource;
use App\Services\Timezone\TimezoneService;

class ClinicalNoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
  public function toArray($request)
    {
        $labIds = NoteLab::where('note_id', $this->id)->pluck('lab_id')->toArray();
        $medicationIds = NoteMediction::where('note_id', $this->id)->pluck('mediction_id')->toArray();

        $labs = Lab::whereIn('id', $labIds)->get();
        $medications = Medication::whereIn('id', $medicationIds)->get();

        $labsView = $labs->pluck('name')->map(fn($name) => " -{$name}")->implode('');
        $medicationsView = $medications->map(fn($med) => " -{$med->name} {$med->dosage}")->implode('');

        $dosageIds = [];

        if (!is_null($this->doasge)) {
            $dosageIds = Arr::pluck($this->doasge, 'dosage_id');
        }

        $dosages = Dose::whereIn('id', $dosageIds)->get();

        $approver = match ($this->resource->resource) {
            'on_demand' => $this->onDemandSmartNote->approver ?? null,
            'smart_note' => $this->aiNote->approver ?? null,
            'appointment_summary' => $this->appointmentSummary->approver ?? null,
            default => null,
        };


        $patient = null;
        $doctor = null;
        if ($this->doctor_id) {
        $doctor = User::find($this->doctor_id);
        }
        if ($this->patient_id) {
            $patient = User::find($this->patient_id);
        }

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
            'labs1' => Lab::whereIn('id', $labIds)->get(),

            'patient_full_name' => $patient ? trim("{$patient->name} {$patient->last_name}") : '',
            'patient_photo' => $patient->photo ?? '',
            'patient_clinic' => $patient && $patient->clinic ? $patient->clinic->name : null,

            'patient_doctor' => $patient && $patient->doctor
                ? trim("{$patient->doctor->name} {$patient->doctor->last_name}")
                : null,
            'pat_id' => $patient->patient_id ?? '',
            'pat_date' => $patient->birth_date ?? '',

            'user_full_name' => $doctor ? trim("{$doctor->name} {$doctor->last_name}") : '',
            'user_photo' => $doctor ? $doctor->photo : null,

            'lap' => $labIds,
            'labsView' => $labsView,
            'medicationsView' => $medicationsView,
            'medication' => MedicationDoasageResource::collection($dosages)->response()->getData(true),
            'is_shared' => $this->is_shared,
            'approved_by' => $approver?->full_name,
            'approved_photo' => $approver?->photo,
            'resource' => $this->resource->resource,
        ];
    }

}