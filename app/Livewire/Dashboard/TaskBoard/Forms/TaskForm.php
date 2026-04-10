<?php

namespace App\Livewire\Dashboard\TaskBoard\Forms;

use Carbon\Carbon;
use Exception;
use Livewire\Attributes\Validate;
use Livewire\Form;
use Morilog\Jalali\CalendarUtils;

class TaskForm extends Form
{
    #[Validate('required|string|max:255')]
    public string $newTitle = '';

    #[Validate('nullable|string')]
    public ?string $newDescription = null;

    #[Validate('nullable|integer')]
    public string $deadlineYear = '';

    #[Validate('nullable|integer|min:1|max:12')]
    public string $deadlineMonth = '';

    #[Validate('nullable|integer|min:1|max:31')]
    public string $deadlineDay = '';

    public $selectedAssignee = null;
    protected array $validationAttributes = [
        'newTitle' => 'عنوان',
        'newDescription' => 'توضیحات',
        'deadlineYear' => 'سال سررسید',
        'deadlineMonth' => 'ماه سررسید',
        'deadlineDay' => 'روز سررسید',
    ];

    /** @throws Exception */
    public function resolveDeadline(): ?Carbon
    {
        if (!$this->deadlineYear || !$this->deadlineMonth || !$this->deadlineDay) return null;

        return CalendarUtils::createCarbonFromFormat(
            'Y/m/d',
            sprintf('%s/%02d/%02d', $this->deadlineYear, $this->deadlineMonth, $this->deadlineDay)
        );
    }

    protected function messages(): array
    {
        return [
            'newTitle.required' => 'فیلد عنوان الزامی است.',
            'newTitle.max' => 'عنوان نباید بیش از :max کاراکتر باشد.',
            'deadlineMonth.min' => 'ماه باید بین 1 تا 12 باشد.',
            'deadlineMonth.max' => 'ماه باید بین 1 تا 12 باشد.',
            'deadlineDay.min' => 'روز باید بین 1 تا 31 باشد.',
            'deadlineDay.max' => 'روز باید بین 1 تا 31 باشد.',
        ];
    }
}
