<?php

namespace App\Livewire\Dashboard\TaskBoard\Forms;

use App\Filament\Resources\TaskResource\Enums\TaskState;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Validate;
use Livewire\Form;

class TaskForm extends Form
{

    #[Validate('nullable|numeric')]
    public string $deadlineYear = '';

    #[Validate('nullable|numeric|min:1|max:12')]
    public string $deadlineMonth = '';

    #[Validate('nullable|numeric|min:1|max:31')]
    public string $deadlineDay = '';
    #[Validate('required|string|max:255')]
    public string $newTitle = '';

    #[Validate('nullable|string')]
    public ?string $newDescription = null;

    #[Validate('nullable|string|max:10')]
    public ?string $departmentId = null;

    #[Validate('nullable|string|max:255')]
    public ?string $unit = null;

    #[Validate('nullable|string|max:255')]
    public ?string $section = null;

    #[Validate('nullable|string|max:255')]
    public ?string $project = null;

    #[Validate('nullable|string|max:255')]
    public ?string $scheme = null;

    #[Validate('nullable|string|max:2000')]
    public ?string $actionSourceDomain = null;

    #[Validate('nullable|string|max:2000')]
    public ?string $actionSource = null;

    #[Validate(['collaborators' => 'array', 'collaborators.*' => 'exists:users,id'])]
    public array $collaborators = [];

    #[Validate('nullable|integer|exists:users,id')]
    public ?int $responsibleUserId = null;

    #[Validate(['nullable', new Enum(TaskState::class)])]
    public ?string $state = null;

    public array $attachments = [];
    public array $existingAttachments = [];

    #[Validate('nullable|integer|exists:users,id')]
    public $selectedAssignee = null;
    protected array $validationAttributes = [
        'newTitle' => 'عنوان',
        'newDescription' => 'توضیحات',
        'deadlineYear' => 'سال سررسید',
        'deadlineMonth' => 'ماه سررسید',
        'deadlineDay' => 'روز سررسید',
        'departmentId' => 'واحد سازمانی/دپارتمان',
        'unit' => 'واحد (زیرمجموعه)',
        'section' => 'بخش (زیرمجموعه)',
        'project' => 'پروژه',
        'scheme' => 'طرح',
        'actionSourceDomain' => 'حوزه منشاء اقدام',
        'actionSource' => 'منشاء اقدام',
        'collaborators' => 'همکاران',
        'collaborators.*' => 'همکاران',
        'responsibleUserId' => 'جوابگو',
        'state' => 'تعیین تکلیف',
        'selectedAssignee' => 'مسئول انجام',
    ];

    public function detailAttributes(): array
    {
        return [
            'department_id' => $this->departmentId,
            'unit' => $this->unit,
            'section' => $this->section,
            'project' => $this->project,
            'scheme' => $this->scheme,
            'action_source_domain' => $this->actionSourceDomain,
            'action_source' => $this->actionSource,
            'collaborators' => $this->collaborators,
            'responsible_user_id' => $this->responsibleUserId,
            'state' => $this->state,
        ];
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
            'actionSourceDomain.max' => 'حوزه منشاء اقدام نباید بیش از ۲۰۰۰ کاراکتر باشد.',
            'actionSource.max' => 'منشاء اقدام نباید بیش از ۲۰۰۰ کاراکتر باشد.',
            'responsibleUserId.exists' => 'کاربر جوابگوی انتخاب‌شده معتبر نیست.',
            'state.enum' => 'تعیین تکلیف انتخاب‌شده معتبر نیست.',
            'collaborators.*.exists' => 'یکی از همکاران انتخاب‌شده معتبر نیست.',
            'selectedAssignee.exists' => 'مسئول انجام انتخاب‌شده معتبر نیست.',
        ];
    }
}
