<?php
namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuthValidationRules;

class Login extends Component
{
    use AuthValidationRules;

    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function login()
    {
        $this->validate(
            [
                'email' => $this->emailRules(),
                'password' => $this->passwordRules(),
            ],
            $this->validationMessages()
        );

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->addError('email', 'اطلاعات ورود اشتباه است یا حساب کاربری وجود ندارد.');
            return;
        }

        session()->regenerate();

        return redirect()->intended(config('fortify.home', '/dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.auth');
    }
}
