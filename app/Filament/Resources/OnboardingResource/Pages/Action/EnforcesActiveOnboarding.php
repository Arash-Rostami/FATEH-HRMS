<?php

namespace App\Filament\Resources\OnboardingResource\Pages\Action;

use App\Models\Onboarding;

class EnforcesActiveOnboarding
{
    public static function deactivateConflicting(Onboarding $record): void
    {
        if (!$record->is_active) return;

        Onboarding::where('id', '!=', $record->id)
            ->when(
                $record->user_id === null,
                fn($q) => $q->whereNull('user_id'),
                fn($q) => $q->where('user_id', $record->user_id),
            )
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }
}
