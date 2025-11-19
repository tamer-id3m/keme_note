<?php

namespace App\Services\ClinicalNotes;

use App\Http\Resources\ClinicalNote\ClinicalNoteResource;
use App\Models\ClinicalNote;
use App\Models\Dose;
use App\Models\NoteLab;
use App\Models\NoteMedication;
use App\Traits\ApiResponseTrait;
use App\Http\Clients\UserClient;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ClinicalNotesService
{
    use ApiResponseTrait;

    protected $userClient;

    public function __construct(UserClient $userClient)
    {
        $this->userClient = $userClient;
    }

    /**
     * Retrieve a specific clinical note.
     */
    public function show($id)
    {
        $note = ClinicalNote::with(['aiNote', 'aiNote.approver', 'doctor','patients.clinic',
        'patients.doctor'])->find($id);
        if (! $note) {
            return $this->ApiResponse('Clinical Note not found', 404);
        }

        // Enrich with external data
        $enrichedNote = $this->enrichWithExternalData($note);
        
        return $this->ApiResponse('success', 200, new ClinicalNoteResource($enrichedNote));
    }

    /**
     * Enrich note with data from User Service
     */
/**
 * Enrich note with data from User Service and prepare all resource data
 */
private function enrichWithExternalData($note)
{
    // Get patient info from User Service
    $patientUser = $this->userClient->getPatientById($note->patient_id);
    
    // Get doctor info from User Service
    $doctorUser = $this->userClient->getDoctorById($note->doctor_id);
    
    // Get labs data (these should be local to Clinical Notes service)
    $labIds = $note->labs->pluck('id')->toArray();
    $labs = $note->labs;
    
    // Get medications data (these should be local to Clinical Notes service)
    $medicationIds = $note->medications->pluck('id')->toArray();
    $medications = $note->medications;

    // Prepare view data
    $labsView = $labs->pluck('name')->map(fn($name) => " -{$name}")->implode('');
    $medicationsView = $medications->map(fn($med) => " -{$med->name} {$med->dosage}")->implode('');

    // Add all the data needed for the resource
    if ($patientUser) {
        $note->patient_full_name = $patientUser->name . ' ' . $patientUser->last_name;
        $note->patient_photo = $patientUser->photo;
        $note->patient_clinic = $patientUser->clinic_id ? 'Clinic #' . $patientUser->clinic_id : null;
        $note->pat_id = $patientUser->patient_id;
        $note->pat_date = $patientUser->birth_date;
        
        // If patient has a doctor in User Service, use that
        if ($patientUser->doctor_id) {
            $patientDoctor = $this->userClient->getDoctorById($patientUser->doctor_id);
            $note->patient_doctor = $patientDoctor ? ($patientDoctor->name . ' ' . $patientDoctor->last_name) : null;
        }
    }

    if ($doctorUser) {
        $note->user_full_name = $doctorUser->name . ' ' . $doctorUser->last_name;
        $note->user_photo = $doctorUser->photo;
    }

    // Add labs and medications data
    $note->labs1 = $labs;
    $note->lap = $labIds;
    $note->labsView = $labsView;
    $note->medicationsView = $medicationsView;
    $note->medication = $note->medications; // This should be your local medication relationship

    return $note;
}

    /**
     * Store a new clinical note along with related labs and medications.
     */
    public function store($request)
    {
        try {
            DB::beginTransaction();
            
            // Validate patient exists in User Service
            $patientUser = $this->userClient->getPatientById($request->patient_id);
            if (!$patientUser) {
                return $this->ApiResponse('Patient not found in User Service', 404);
            }

            $note = $this->createClinicalNoteRecord($request);
            $this->processLabs($request, $note);
            $this->processMedications($request, $note);

            DB::commit();

            return $this->ApiResponse(__('Added Successfully'), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing clinical note: ' . $e->getMessage());

            return $this->ApiResponse('Something went wrong.', 500);
        }
    }

    /**
     * Create a clinical note record.
     */
    private function createClinicalNoteRecord($request)
    {
        $data = [
            'subjective' => $request->subjective,
            'chief_complaint' => $request->chief_complaint,
            'history_of_present_illness' => $request->history_of_present_illness,
            'current_medications' => $request->current_medications,
            'diagnosis' => $request->diagnosis,
            'assessments' => $request->assessments,
            'plan' => $request->plan,
            'procedures' => $request->procedures,
            'medications' => $request->medications,
            'risks_benefits_discussion' => $request->risks_benefits_discussion,
            'care_plan' => $request->care_plan,
            'next_follow_up' => $request->next_follow_up,
            'next_follow_up_value' => $request->next_follow_up_value,
            'next_follow_up_timeframe' => $request->next_follow_up_timeframe,
            'date' => Carbon::now(),
            'patient_id' => $request->patient_id,
            'doctor_id' => auth()->user()->id,
            'is_shared' => false,
            'resource' => 'clinical_note',
        ];
        $note = ClinicalNote::create($data);
        Cache::tags(['clinical_notes'])->flush();

        // Update patient status in User Service
        $this->userClient->updatePatientType($request->patient_id, 'FollowUp');

        return $note;
    }

    /**
     * Process lab records associated with the clinical note.
     */
    private function processLabs($request, $note)
    {
        if ($request->has('lab')) {
            foreach ($request->input('lab') as $val) {
                NoteLab::create(
                    [
                        'note_id' => $note->id,
                        'lab_id' => $val,
                    ]
                );
            }
        }
    }

    /**
     * Process medication records associated with the clinical note.
     */
    private function processMedications($request, $note)
    {
        if ($request->has('medication')) {
            foreach ($request->input('medication') as $mediction) {
                $medicationId = Dose::where('id', $mediction)->value('medication_id');

                NoteMedication::create([
                    'note_id' => $note->id,
                    'mediction_id' => $medicationId,
                    'dosage_id' => $mediction,
                ]);
            }
        }
    }

    /**
     * Update an existing clinical note along with related labs and medications.
     */
    public function update($request, $id)
    {
        try {
            DB::beginTransaction();
            $note = ClinicalNote::find($id);
            if (! $note) {
                return $this->ApiResponse('Clinical Note not found', 404);
            }

            $formattedDate = DateTime::createFromFormat('m-d-y', $note->date)->format('Y-m-d');
            $note->update($this->getUpdateData($request, $formattedDate));
            $this->updateNoteLabs($request->lab, $note->id);
            $this->updateNoteMedications($request->input('medication'), $note->id);
            Cache::tags(['clinical_notes'])->flush();

            DB::commit();

            return $this->ApiResponse(__('Updated Successfully'), 200, new ClinicalNoteResource($note));
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->ApiResponse('Something went wrong.', 500, $e->getMessage());
        }
    }

    /**
     * Retrieve the data for updating a clinical note.
     */
    private function getUpdateData($request, $date)
    {
        return $request->only([
            'subjective',
            'chief_complaint',
            'history_of_present_illness',
            'current_medications',
            'diagnosis',
            'assessments',
            'plan',
            'procedures',
            'medications',
            'risks_benefits_discussion',
            'care_plan',
            'next_follow_up',
            'next_follow_up_value',
            'next_follow_up_timeframe',
            'patient_id',
        ]) + ['date' => $date];
    }

    /**
     * Update the labs associated with a clinical note.
     */
    private function updateNoteLabs($labs, $noteId)
    {
        if ($labs !== null) {
            NoteLab::where('note_id', $noteId)->delete();

            foreach ($labs as $labId) {
                NoteLab::create(['note_id' => $noteId, 'lab_id' => $labId]);
            }
        }
    }

    /**
     * Update the medications associated with a clinical note.
     */
    private function updateNoteMedications($medictions, $noteId)
    {

        if (! empty($medictions)) {
            NoteMedication::where('note_id', $noteId)->delete();

            foreach ($medictions as $dosageId) {
                $medicationId = Dose::where('id', $dosageId)->value('medication_id');

                NoteMedication::create([
                    'note_id' => $noteId,
                    'mediction_id' => $medicationId,
                    'dosage_id' => $dosageId,
                ]);
            }
        }
    }

    /**
     * Delete a clinical note along with its related labs and medications.
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $note = ClinicalNote::find($id);
            if (! $note) {
                return $this->ApiResponse('Clinical Note not found', 404);
            }

            $note->labs()->detach();
            $note->medications()->detach();
            $note->delete();
            Cache::tags(['clinical_notes'])->flush();

            DB::commit();

            return $this->ApiResponse('Note and related details deleted successfully', 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->ApiResponse('Something went wrong.', 500, $e->getMessage());
        }
    }

    /**
     * Toggle the sharing status of a clinical note.
     */
    public function shareStatus($id)
    {

        $note = ClinicalNote::findOrFail($id);

        $this->toggleNoteSharing($note);

        if ($note->is_shared) {
            $this->updatePatientType($note->patient_id);
        }

        $message = $note->is_shared ? __('Status Activated Successfully') : __('Status Deactivated Successfully');
        Cache::tags(['clinical_notes'])->flush();

        return $this->ApiResponse($message, 200);
    }

    /**
     * Toggle the is_shared status of a clinical note.
     */
    private function toggleNoteSharing(ClinicalNote $note)
    {
        if (! $note->is_shared && $note->doctor_id === null) {
            $note->doctor_id = auth()->user()->id;
        }

        $note->is_shared = ! $note->is_shared;
        $note->save();
    }

    /**
     * Update the type of a patient based on the clinical note's action.
     */
    private function updatePatientType($patientId)
    {
        // Update patient status in User Service
        $this->userClient->updatePatientType($patientId, 'FollowUp');
    }

    // ========== INTERNAL SERVICE METHODS ==========

    /**
     * Get clinical notes by patient ID
     */
    public function getByPatientId($patientId)
    {
        return ClinicalNote::where('patient_id', $patientId)
            ->with(['labs', 'medications'])
            ->get();
    }

    /**
     * Get multiple clinical notes by IDs
     */
    public function getByIds(array $ids)
    {
        return ClinicalNote::whereIn('id', $ids)
            ->with(['labs', 'medications'])
            ->get();
    }

    /**
     * Delete clinical notes by patient ID
     */
    public function deleteByPatientId($patientId)
    {
        $notes = ClinicalNote::where('patient_id', $patientId)->get();
        $count = $notes->count();
        
        foreach ($notes as $note) {
            $this->destroy($note->id);
        }
        
        return $count;
    }

    /**
     * Get clinical notes count by patient ID
     */
    public function getCountByPatient($patientId)
    {
        return ClinicalNote::where('patient_id', $patientId)->count();
    }
}