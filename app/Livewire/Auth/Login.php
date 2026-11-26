<?php
namespace App\Livewire\Auth;

use App\Livewire\Auth\Actions\PasswordLoginAction;
use App\Livewire\Auth\Actions\SendLoginCodeAction;
use App\Livewire\Auth\Actions\VerifyLoginCodeAction;
use App\Services\Notify\Channels\SmsChannel;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Traits\Livewire\AuthValidationRules;

class Login extends Component
{
    use AuthValidationRules;

    public string $email = '';
    public string $password = '';
    public bool $remember = false;
    public string $outcome = '';
    public bool $smsLogin = false;
    public string $phone = '';
    public string $code = '';
    public bool $codeSent = false;

    public function login(PasswordLoginAction $action)
    {
        $this->validate(
            ['email' => $this->emailRules(), 'password' => $this->passwordRules()],
            $this->validationMessages()
        );

        $error = $action->execute($this->email, $this->password, $this->remember, request()->ip());

        if ($error) {
            $this->reset('password');
            $this->addError('email', $error);
            $this->outcome = 'fail';
            return;
        }

        $this->enter();
    }

    public function updatedSmsLogin(): void
    {
        $this->resetErrorBag();
        $this->reset('phone', 'code', 'codeSent', 'outcome');
        $this->smsLogin = $this->smsLogin && SendLoginCodeAction::available();
    }

    public function sendCode(SendLoginCodeAction $action, SmsChannel $sms): void
    {
        $this->phone = $this->latinDigits($this->phone);
        $this->validate(['phone' => $this->phoneRules()], $this->validationMessages());

        if ($error = $action->execute($this->phone, request()->ip(), $sms)) {
            $this->addError('phone', $error);
            $this->outcome = 'fail';
            return;
        }

        $this->reset('code');
        $this->codeSent = true;
        $this->dispatch('login-code-sent', seconds: SendLoginCodeAction::COOLDOWN);
    }

    public function verifyCode(VerifyLoginCodeAction $action, SmsChannel $sms): void
    {
        $this->code = $this->latinDigits($this->code);
        $this->validate(['code' => $this->codeRules()], $this->validationMessages());

        if (! $user = $action->execute($this->phone, $this->code, request()->ip(), $sms)) {
            $this->reset('code');
            $this->addError('code', 'کد نادرست یا منقضی شده است.');
            $this->outcome = 'fail';
            return;
        }

        Auth::login($user, $this->remember);
        $this->enter();
    }

    private function enter(): void
    {
        session()->regenerate();

        $this->outcome = 'success';
        $this->js('setTimeout(() => window.location.href = ' . json_encode(url($this->destination())) . ', 800)');
    }

    private function destination(): string
    {
        $home = config('fortify.home', '/dashboard');
        $intended = session()->pull('url.intended');

        if (! is_string($intended) || rtrim((string) parse_url($intended, PHP_URL_PATH), '/') === '/launchpad') {
            return $home;
        }

        return $intended;
    }

    private function latinDigits(string $value): string
    {
        return strtr($value, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
    }

    public function render()
    {
        return view('livewire.auth.login', ['otpOn' => SendLoginCodeAction::available()])->layout('layouts.auth');
    }
}
