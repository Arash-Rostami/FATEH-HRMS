<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** `extra` and `internal_url` must be filled together or both empty — field-agnostic like UniqueLiveDocument: both values are read from siblings via Get in the form, so attaching the same rule to either field re-validates the pair regardless of which one changed. */
class ExtraRequiresInternalUrl implements ValidationRule
{
    public function __construct(
        public readonly mixed $extra,
        public readonly mixed $internalUrl,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $extra = is_array($this->extra)
            ? array_values(array_filter(array_map('trim', $this->extra), fn (string $v): bool => $v !== ''))
            : [];

        if (($extra === []) !== blank($this->internalUrl)) {
            $fail(__('resources/link/strings.validation.extra_requires_internal_url'));
        }
    }
}
