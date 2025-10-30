<?php

namespace App\Services\ClinicalNotes;

use App\Models\Dose;
use App\Models\NoteLab;
use App\Models\v3\Patient;
use App\Models\ClinicalNote;
use App\Traits\ApiResponseTrait;
use App\Models\v3\NoteMedication;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use DateTime;
use App\Http\Resources\ClinicalNote\ClinicalNoteResource;

class ClinicalNotesService
{

use ApiResponseTrait;

/**
 * Display a specific clinical note with its related entities.
 *
 * @param  int  $id  The ID of the clinical note to retrieve.
 * @return \Illuminate\Http\JsonResponse
 */
public function show($id)
{
    $note = ClinicalNote::with([
            'aiNote',
            'aiNote.approver',
            'doctor'
        ])
        ->find($id);

    if (!$note) {
        return $this->apiResponse(
            'Clinical Note not found',
            404
        );
    }

    return $this->apiResponse(
        'Clinical Note retrieved successfully',
        200,
        new ClinicalNoteResource($note)
    );
}


/**
 * Store a newly created clinical note and attach related labs and medications.
 *
 * @param  \Illuminate\Http\Request  $request  The request containing clinical note data.
 * @return \Illuminate\Http\JsonResponse
 */
public function store($request)
{
    DB::beginTransaction();

    try {
        $note = $this->createClinicalNote($request);
        $this->attachLabsToNote($request, $note);
        $this->attachMedicationsToNote($request, $note);

        DB::commit();

        return $this->apiResponse(
            __('clinical_note.created_successfully'),
            201,
            new ClinicalNoteResource($note)
        );
    } catch (\Throwable $e) {
        DB::rollBack();
         return $this->ApiResponse('An error occurred while creating the clinical note.', 500, [
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Create a new clinical note and perform associated actions like updating patient status and clearing cache.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \App\Models\ClinicalNote
 */
protected function createClinicalNote($request): ClinicalNote
{
    $note = ClinicalNote::create($this->prepareNoteData($request));

    $this->updatePatientStatus($note->patient_id);
    $this->clearClinicalNotesCache();

    return $note;
}

/**
 * Prepare data for clinical note creation.
 *
 * @param \Illuminate\Http\Request $request
 **/
protected function prepareNoteData($request)
{
    $patient = Patient::find($request->patient_id);
    if (!$patient) {
        throw new \Exception('Patient not found');
    }
    return [
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
        'date' => now(),
        'patient_id' => $patient->id,
        'doctor_id' => $patient->doctor_id,
        'is_shared' => false,
        'resource' => 'clinical_note',
    ];
}

/**
 * Update patient status to FollowUp.
 *
 * @param int $patientId
 * @return void
 */
protected function updatePatientStatus(int $patientId): void
{
    Patient::where('id', $patientId)->update(['type' => 'FollowUp']);
}

/**
 * Clear clinical notes cache.
 *
 * @return void
 */
protected function clearClinicalNotesCache(): void
{
    Cache::tags(['clinical_notes'])->flush();
}

/**
 * Attach labs to the clinical note.
 *
 * @param \Illuminate\Http\Request $request
 * @param \App\Models\ClinicalNote $note
 * @return void
 */
protected function attachLabsToNote($request, ClinicalNote $note): void
{
    foreach ($request->input('lab', []) as $labId) {
        NoteLab::create([
            'note_id' => $note->id,
            'lab_id' => $labId,
        ]);
    }
}

/**
 * Attach medications to the clinical note.
 *
 * @param \Illuminate\Http\Request $request
 * @param \App\Models\ClinicalNote $note
 * @return void
 */
protected function attachMedicationsToNote($request, ClinicalNote $note): void
{
    foreach ($request->input('medication', []) as $dosageId) {
        NoteMedication::create([
            'note_id'       => $note->id,
            'mediction_id' => Dose::where('id', $dosageId)->value('medication_id'),
            'dosage_id'     => $dosageId,
        ]);
    }
}
/**
 * Delete the specified clinical note along with its related labs and medications.
 *
 * @param  int  $id
 * @return \Illuminate\Http\JsonResponse
 */

public function destroy($id)
{
    DB::beginTransaction();

    try {
        $note = ClinicalNote::find($id);

        if (!$note) {
            return $this->apiResponse(
                'clinical note not found',
                404
            );
        }

        $this->deleteNoteRelations($note);
        $note->delete();
        $this->clearClinicalNotesCache();

        DB::commit();

        return $this->apiResponse(
            'clinical note deleted successfully',
            200
        );
    } catch (\Exception $e) {
        DB::rollBack();
        return $this->ApiResponse('Failed to delete clinical note.', 500, [
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Detach all related labs and medications from the clinical note.
 *
 * @param  \App\Models\ClinicalNote  $note
 * @return void
 */
protected function deleteNoteRelations(ClinicalNote $note): void
{
    $note->labs()->detach();
    $note->medications()->detach();
}
/**
 * Toggle the shared status of a clinical note and update patient type if applicable.
 *
 * @param  int  $id
 * @return \Illuminate\Http\JsonResponse
 */
public function shareStatus($id)
{
    $note = ClinicalNote::find($id);

    if (!$note) {
            return $this->apiResponse(
                'clinical note not found',
                404
            );
        }

    $this->toggleNoteSharing($note);

    if ($note->is_shared) {
        $this->updatePatientType($note->patient_id);
    }

    $this->clearClinicalNotesCache();

    return $this->apiResponse(
        $note->is_shared
            ? __('The clinical note is shared successfully')
            : __('The clinical note is not shared successfully'),
        200
    );
}

/**
 * Toggle the `is_shared` flag of a clinical note and set doctor ID if missing.
 *
 * @param  \App\Models\ClinicalNote  $note
 * @return void
 */
private function toggleNoteSharing(ClinicalNote $note): void
{
    if (!$note->is_shared && is_null($note->doctor_id)) {
        $note->doctor_id = auth()->id();
    }

    $note->is_shared = !$note->is_shared;
    $note->save();
}
/**
 * Update the patient type from "New" to "FollowUp" if applicable.
 *
 * @param  int  $patientId
 * @return void
 */

private function updatePatientType(int $patientId): void
{
    Patient::where('id', $patientId)
        ->where('type', 'New')
        ->update(['type' => 'FollowUp']);
}
/**
 * Update the specified clinical note with new data, labs, and medications.
 *
 * @param  \Illuminate\Http\Request  $request
 * @param  int  $id  The ID of the clinical note.
 * @return \Illuminate\Http\JsonResponse
 */
public function update($request, int $id)
{
    DB::beginTransaction();

    try {
        $note = ClinicalNote::find($id);

        if (! $note) {
            return $this->apiResponse('Clinical Note not found', 404);
        }

        $formattedDate = $this->formatDate($note->date);

        $note->update($this->prepareUpdateData($request, $formattedDate));

        $this->syncNoteLabs($request->lab, $note->id);
        $this->syncNoteMedications($request->input('medication'), $note->id);

        $this->clearClinicalNotesCache();

        DB::commit();

        return $this->apiResponse(__('Updated Successfully'), 200, new ClinicalNoteResource($note));
    } catch (\Throwable $e) {
        DB::rollBack();
        return $this->apiResponse('Something went wrong.', 500, $e->getMessage());
    }
}
/**
 * Format a date string from 'm-d-y' to 'Y-m-d'.
 *
 * @param  string  $date
 * @return string
 */
private function formatDate(string $date): string
{
    return DateTime::createFromFormat('m-d-y', $date)->format('Y-m-d');
}
/**
 * Prepare an array of clinical note fields for update.
 *
 * @param  \Illuminate\Http\Request  $request
 * @param  string  $date  The formatted date string.
 * @return array
 */
private function prepareUpdateData($request, string $date): array
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
 * Synchronize labs related to the clinical note.
 *
 * @param  array|null  $labs
 * @param  int  $noteId
 * @return void
 */
private function syncNoteLabs(?array $labs, int $noteId): void
{
    if (is_null($labs)) {
        return;
    }

    NoteLab::where('note_id', $noteId)->delete();

    foreach ($labs as $labId) {
        NoteLab::create([
            'note_id' => $noteId,
            'lab_id' => $labId,
        ]);
    }
}

/**
 * Synchronize medications related to the clinical note.
 *
 * @param  array|null  $medications
 * @param  int  $noteId
 * @return void
 */
private function syncNoteMedications(?array $medications, int $noteId): void
{
    if (empty($medications)) {
        return;
    }

    NoteMedication::where('note_id', $noteId)->delete();

    foreach ($medications as $dosageId) {
        $medicationId = Dose::where('id', $dosageId)->value('medication_id');

        NoteMedication::create([
            'note_id' => $noteId,
            'mediction_id' => $medicationId,
            'dosage_id' => $dosageId,
        ]);
    }
}

}