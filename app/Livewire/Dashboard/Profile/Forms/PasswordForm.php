<?php

namespace App\Livewire\Dashboard\Profile\Forms;

use App\Actions\Fortify\PasswordValidationRules;
use Livewire\Form;

class PasswordForm extends Form
{
    use PasswordValidationRules;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => $this->passwordRules(),
        ];
    }
}
