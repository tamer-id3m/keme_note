<?php

namespace App\Services\AiNote;

interface AiNoteServiceInterface
{
    /**
     * Parse the note content into structured data.
     */
    public function parseNoteField($noteContent);

    /**
     * Create or update the clinical note based on parsed data.
     */
    public function createOrUpdateClinicalNote($parsedData, $request, $note,$resource);

    /**
     * Extracts the value and timeframe from the next follow-up string.
     */
    public function extractFollowUpDetails($nextFollowUp);

    /**
     * Update the patient type if conditions are met.
     */
    public function updatePatientType($patientId);
}