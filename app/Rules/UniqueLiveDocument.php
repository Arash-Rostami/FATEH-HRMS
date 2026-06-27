<?php

namespace App\Rules;

use App\Filament\Resources\DmsResource\Enums\DocumentStatus;
use App\Models\DMS;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** (code, version) must be unique among LIVE docs only — non-live duplicates are allowed. On edit the record itself is excluded. Field-agnostic: the whole triple is read from siblings via Get in the form, so the same rule can be attached to code/version/status and re-validates whenever any of the three changes. Form-layer only: MySQL has no filtered unique index, so a DB unique would wrongly block the allowed non-live duplicates. */
class UniqueLiveDocument implements ValidationRule
{
    public function __construct(
        public readonly string $code,
        public readonly string $version,
        public readonly string $status,
        public readonly ?int $exceptId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // $value is the field this rule is attached to; the triple below is the source of truth.
        if ($this->code === '' || $this->version === '') {
            return;
        }

        if ($this->status !== DocumentStatus::Live->value) {
            return;
        }

        $conflict = DMS::query()
            ->where('code', $this->code)
            ->where('version', $this->version)
            ->where('status', DocumentStatus::Live->value)
            ->when($this->exceptId, fn ($q) => $q->where('id', '!=', $this->exceptId))
            ->exists();

        if ($conflict) {
            $fail(__('resources/dms/strings.validation.unique_live_version', [
                'code' => $this->code,
                'version' => $this->version,
            ]));
        }
    }
}