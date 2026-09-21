<?php

namespace App\Livewire\Dashboard\Navbar;

use App\Traits\HasFocusMode;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class QuickSettings extends Component
{
    use HasFocusMode;

    public function render()
    {
        $user = Auth::user();

        return view('livewire.dashboard.navbar.top.quick-settings', [
            'presence' => $user->presence,
            'focusUntil' => $user->focus_until,
        ]);
    }
}
