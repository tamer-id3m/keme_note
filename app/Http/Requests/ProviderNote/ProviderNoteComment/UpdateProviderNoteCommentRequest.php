<?php

namespace App\Http\Requests\ProviderNote\ProviderNoteComment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProviderNoteCommentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'body' => 'string|nullable',
            'user_id' => 'integer|exists:users,id|nullable',
            'provider_note_id' => 'integer|exists:provider_notes,id|nullable',
            'patient_id' => 'integer|exists:users,id|nullable',
        ];
    }
}