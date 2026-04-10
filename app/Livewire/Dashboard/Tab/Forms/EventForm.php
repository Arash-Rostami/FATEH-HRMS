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

    #[Validate('required|date_format:Y-m-d')]
    public string $date = '';

    #[Validate('required')]
    public string $time = '12:00';

    #[Validate('boolean')]
    public bool $private = false;

    public ?int $editingId = null;

    public function resetForm(string $defaultDate): void
    {
        $this->reset();
        $this->date = $defaultDate;
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
            'date.required' => 'تاریخ الزامی است',
            'date.date_format' => 'فرمت تاریخ صحیح نیست',
            'time.required' => 'زمان الزامی است',
            'description.string' => 'توضیحات باید متن باشد',
            'description.max' => 'توضیحات نمی‌تواند بیشتر از ۱۰۰۰ کاراکتر باشد',
            'private.boolean' => 'مقدار حریم خصوصی نامعتبر است'
        ];
    }
}
