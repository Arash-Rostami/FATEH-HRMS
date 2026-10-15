<?php

namespace App\Traits;

use App\Enums\PresenceStatus;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait HasFocusMode
{
    private const MAX_FOCUS_MINUTES = 240;

    public function activateFocusMode(?int $minutes = null): void
    {
        $until = now()->addMinutes($minutes ? min(max($minutes, 1), self::MAX_FOCUS_MINUTES) : User::INDEFINITE_FOCUS_MINUTES);

        Auth::user()->update([
            'presence' => PresenceStatus::Busy,
            'focus_until' => $until,
        ]);

        $this->dispatch('statusSwitcher-updated', status: PresenceStatus::Busy->value);
    }

    public function deactivateFocusMode(?string $restorePresence = null): void
    {
        $presence = PresenceStatus::tryFrom($restorePresence ?? '') ?? PresenceStatus::default();

        Auth::user()->update([
            'presence' => $presence,
            'focus_until' => null,
        ]);

        $this->dispatch('statusSwitcher-updated', status: $presence->value);
    }
}
