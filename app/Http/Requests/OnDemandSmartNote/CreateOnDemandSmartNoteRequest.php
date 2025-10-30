<?php

namespace App\Http\Requests\OnDemandSmartNote;

use App\Traits\ApiResponseTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateOnDemandSmartNoteRequest extends FormRequest
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

        $rules = [
            'doctor_id' => ['required', 'exists:users,id'],
            'approved_by' => ['required', 'exists:users,id'],
            'approved' => ['required', 'boolean'],
            'is_shared' => ['required', 'boolean'],
            'approval_date' => ['required', 'date'],
            'note' => ['required'],
            'spoken_languages' => ['required', 'string'],
        ];
        // Check if the 'keme_direct' field is present in the request
        $rules['patient_id'] = $this->input('patient_id') === 'none'
            ? ['nullable']
            : ['required', 'exists:users,id'];

        return $rules;
    }
    public function messages()
    {
        return [
            'required' => 'Please fill in required fields.',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->ApiResponse($validator->errors()->first(), 404, $validator->errors()));
    }
}