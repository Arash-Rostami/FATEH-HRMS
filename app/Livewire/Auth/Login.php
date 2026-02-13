<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required|string|min:8',
    ];

    protected $messages = [
        'email.required' => 'ایمیل لازم است.',
        'email.email' => 'ایمیل نامعتبر است.',
        'password.required' => 'رمز لازم است.',
        'password.min' => 'رمز حداقل 8 حرف باشد.',
    ];

    public function login()
    {
        $this->validate();

        if (!Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->addError('email', 'ایمیل یا رمز اشتباه است.');
            return null;
        }

        session()->regenerate();

        return redirect()->intended(config('fortify.home', '/home'));
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.auth');
    }
}
