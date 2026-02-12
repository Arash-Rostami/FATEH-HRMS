<?php
namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\URL;

class VerifyNotice extends Component
{
    public function resend()
    {
        auth()->user()->sendEmailVerificationNotification();
        session()->flash('status','Verification link sent.');
    }

    public function render()
    {
        return view('livewire.auth.verify-notice')->layout('layouts.app');
    }
}
