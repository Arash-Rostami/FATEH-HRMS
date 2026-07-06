<?php

namespace App\Livewire\Dashboard\Channel\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class EditChannelMessageForm extends Form
{
    #[Validate('required|string|min:1|max:4000')]
    public string $editingBody = '';

    protected function messages(): array
    {
        return [
            'editingBody.required' => 'وارد کردن متن پیام الزامی است.',
            'editingBody.min' => 'متن پیام نباید خالی باشد.',
            'editingBody.max' => 'متن پیام نباید بیشتر از ۴۰۰۰ کاراکتر باشد.',
        ];
    }
}