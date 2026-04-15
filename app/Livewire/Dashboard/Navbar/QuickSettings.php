<?php

namespace App\Livewire\Dashboard\Navbar;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class QuickSettings extends Component
{
    public function setFocusMode(bool $enabled): void
    {
        $presence = $enabled ? 'busy' : 'onsite';
        Auth::user()->update(['presence' => $presence]);
        $this->dispatch('statusSwitcher-updated', status: $presence);
    }

    public function render()
    {
        return view('livewire.dashboard.navbar.top.quick-settings');
    }
}
