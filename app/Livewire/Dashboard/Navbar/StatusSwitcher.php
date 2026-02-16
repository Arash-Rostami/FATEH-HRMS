<?php

namespace App\Livewire\Dashboard\Navbar;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class StatusSwitcher extends Component
{
    public string $status = 'onsite';

    const STATUSES = ['onsite', 'busy', 'offline', 'mission'];

    public function mount(): void
    {
        $current = Auth::user()->presence;
        $this->status = in_array($current, self::STATUSES) ? $current : 'onsite';
    }

    public function changeStatus(string $val): void
    {
        if (!in_array($val, self::STATUSES)) {
            return;
        }

        $user = Auth::user();
        $key = "idle_{$user->id}";

        match ($val) {
            'busy' => Cache::add($key, true, now()->addHours(8)),
            default => Cache::forget($key),
        };

        $user->update(['presence' => $val]);

        $this->status = $val;

        $this->dispatch('statusSwitcher-updated', status: $val);
    }
}
