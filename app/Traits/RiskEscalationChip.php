<?php

namespace App\Traits;

trait RiskEscalationChip
{
    protected function riskToneClasses(string $tone): string
    {
        return match ($tone) {
            'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)] border-[color-mix(in_srgb,var(--md-sys-color-error)_25%,transparent)]',
            'warning' => 'bg-[var(--md-sys-color-warning-container)] text-[var(--md-sys-color-on-warning-container)] border-[color-mix(in_srgb,var(--md-sys-color-warning)_25%,transparent)]',
            default => 'bg-[var(--md-sys-color-success-container)] text-[var(--md-sys-color-on-success-container)] border-[color-mix(in_srgb,var(--md-sys-color-success)_25%,transparent)]',
        };
    }
}
