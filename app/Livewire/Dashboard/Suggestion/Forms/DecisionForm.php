<?php

namespace App\Livewire\Dashboard\Suggestion\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class DecisionForm extends Form
{
    #[Validate('required|in:accepted,rejected,under_review')]
    public string $decision = '';
    public string $decisionComment = '';
    public string $referralActions = '';
    public array $referralDepts = [];

    public function messages(): array
    {
        return [
            'decision.required' => 'تصمیم معتبر انتخاب کنید.',
            'decision.in' => 'تصمیم معتبر انتخاب کنید.',
        ];
    }

    public function validated(): array
    {
        return $this->validate();
    }

    public function validationAttributes(): array
    {
        return [
            'decision' => 'تصمیم',
        ];
    }
}
