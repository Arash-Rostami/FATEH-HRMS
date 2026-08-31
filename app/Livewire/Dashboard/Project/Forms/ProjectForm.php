<?php

namespace App\Livewire\Dashboard\Project\Forms;

use App\Models\Project;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ProjectForm extends Form
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate(['memberIds' => 'array', 'memberIds.*' => 'integer|exists:users,id'])]
    public array $memberIds = [];

    #[Validate(['departments' => 'array', 'departments.*' => 'string|exists:departments,code'])]
    public array $departments = [];

    #[Validate('boolean')]
    public bool $requiresApproval = false;

    #[Validate('nullable|integer|min:1')]
    public ?int $slaHours = null;

    #[Validate('nullable|numeric')]
    public string $deadlineYear = '';

    #[Validate('nullable|numeric|min:1|max:12')]
    public string $deadlineMonth = '';

    #[Validate('nullable|numeric|min:1|max:31')]
    public string $deadlineDay = '';

    #[Validate(['customSchema' => 'array', 'customSchema.*.key' => 'required|string|regex:/^[a-z0-9_]+$/|max:40|distinct', 'customSchema.*.label' => 'required|string|max:80'])]
    public array $customSchema = [];

    public array $extraSettings = [];

    public ?int $updatedAt = null;

    protected function rules(): array
    {
        return [
            'extraSettings' => 'array',
            'extraSettings.*.key' => ['required', 'string', 'regex:/^[a-z0-9_]+$/', 'max:40', 'distinct', Rule::notIn(Project::KNOWN_SETTING_KEYS)],
            'extraSettings.*.value' => 'nullable|string|max:500',
        ];
    }

    protected array $validationAttributes = [
        'name' => 'نام پروژه',
        'memberIds' => 'اعضا',
        'departments' => 'دپارتمان‌ها',
        'requiresApproval' => 'تأیید مدیر پروژه',
        'slaHours' => 'ساعت SLA',
        'deadlineYear' => 'سال مهلت پروژه',
        'deadlineMonth' => 'ماه مهلت پروژه',
        'deadlineDay' => 'روز مهلت پروژه',
        'customSchema' => 'متای سفارشی',
        'customSchema.*.key' => 'کلید متای سفارشی',
        'customSchema.*.label' => 'برچسب متای سفارشی',
        'extraSettings' => 'تنظیمات دیگر',
        'extraSettings.*.key' => 'کلید تنظیم',
        'extraSettings.*.value' => 'مقدار تنظیم',
    ];

    protected function messages(): array
    {
        return [
            'name.required' => 'وارد کردن نام پروژه الزامی است.',
            'name.max' => 'نام پروژه نباید بیش از ۲۵۵ کاراکتر باشد.',
            'deadlineMonth.min' => 'ماه باید بین 1 تا 12 باشد.',
            'deadlineMonth.max' => 'ماه باید بین 1 تا 12 باشد.',
            'deadlineDay.min' => 'روز باید بین 1 تا 31 باشد.',
            'deadlineDay.max' => 'روز باید بین 1 تا 31 باشد.',
            'slaHours.min' => 'ساعت SLA باید حداقل 1 باشد.',
            'customSchema.*.key.regex' => 'کلید متای سفارشی فقط می‌تواند شامل حروف کوچک لاتین، عدد و زیرخط باشد.',
            'customSchema.*.key.distinct' => 'کلید متای سفارشی نباید تکراری باشد.',
            'customSchema.*.key.max' => 'کلید متای سفارشی نباید بیش از :max کاراکتر باشد.',
            'customSchema.*.label.max' => 'برچسب متای سفارشی نباید بیش از :max کاراکتر باشد.',
            'extraSettings.*.key.regex' => 'کلید تنظیم فقط می‌تواند شامل حروف کوچک لاتین، عدد و زیرخط باشد.',
            'extraSettings.*.key.distinct' => 'کلید تنظیم نباید تکراری باشد.',
            'extraSettings.*.key.not_in' => 'این کلید رزرو شده است و از بخش‌های دیگر همین فرم قابل تنظیم است.',
            'extraSettings.*.value.max' => 'مقدار تنظیم نباید بیش از :max کاراکتر باشد.',
        ];
    }
}
