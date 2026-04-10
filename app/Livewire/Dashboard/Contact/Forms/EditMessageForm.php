<?php

namespace App\Livewire\Dashboard\Contact\Forms;

use Livewire\Form;
use Livewire\Attributes\Validate;

class EditMessageForm extends Form
{
    #[Validate('required|string|min:1|max:2000')]
    public string $editingBody = '';

    protected function messages(): array
    {
        return [
            'editingBody.required' => 'وارد کردن متن پیام الزامی است.',
            'editingBody.min' => 'متن پیام نباید خالی باشد.',
            'editingBody.max' => 'متن پیام نباید بیشتر از ۲۰۰۰ کاراکتر باشد.',
        ];
    }
}
