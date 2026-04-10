<?php

namespace App\Livewire\Dashboard\Ths\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class RatingForm extends Form
{
    #[Validate('required|integer|min:1|max:5')]
    public ?int $score = null;

    #[Validate('nullable|string|max:1000')]
    public ?string $comment = null;

    protected function messages(): array
    {
        return [
            'score.required' => 'لطفا امتیاز رضایت را انتخاب کنید.',
            'score.integer' => 'امتیاز باید یک عدد صحیح باشد.',
            'score.min' => 'امتیاز باید حداقل ۱ باشد.',
            'score.max' => 'امتیاز نمی‌تواند بیشتر از ۵ باشد.',
            'comment.max' => 'توضیحات نمی‌تواند بیش از ۱۰۰۰ کاراکتر باشد.',
        ];
    }

    public function validated(): void
    {
        $this->validate();
    }
}
