<?php
namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Password;
use App\Traits\AuthValidationRules;

class ForgotPassword extends Component
{
    use AuthValidationRules;

    public string $email = '';

    public function send()
    {
        $this->validate(
            [
                'email' => $this->emailRules(),
            ],
            $this->validationMessages()
        );

        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status === Password::RESET_LINK_SENT) {
            session()->flash('status', 'لینک بازیابی رمز عبور به ایمیل شما ارسال شد.');
        } else {
            $this->addError('email', 'ایمیل یافت نشد.');
        }
    }

    public function render()
    {
        return view('livewire.auth.forgot-password')
            ->layout('layouts.auth');
    }
}
