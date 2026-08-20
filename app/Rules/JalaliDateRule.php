<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Morilog\Jalali\CalendarUtils;

class JalaliDateRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $value)) {
            $fail('تاریخ نامعتبر است.');
            return;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        if (!CalendarUtils::checkDate($year, $month, $day, true)) {
            $fail('تاریخ نامعتبر است.');
        }
    }
}