<?php

namespace App\Livewire\Dashboard\Navbar;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class QuickSettings extends Component
{
    public function enableFocusMode()
    {
        $user = Auth::user();
        $user->update(['presence' => 'busy']);
        $this->dispatch('statusSwitcher-updated', status: 'busy');
    }

    public function disableFocusMode()
    {
        $user = Auth::user();
        $user->update(['presence' => 'onsite']);
        $this->dispatch('statusSwitcher-updated', status: 'onsite');
    }

    public function render()
    {
        return view('livewire.dashboard.navbar.quick-settings');
    }
}
