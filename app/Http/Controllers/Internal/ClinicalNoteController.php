<?php

namespace App\Http\Controllers\Internal;

use Pino\Numera;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use App\Models\ClinicalNote;

class ClinicalNoteController extends Controller
{
    public function createOrUpdateClinicalNote(Request $request)
    {
        $resource = $request->get('resource');
        $note = (object) $request->note;
        $parsedData = [
            'subjective' => $request->get('subjective'),
            'chief_complaint' => $request->get('chief_complaint'),
            'history_of_present_illness' => $request->get('history_of_present_illness'),
            'current_medications' => $request->get('current_medications'),
            'diagnosis' => $request->get('diagnosis'),
            'assessments' => $request->get('assessments'),
            'plan' => $request->get('plan'),
            'procedures' => $request->get('procedures'),
            'medications' => $request->get('medications'),
            'risks_benefits_discussion' => $request->get('risks_benefits_discussion'),
            'care_plan' => $request->get('care_plan'),
            'next_follow_up' => $request->get('next_follow_up'),
        ];

        if ($resource == 'smart_note') {
            $clinicalNote = ClinicalNote::firstOrNew(['note_id' => $note->id]);
        } elseif ($resource == 'on_demand') {
            $clinicalNote = ClinicalNote::firstOrNew(['on_demand_smart_note_id' => $note->id]);
        } elseif ($resource == 'appointment_summary') {
            $clinicalNote = ClinicalNote::firstOrNew(['appointment_summary_id' => $note->id]);
        }

        $nextFollowUpDetails = $this->extractFollowUpDetails($parsedData['next_follow_up']);

        $clinicalNote->fill([
            'subjective' => $parsedData['subjective'],
            'chief_complaint' => $parsedData['chief_complaint'],
            'history_of_present_illness' => $parsedData['history_of_present_illness'],
            'current_medications' => $parsedData['current_medications'],
            'diagnosis' => $parsedData['diagnosis'],
            'assessments' => $parsedData['assessments'],
            'plan' => $parsedData['plan'],
            'procedures' => $parsedData['procedures'],
            'medications' => $parsedData['medications'],
            'risks_benefits_discussion' => $parsedData['risks_benefits_discussion'],
            'care_plan' => $parsedData['care_plan'],
            'next_follow_up' => $parsedData['next_follow_up'],
            'next_follow_up_value' => $nextFollowUpDetails['value'],
            'next_follow_up_timeframe' => $nextFollowUpDetails['timeframe'],
            'patient_id' => $request->get('request_patient_id', $note->patient_id),
            'doctor_id' => $request->get('request_doctor_id', $note->doctor_id),
            'date' => $note->approval_date,
            'resource' => $resource
        ])->save();
    }
    private function extractFollowUpDetails($nextFollowUp): array
    {
        $value = null;
        $timeframe = null;

        if (preg_match('/(\d+)\s*(weeks|days|months)/i', $nextFollowUp, $matches)) {
            $value = $matches[1];
            $timeframe = $matches[2];
        } elseif (preg_match_all('/[a-zA-Z]+/', $nextFollowUp, $matches)) {
            $numera = Numera::init();
            $number = $numera->convertToNumber(strtolower($matches[0][0]));
            $value = $number;
            $timeframe = $matches[0][1] ?? null;
        }

        return ['value' => $value, 'timeframe' => $timeframe];
    }
}
