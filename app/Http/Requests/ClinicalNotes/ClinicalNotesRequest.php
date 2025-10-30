<?php

namespace App\Http\Requests\ClinicalNotes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use App\Traits\ApiResponseTrait;

class ClinicalNotesRequest extends FormRequest
{
    use ApiResponseTrait;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'subjective' => 'nullable|string|max:1000',
            'chief_complaint' => 'nullable|string|max:1000',
            'history_of_present_illness' => 'nullable|string',
            'current_medications' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'assessments' => 'nullable|string',
            'plan' => 'nullable|string',
            'procedures' => 'nullable|string',
            'medications' => 'nullable|string',
            'risks_benefits_discussion' => 'nullable|string',
            'care_plan' => 'nullable|string',
            'next_follow_up' => 'nullable|string',
            'next_follow_up_value' => 'nullable|integer',
            'next_follow_up_timeframe' => 'nullable|string|max:1000',
            'patient_id' => 'required|exists:users,id',
            'lab' => 'nullable|array',
            'lab.*' => 'exists:labs,id',
            'medication' => 'nullable|array',
            'required_field' => 'required_without_all:subjective,chief_complaint,history_of_present_illness,current_medications,diagnosis,assessments,plan,procedures,medications,risks_benefits_discussion,care_plan,next_follow_up,next_follow_up_value,next_follow_up_timeframe',
        ];
    }

    public function messages()
    {
        return [
            'required_field.required_without_all' => 'At least one of the following fields is required.',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->ApiResponse($validator->errors()->first(), 404, $validator->errors()));
    }
}