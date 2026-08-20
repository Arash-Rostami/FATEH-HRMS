<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

/** MA/MG-department users cannot submit suggestions; surfaces as a field error at submit time. */
class SuggestionSubmitterNotFromMaOrMg implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $submitter = User::with('profile.department')->find($value) ?? Auth::user();

        if (in_array($submitter?->profile?->department_id, ['MA', 'MG'], true)) {
            $fail(__('resources/suggestion/strings.errors.ma_restricted'));
        }
    }
}
