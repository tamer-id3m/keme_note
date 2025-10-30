<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPatient implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if the user exists and has the "patient" role
        $exists = User::where('id', $value)->whereHas('roles', function ($query) {
            $query->where('name', 'patient');
        })->exists();

        if (! $exists) {
            $fail('The selected patient is not valid.');
        }
    }
}