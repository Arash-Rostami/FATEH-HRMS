<?php
namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class LogoutButton extends Component
{
    public $iconOnly = false;

    public function logout()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/login');
    }

    public function render()
    {
        return view('livewire.auth.logout-button');
    }
}
