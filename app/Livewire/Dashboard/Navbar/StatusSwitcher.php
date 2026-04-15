<?php

namespace App\Livewire\Dashboard\Navbar;

use App\Enums\PresenceStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;

class StatusSwitcher extends Component
{
    public string $status = 'onsite';

    public function changeStatus(string $val): void
    {
        $statusEnum = PresenceStatus::tryFrom($val);
        if (!$statusEnum) return;

        $user = Auth::user();
        $key = "idle_{$user->id}";

        $statusEnum === PresenceStatus::Busy
            ? Cache::add($key, true, now()->addHours(8))
            : Cache::forget($key);

        $user->update(['presence' => $statusEnum]);
        $this->status = $statusEnum->value;
        $this->dispatch('statusSwitcher-updated', status: $val);
    }

    public function mount(): void
    {
        $current = Auth::user()->presence;
        $this->status = $current instanceof PresenceStatus ? $current->value : ($current ?? 'onsite');
    }

    public function render()
    {
        return view('livewire.dashboard.navbar.top.status-switcher');
    }

    #[On('statusSwitcher-updated')]
    public function updatedFromEvent(string $status): void
    {
        $this->status = $status;
    }
}
