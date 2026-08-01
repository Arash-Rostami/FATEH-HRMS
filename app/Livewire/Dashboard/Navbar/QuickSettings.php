<?php

namespace App\Livewire\Dashboard\Navbar;

use App\Enums\PresenceStatus;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class QuickSettings extends Component
{
    public function setFocusMode(bool $enabled): void
    {
        $presence = $enabled ? PresenceStatus::Busy : PresenceStatus::Onsite;
        Auth::user()->update(['presence' => $presence]);
        $this->dispatch('statusSwitcher-updated', status: $presence->value);
    }

    public function render()
    {
        return view('livewire.dashboard.navbar.top.quick-settings');
    }
}
