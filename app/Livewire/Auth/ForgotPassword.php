<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Livewire\Component;

class ForgotPassword extends Component
{
    public string $email = '';

    protected $rules = ['email' => 'required|email'];

    protected $messages = [
        'email.required' => 'ایمیل لازم است.',
        'email.email'    => 'ایمیل نامعتبر است.',
    ];

    public function render()
    {
        return view('livewire.auth.forgot-password')->layout('layouts.auth');
    }

    public function send()
    {
        $this->validate();

        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status === Password::RESET_LINK_SENT) {
            session()->flash('status', 'لینک بازیابی رمز به ایمیل فرستاده شد.');
        } else {
            $this->addError('email', 'ایمیل یافت نشد.');
        }
    }
}
