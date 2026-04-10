<?php

namespace App\Livewire\Dashboard\Suggestion\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class FeedbackForm extends Form
{
    #[Validate('required|in:agree,neutral,disagree')]
    public string $feedback = '';
    public string $comment = '';

    public function messages(): array
    {
        return [
            'feedback.required' => 'بازخورد معتبر انتخاب کنید.',
            'feedback.in' => 'بازخورد معتبر انتخاب کنید.',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'feedback' => 'بازخورد',
        ];
    }

    public function validated(): array
    {
        return $this->validate();
    }
}
