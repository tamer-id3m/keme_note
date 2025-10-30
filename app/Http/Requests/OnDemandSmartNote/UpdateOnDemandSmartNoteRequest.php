<?php

namespace App\Http\Requests\OnDemandSmartNote;

use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOnDemandSmartNoteRequest extends FormRequest
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
    public function rules(): array
    {
        $rules =[
            'patient_id' => ['nullable', Rule::when(
                fn($input) => $input->get('patient_id') !== 'none',
                'exists:users,id'
            )],
            'doctor_id' => ['nullable', 'exists:users,id'],
            'approved_by' => ['nullable', 'exists:users,id'],
            'approved' => ['nullable', 'boolean'],
            'is_shared' => ['nullable', 'boolean'],
            'approval_date' => ['nullable', 'date'],
            'note' => ['required'],
            'spoken_languages' => ['string'],
        ];

        return $rules;
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->ApiResponse($validator->errors()->first(), 404, $validator->errors()));
    }
}