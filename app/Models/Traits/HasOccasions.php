<?php

namespace App\Models\Traits;

trait HasOccasions
{
    public function isBirthdayToday(): bool
    {
        return !$this->isTerminated() && (bool)$this->birthdate?->isBirthday();
    }

    public function isAnniversaryToday(): bool
    {
        return !$this->isTerminated() && (bool)$this->start_date?->isBirthday();
    }

    public function todaysOccasionType(): ?string
    {
        return match (true) {
            $this->isBirthdayToday() => 'birthday',
            $this->isAnniversaryToday() => 'anniversary',
            default => null,
        };
    }

    public static function occasionTone(string $type): array
    {
        return match ($type) {
            'birthday' => [
                'chip' => 'bg-[var(--tool-gold-bg)] text-[var(--tool-gold-text)]',
                'ring' => 'ring-[var(--tool-gold-color)]',
                'text' => 'text-[var(--tool-gold-color)]',
                'bg' => 'color-mix(in srgb, var(--tool-gold-color) 14%, var(--md-sys-color-surface-container-low))',
                'icon' => 'cake',
                'label' => 'تولد',
            ],
            'anniversary' => [
                'chip' => 'bg-[var(--tool-sage-bg)] text-[var(--tool-sage-text)]',
                'ring' => 'ring-[var(--tool-sage-color)]',
                'text' => 'text-[var(--tool-sage-color)]',
                'bg' => 'color-mix(in srgb, var(--tool-sage-color) 14%, var(--md-sys-color-surface-container-low))',
                'icon' => 'workspace_premium',
                'label' => 'سالگرد کاری',
            ],
        };
    }
}
