<?php

namespace App\Livewire\Dashboard\Tab\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class EventForm extends Form
{
    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    #[Validate('required|numeric')]
    public string $dateYear = '';

    #[Validate('required|numeric|min:1|max:12')]
    public string $dateMonth = '';

    #[Validate('required|numeric|min:1|max:31')]
    public string $dateDay = '';

    #[Validate('required')]
    public string $time = '12:00';

    #[Validate('boolean')]
    public bool $private = false;

    public ?int $editingId = null;

    public function resetForm(string $defaultDate): void
    {
        $this->reset();
        $parts = explode('-', $defaultDate) + ['', '', ''];
        $this->dateYear  = $parts[0] !== '' ? (string)(int)$parts[0] : '';
        $this->dateMonth = $parts[1] !== '' ? (string)(int)$parts[1] : '';
        $this->dateDay   = $parts[2] !== '' ? (string)(int)$parts[2] : '';
        $this->time = '12:00';
    }

    public function validated(): array
    {
        return $this->validate();
    }

    protected function messages(): array
    {
        return [
            'title.required' => 'عنوان رویداد الزامی است',
            'dateYear.required' => 'تاریخ الزامی است',
            'dateMonth.required' => 'تاریخ الزامی است',
            'dateDay.required' => 'تاریخ الزامی است',
            'dateMonth.min' => 'ماه نامعتبر است',
            'dateMonth.max' => 'ماه نامعتبر است',
            'dateDay.min' => 'روز نامعتبر است',
            'dateDay.max' => 'روز نامعتبر است',
            'time.required' => 'زمان الزامی است',
            'description.string' => 'توضیحات باید متن باشد',
            'description.max' => 'توضیحات نمی‌تواند بیشتر از ۱۰۰۰ کاراکتر باشد',
            'private.boolean' => 'مقدار حریم خصوصی نامعتبر است'
        ];
    }
}
