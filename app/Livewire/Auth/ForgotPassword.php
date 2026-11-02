<?php
namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use App\Traits\AuthValidationRules;

class ForgotPassword extends Component
{
    use AuthValidationRules;

    public string $email = '';

    public bool $sent = false;

    public function send()
    {
        if ($this->sent) {
            return;
        }

        $this->validate(
            [
                'email' => $this->emailRules(),
            ],
            $this->validationMessages()
        );

        $key = 'forgot:' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', 'تعداد درخواست‌ها زیاد است؛ لطفاً کمی بعد دوباره تلاش کنید.');

            return;
        }

        RateLimiter::hit($key, 60);

        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status === Password::RESET_LINK_SENT) {
            $this->sent = true;
        } else {
            $this->addError('email', __($status));
        }
    }

    public function render()
    {
        return view('livewire.auth.forgot-password')
            ->layout('layouts.auth');
    }
}
