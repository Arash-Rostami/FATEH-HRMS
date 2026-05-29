<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Traits\AuthValidationRules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

class Register extends Component
{
    use AuthValidationRules;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register()
    {
        $this->validate(
            [
                'name' => 'required|string|max:255',
                'email' => $this->emailRules(true),
                'password' => $this->passwordRules(true),
                'password_confirmation' => 'required|same:password',
            ],
            $this->validationMessages()
        );

        $key = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->addError('email', 'تعداد تلاش‌های ثبت‌نام بیش از حد مجاز است. لطفاً ' . RateLimiter::availableIn($key) . ' ثانیه دیگر تلاش کنید.');
            return;
        }

        RateLimiter::hit($key, 300);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
        ]);

        RateLimiter::clear($key);
        Auth::login($user);
        session()->regenerate();

        return redirect(config('fortify.home', '/dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.register')->layout('layouts.auth');
    }

    private function throttleKey(): string
    {
        return 'register:' . Str::lower($this->email) . '|' . request()->ip();
    }
}
