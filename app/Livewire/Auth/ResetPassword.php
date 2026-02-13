<?php

namespace App\Livewire\Auth;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Component;

class ResetPassword extends Component
{
    public string $token;
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required|confirmed|min:8',
    ];

    protected $messages = [
        'email.required' => 'ایمیل لازم است.',
        'email.email'    => 'ایمیل نامعتبر است.',
        'password.required' => 'رمز لازم است.',
        'password.min'      => 'رمز حداقل 8 حرف باشد.',
        'password.confirmed'=> 'رمزها مطابقت ندارند.',
    ];

    public function mount($token)
    {
        $this->token = $token;
    }

    public function submitReset()
    {
        $this->validate();

        $status = Password::reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function ($user) {
                $user->forceFill([
                    'password' => Hash::make($this->password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            session()->flash('status', 'رمز با موفقیت تغییر کرد. اکنون می‌توانید وارد شوید.');
            return redirect()->route('login');
        }

        $this->addError('email', 'ایمیل یا توکن نامعتبر است.');
    }

    public function render()
    {
        return view('livewire.auth.reset-password')->layout('layouts.auth');
    }
}
