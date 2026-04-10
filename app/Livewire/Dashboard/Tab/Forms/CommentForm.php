<?php

namespace App\Livewire\Dashboard\Tab\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class CommentForm extends Form
{

    #[Validate('required|string|max:1000')]
    public string $content = '';

    public function validateComment(): void
    {
        $this->validate([
            'content' => 'required|string|max:1000',
        ], [
            'content.required' => 'لطفاً متن نظر را وارد کنید.',
            'content.string' => 'متن نظر باید رشته باشد.',
            'content.max' => 'متن نظر نباید بیشتر از 1000 کاراکتر باشد.',
        ]);
    }

    public function validated(): array
    {
        $this->validateComment();
        return $this->only(['content']);
    }

    protected function messages(): array
    {
        return [
            'content.required' => 'لطفاً متن نظر را وارد کنید.',
            'content.string' => 'متن نظر باید رشته باشد.',
            'content.max' => 'متن نظر نباید بیشتر از ۱۰۰۰ کاراکتر باشد.',
        ];
    }
}
