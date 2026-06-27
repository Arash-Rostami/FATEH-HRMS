<?php

namespace App\Rules;

use App\Models\Permission;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Caps a super admin's exclusions at 0..~20% of modules; beyond that the row is really a restricted admin and must use abilities. */
class SuperAdminExclusion implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $excluded = is_array($value) ? count($value) : 0;

        $total = count(Permission::availableModules());
        // floor(20%); max() guards a tiny module count from producing 0.
        $max = max(1, (int) floor($total * 0.20));

        if ($excluded > $max) {
            $fail(__('resources/permission/strings.validation.exclusion_too_many', [
                'excluded' => $excluded,
                'total'    => $total,
                'max'      => $max,
            ]));
        }
    }
}