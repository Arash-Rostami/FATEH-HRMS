<?php
namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class VerifyNotice extends Component
{
    public function sendVerification()
    {
        if (Auth::user()->hasVerifiedEmail()) {
            return redirect()->intended(config('fortify.home', '/dashboard'));
        }

        Auth::user()->sendEmailVerificationNotification();
        session()->flash('status', 'verification-link-sent');
    }

    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->route('login');
    }

    public function render()
    {
        return view('livewire.auth.verify-notice')->layout('layouts.auth');
    }
}
