<?php

namespace App\Livewire\Dashboard\Navbar;

use App\Enums\PresenceStatus;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class StatusSwitcher extends Component
{
    public string $status = PresenceStatus::Onsite->value;

    public function changeStatus(string $val): void
    {
        $statusEnum = PresenceStatus::tryFrom($val);
        if (!$statusEnum) return;

        $user = Auth::user();
        $user->update(['presence' => $statusEnum]);
        $this->status = $statusEnum->value;
        $this->dispatch('statusSwitcher-updated', status: $val);
    }

    public function mount(): void
    {
        $current = Auth::user()->presence;
        $this->status = $current instanceof PresenceStatus ? $current->value : ($current ?? PresenceStatus::Onsite->value);
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
