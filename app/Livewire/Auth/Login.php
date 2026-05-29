<?php
namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Traits\AuthValidationRules;

class Login extends Component
{
    use AuthValidationRules;

    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    private function throttleKey(): string
    {
        return 'login:' . Str::lower($this->email) . '|' . request()->ip();
    }

    public function login()
    {
        $this->validate(
            ['email' => $this->emailRules(), 'password' => $this->passwordRules()],
            $this->validationMessages()
        );

        $key = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($key, 4)) {
            $this->addError('email', 'تعداد تلاش‌های ورود بیش از حد مجاز است. لطفاً ' . RateLimiter::availableIn($key) . ' ثانیه دیگر تلاش کنید.');
            return;
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($key);
            $this->reset('password');
            $this->addError('email', 'اطلاعات ورود اشتباه است یا حساب کاربری وجود ندارد.');
            return;
        }

        RateLimiter::clear($key);
        session()->regenerate();

        return redirect()->intended(config('fortify.home', '/dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.auth');
    }
}
