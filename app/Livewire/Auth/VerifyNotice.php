<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class VerifyNotice extends Component
{
    public function logout()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/');
    }

    public function render()
    {
        return view('livewire.auth.verify-notice')->layout('layouts.auth');
    }

    public function resend()
    {
        auth()->user()->sendEmailVerificationNotification();
        session()->flash('status', 'لینک تایید ارسال شد.');
    }
}
