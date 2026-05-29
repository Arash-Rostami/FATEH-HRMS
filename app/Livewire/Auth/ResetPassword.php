<?php

namespace App\Livewire\Auth;

use App\Traits\AuthValidationRules;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ResetPassword extends Component
{
    use AuthValidationRules;

    #[Locked]
    public string $token;
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount($token)
    {
        $this->token = $token;
        $this->email = request()->query('email', '');
    }

    public function render()
    {
        return view('livewire.auth.reset-password')->layout('layouts.auth');
    }

    public function submitReset()
    {
        $this->validate(
            [
                'token' => 'required',
                'email' => $this->emailRules(),
                'password' => $this->passwordRules(true),
            ],
            $this->validationMessages()
        );

        $status = Password::reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function ($user) {
                $user->forceFill([
                    'password' => $this->password,
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            session()->flash('statusSwitcher', 'رمز عبور با موفقیت تغییر کرد. اکنون می‌توانید وارد شوید.');
            return redirect()->route('login');
        }

        $this->addError('email', 'ایمیل یا توکن نامعتبر است.');
    }
}
