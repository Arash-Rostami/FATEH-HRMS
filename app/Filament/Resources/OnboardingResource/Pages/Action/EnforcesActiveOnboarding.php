<?php

namespace App\Filament\Resources\OnboardingResource\Pages\Action;

use App\Models\Onboarding;

class  EnforcesActiveOnboarding
{
    /**
     * When a record is activated, deactivate all other records targeting the same audience (null user_id = global, or same user_id = personal).
     */
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
