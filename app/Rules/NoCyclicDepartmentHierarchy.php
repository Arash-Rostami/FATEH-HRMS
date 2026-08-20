<?php

namespace App\Rules;

use App\Models\Department;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Rejects a department subordinate_to value that self-references or would form a hierarchy cycle. */
class NoCyclicDepartmentHierarchy implements ValidationRule
{
    public function __construct(private readonly ?string $code)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (Department::wouldCreateCycle($this->code, $value)) {
            $fail(__('resources/department/strings.errors.cyclic_hierarchy'));
        }
    }
}
