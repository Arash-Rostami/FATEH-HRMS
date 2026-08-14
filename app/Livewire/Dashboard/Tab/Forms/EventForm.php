<?php

namespace App\Livewire\Dashboard\Tab\Forms;

use App\Models\Event;
use Closure;
use Livewire\Form;
use Morilog\Jalali\CalendarUtils;

class EventForm extends Form
{
    public string $title = '';

    public string $description = '';

    public string $dateYear = '';

    public string $dateMonth = '';

    public string $dateDay = '';

    public string $time = '12:00';

    public bool $private = false;

    public ?int $remindHours = null;

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

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'dateYear' => ['required', 'numeric', 'min:1300', 'max:1500'],
            'dateMonth' => ['required', 'numeric', 'min:1', 'max:12'],
            'dateDay' => [
                'required',
                'numeric',
                'min:1',
                'max:31',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value === '' || $value === null) {
                        return;
                    }
                    $year = (int) $this->dateYear;
                    $month = (int) $this->dateMonth;
                    if ($year < 1300 || $year > 1500 || $month < 1 || $month > 12) {
                        return;
                    }
                    if (!CalendarUtils::checkDate($year, $month, (int) $value, true)) {
                        $fail('روز نامعتبر است');
                    }
                },
            ],
            'time' => ['required', 'date_format:H:i'],
            'private' => ['boolean'],
            'remindHours' => ['nullable', 'integer', 'in:' . implode(',', Event::REMIND_HOURS_OPTIONS)],
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required' => 'عنوان رویداد الزامی است',
            'dateYear.required' => 'تاریخ الزامی است',
            'dateYear.min' => 'سال نامعتبر است',
            'dateYear.max' => 'سال نامعتبر است',
            'dateMonth.required' => 'تاریخ الزامی است',
            'dateMonth.min' => 'ماه نامعتبر است',
            'dateMonth.max' => 'ماه نامعتبر است',
            'dateDay.required' => 'تاریخ الزامی است',
            'dateDay.min' => 'روز نامعتبر است',
            'dateDay.max' => 'روز نامعتبر است',
            'time.required' => 'زمان الزامی است',
            'time.date_format' => 'زمان را به‌صورت ساعت:دقیقه وارد کنید',
            'description.string' => 'توضیحات باید متن باشد',
            'remindHours.in' => 'مقدار یادآوری نامعتبر است',
            'description.max' => 'توضیحات نمی‌تواند بیشتر از ۱۰۰۰ کاراکتر باشد',
            'private.boolean' => 'مقدار حریم خصوصی نامعتبر است',
        ];
    }
}