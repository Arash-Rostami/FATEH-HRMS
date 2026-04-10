<?php

namespace App\Livewire\Dashboard\Contact\Forms;

use Livewire\Form;
use Livewire\Attributes\Validate;

class MessageComposerForm extends Form
{
    #[Validate('required|string|min:1|max:2000')]
    public string $body = '';

    protected function messages(): array
    {
        return [
            'body.required' => 'وارد کردن متن پیام الزامی است.',
            'body.min' => 'متن پیام نباید خالی باشد.',
            'body.max' => 'متن پیام نباید بیشتر از ۲۰۰۰ کاراکتر باشد.',
        ];
    }
}
