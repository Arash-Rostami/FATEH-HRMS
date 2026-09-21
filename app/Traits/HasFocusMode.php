<?php

namespace App\Traits;

use App\Enums\PresenceStatus;
use Illuminate\Support\Facades\Auth;

trait HasFocusMode
{
    private const MAX_FOCUS_MINUTES = 240;

    public function activateFocusMode(?int $minutes = null): void
    {
        $until = $minutes ? now()->addMinutes(min(max($minutes, 1), self::MAX_FOCUS_MINUTES)) : null;

        Auth::user()->update([
            'presence' => PresenceStatus::Busy,
            'focus_until' => $until,
        ]);

        $this->dispatch('statusSwitcher-updated', status: PresenceStatus::Busy->value);
    }

    public function deactivateFocusMode(?string $restorePresence = null): void
    {
        $presence = PresenceStatus::tryFrom($restorePresence ?? '') ?? PresenceStatus::Onsite;

        Auth::user()->update([
            'presence' => $presence,
            'focus_until' => null,
        ]);

        $this->dispatch('statusSwitcher-updated', status: $presence->value);
    }
}
