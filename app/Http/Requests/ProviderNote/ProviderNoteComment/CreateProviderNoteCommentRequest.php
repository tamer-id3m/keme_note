<?php

namespace App\Http\Requests\ProviderNote\ProviderNoteComment;

use Illuminate\Foundation\Http\FormRequest;

class CreateProviderNoteCommentRequest extends FormRequest
{
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
            'body' => 'required|string',
            'user_id' => 'required|exists:users,id',
            'provider_note_id' => 'required|exists:provider_notes,id',
            'patient_id' => 'required|exists:users,id',
        ];
    }
}