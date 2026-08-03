<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

/** MA-department users cannot submit suggestions; surfaces as a field error at submit time. */
class SuggestionSubmitterNotFromMa implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $submitter = User::with('profile.department')->find($value) ?? Auth::user();

        if ($submitter?->profile?->department_id === 'MA') {
            $fail(__('resources/suggestion/strings.errors.ma_restricted'));
        }
    }
}
