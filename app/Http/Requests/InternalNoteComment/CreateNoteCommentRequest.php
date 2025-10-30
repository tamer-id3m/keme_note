<?php

namespace App\Http\Requests\InternalNoteComment;

use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateNoteCommentRequest extends FormRequest
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
        return [
            'patient_id' => 'required|exists:users,id',
            'user_id' => 'required|exists:users,id',
            'body' => 'required',
            'internal_note_id' => 'required',
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
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->ApiResponse($validator->errors()->first(), 404, $validator->errors()));
    }
}