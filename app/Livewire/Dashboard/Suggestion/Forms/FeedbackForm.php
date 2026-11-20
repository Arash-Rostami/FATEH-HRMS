<?php

namespace App\Livewire\Dashboard\Suggestion\Forms;

use Livewire\Form;

class FeedbackForm extends Form
{
    public string $feedback = '';
    public string $comment = '';

    public function rules(): array
    {
        return ['feedback' => 'required|in:agree,neutral,disagree'];
    }

    public function messages(): array
    {
        return [
            'feedback.required' => 'نظر واحدتان را انتخاب کنید.',
            'feedback.in' => 'نظر واحدتان را از گزینه‌های فرم انتخاب کنید.',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'feedback' => 'نظر واحد',
            'comment' => 'توضیح نظر',
        ];
    }

    public function validated(): array
    {
        return $this->validate();
    }
}
