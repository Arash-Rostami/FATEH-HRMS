<?php

namespace App\Rules;

use App\Models\Skill;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Blocks a name/name_en that collides with another skill's name or name_en (Skill::matchingName checks both columns). On create, only active-skill collisions block; on edit, any collision except the record itself blocks. */
class UniqueActiveSkillName implements ValidationRule
{
    public function __construct(
        public readonly ?Skill $record,
        public readonly string $operation,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $conflicts = Skill::matchingName($value)
            ->when($this->record, fn ($query) => $query->whereKeyNot($this->record->getKey()))
            ->when($this->operation === 'create', fn ($query) => $query->where('is_active', true))
            ->exists();

        if ($conflicts) {
            $fail(__('resources/skill/strings.errors.name_conflict'));
        }
    }
}
