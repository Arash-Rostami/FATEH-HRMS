<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TaskLabelLength implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            return;
        }

        foreach ($value as $tag) {
            if (is_string($tag) && mb_strlen($tag) > 30) {
                $fail(__('resources/task/strings.validation.labels.max_length'));

                return;
            }
        }
    }
}