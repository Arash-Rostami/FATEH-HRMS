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

    public function validationAttributes(): array
    {
        return ['score' => 'امتیاز رضایت', 'comment' => 'توضیح ارزیابی'];
    }

    protected function messages(): array
    {
        return [
            'score.required' => 'برای ثبت ارزیابی، یک امتیاز انتخاب کنید.',
            'score.integer' => 'یکی از امتیازهای ۱ تا ۵ را انتخاب کنید.',
            'score.min' => 'کمترین امتیاز ۱ است.',
            'score.max' => 'بیشترین امتیاز ۵ است.',
            'comment.max' => 'توضیح را در حداکثر ۱۰۰۰ نویسه بنویسید.',
        ];
    }

    public function validated(): void
    {
        $this->validate();
    }
}
