<?php

namespace App\Livewire\Dashboard\Suggestion\Forms;

use Livewire\Form;

class DecisionForm extends Form
{
    public string $decision = '';
    public string $decisionComment = '';
    public string $referralActions = '';
    public array $referralDepts = [];

    public function rules(): array
    {
        return ['decision' => 'required|in:accepted,rejected,under_review'];
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'پذیرش، رد یا نیاز به تکمیل را انتخاب کنید.',
            'decision.in' => 'تصمیم را از گزینه‌های فرم انتخاب کنید.',
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
            'decisionComment' => 'توضیح تصمیم',
            'referralDepts' => 'واحدهای مسئول اجرا',
            'referralActions' => 'کارهای لازم برای اجرا',
        ];
    }
}
