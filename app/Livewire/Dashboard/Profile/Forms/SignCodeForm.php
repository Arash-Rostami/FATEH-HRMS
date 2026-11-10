<?php

namespace App\Livewire\Dashboard\Profile\Forms;

use Closure;
use Livewire\Form;

class SignCodeForm extends Form
{
    public const CODE_BYTES = 72;

    public string $current_password = '';

    public string $code = '';

    protected function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'code' => [
                'required',
                'string',
                'min:' . (int) config('ai.correspondence.sign_code_min_length', 6),
                'max:72',
                fn (string $attribute, mixed $value, Closure $fail) => strlen($value) > self::CODE_BYTES
                    && $fail(__('resources/correspondence/strings.profile.sign_code_bytes')),
            ],
        ];
    }
}
