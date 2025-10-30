<?php

namespace App\Http\Requests\StaffNote;

use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateStaffNoteRequest extends FormRequest
{
    use ApiResponseTrait;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        // You can add authorization logic here if needed
        return true;
    }

    public function rules()
    {
        return [
            'body' => ['required'],
            'user_id' => ['required', 'exists:users,id'],
            'patient_id' => ['required', 'exists:users,id'],

        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'required' => 'Please fill in required fields.',
            'unique' => 'The :attribute has already been taken.',
            'string' => 'The :attribute must be string',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->ApiResponse($validator->errors()->first(), 404, $validator->errors()));
    }
}