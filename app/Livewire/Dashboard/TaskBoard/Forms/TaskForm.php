<?php

namespace App\Livewire\Dashboard\TaskBoard\Forms;

use App\Enums\TaskPriority;
use App\Enums\TaskState;
use Closure;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Validate;
use Livewire\Form;

class TaskForm extends Form
{

    #[Validate('nullable|numeric')]
    public ?string $deadlineYear = null;

    #[Validate('nullable|numeric|min:1|max:12')]
    public ?string $deadlineMonth = null;

    #[Validate('nullable|numeric|min:1|max:31')]
    public ?string $deadlineDay = null;

    #[Validate('required|string|max:255')]
    public string $newTitle = '';

    #[Validate('nullable|string|max:5000')]
    public ?string $newDescription = null;

    #[Validate('nullable|string|max:10')]
    public ?string $departmentId = null;

    #[Validate('nullable|integer|exists:projects,id')]
    public ?int $projectId = null;

    public ?string $pendingProjectName = null;
    public array $pendingProjectMemberIds = [];
    public array $pendingProjectDepartments = [];

    #[Validate(['labels' => 'array|max:10', 'labels.*' => 'string|max:30'])]
    public array $labels = [];

    #[Validate(['nullable', new Enum(TaskPriority::class)])]
    public ?string $priority = null;

    #[Validate(['checklist' => 'array', 'checklist.*.text' => 'string|max:255', 'checklist.*.done' => 'boolean', 'checklist.*.weight' => 'integer|min:0|max:100'])]
    public array $checklist = [];

    public array $meta = [];

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
    public ?int $detailUpdatedAt = null;
    public ?int $taskUpdatedAt = null;
    public ?string $taskVersion = null;

    #[Validate('nullable|integer|exists:users,id')]
    public ?int $selectedAssignee = null;

    protected array $validationAttributes = [
        'newTitle' => 'عنوان',
        'newDescription' => 'توضیحات',
        'deadlineYear' => 'سال سررسید',
        'deadlineMonth' => 'ماه سررسید',
        'deadlineDay' => 'روز سررسید',
        'departmentId' => 'بخش سازمانی',
        'projectId' => 'پروژهٔ مرتبط',
        'labels' => 'برچسب‌ها',
        'priority' => 'اولویت',
        'checklist' => 'چک‌لیست',
        'unit' => 'واحد (زیرمجموعه)',
        'section' => 'بخش (زیرمجموعه)',
        'project' => 'نام نمایشی پروژه',
        'scheme' => 'طرح',
        'actionSourceDomain' => 'دستهٔ منبع درخواست',
        'actionSource' => 'منبع درخواست',
        'collaborators' => 'همکاران',
        'collaborators.*' => 'همکاران',
        'responsibleUserId' => 'مسئول پیگیری',
        'state' => 'نتیجهٔ کار',
        'meta' => 'اطلاعات دلخواه',
        'meta.*' => 'متن اطلاعات دلخواه',
        'checklist.*.text' => 'متن مرحله',
        'checklist.*.weight' => 'سهم مرحله از پیشرفت',
        'attachments' => 'پیوست‌ها',
        'attachments.*' => 'فایل پیوست',
        'selectedAssignee' => 'مسئول انجام',
    ];

    public function validateAttachments(): void
    {
        $this->validate([
            'attachments' => [
                'array',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (count($this->existingAttachments) + count($this->attachments) > 5) {
                        $fail(__('resources/task/strings.validation.attachments.max_items'));
                    }
                },
            ],
            'attachments.*' => 'file|max:' . config('file_upload_limits.document') . '|mimes:jpg,jpeg,png,gif,bmp,webp,svg,pdf,doc,docx,xls,xlsx',
        ], [
            'attachments.*.max' => __('resources/task/strings.validation.attachments.max_size'),
            'attachments.*.mimes' => __('resources/task/strings.validation.attachments.mime_types'),
        ]);
    }

    public function rules(): array
    {
        return [
            'meta' => [
                'array',
                function (string $attribute, mixed $value, Closure $fail) {
                    foreach (array_keys($value ?? []) as $key) {
                        if (!preg_match('/^[a-z0-9_]+$/', (string) $key)) {
                            $fail('نام فیلد را با حروف کوچک انگلیسی، رقم و زیرخط (_) بنویسید؛ مثلاً tracking_code.');
                        }
                    }
                },
            ],
            'meta.*' => 'nullable|string|max:1000',
        ];
    }

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
            'checklist' => $this->checklist,
            'meta' => $this->meta,
        ];
    }

    protected function messages(): array
    {
        return [
            'newTitle.required' => 'عنوان وظیفه را بنویسید.',
            'newTitle.max' => 'عنوان نباید بیش از :max کاراکتر باشد.',
            'deadlineMonth.min' => 'ماه باید بین ۱ تا ۱۲ باشد.',
            'deadlineMonth.max' => 'ماه باید بین ۱ تا ۱۲ باشد.',
            'deadlineDay.min' => 'روز باید بین ۱ تا ۳۱ باشد.',
            'deadlineDay.max' => 'روز باید بین ۱ تا ۳۱ باشد.',
            'actionSourceDomain.max' => 'دستهٔ منبع درخواست نباید بیش از ۲۰۰۰ کاراکتر باشد.',
            'actionSource.max' => 'منبع درخواست نباید بیش از ۲۰۰۰ کاراکتر باشد.',
            'responsibleUserId.exists' => 'مسئول پیگیری معتبر نیست؛ فرد دیگری انتخاب کنید.',
            'state.enum' => 'یکی از نتیجه‌های کار را از فهرست انتخاب کنید.',
            'priority.enum' => 'اولویت انتخاب‌شده معتبر نیست.',
            'collaborators.*.exists' => 'یکی از همکاران انتخاب‌شده معتبر نیست.',
            'selectedAssignee.exists' => 'مسئول انجام انتخاب‌شده معتبر نیست.',
        ];
    }
}
