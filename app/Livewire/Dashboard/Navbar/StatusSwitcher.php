<?php

namespace App\Livewire\Dashboard\Navbar;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;

class StatusSwitcher extends Component
{
    const STATUSES = ['onsite', 'busy', 'remote', 'mission'];
    public string $status = 'onsite';

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

    public function mount(): void
    {
        $current = Auth::user()->presence;
        $this->status = in_array($current, self::STATUSES) ? $current : 'onsite';
    }

    #[On('statusSwitcher-updated')]
    public function updatedFromEvent(string $status): void
    {
        $this->status = $status;
    }
}
