<?php
namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required|string|min:8',
    ];

    public function login()
    {
        $this->validate();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->addError('email','Invalid credentials.');
            return;
        }

        session()->regenerate();

        return redirect()->intended(config('fortify.home', '/home'));
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.app');
    }
}
