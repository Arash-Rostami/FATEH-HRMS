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

    #[Validate('nullable|integer|exists:users,id')]
    public ?int $ownerId = null;

    #[Validate(['departments' => 'array', 'departments.*' => 'string|exists:departments,code'])]
    public array $departments = [];

    #[Validate('boolean')]
    public bool $requiresApproval = false;

    #[Validate('nullable|integer|min:1')]
    public ?int $slaHours = null;

    #[Validate(['labels' => 'array|max:30', 'labels.*' => 'string|max:30'])]
    public array $labels = [];

    #[Validate('nullable|integer|min:1')]
    public ?int $autoArchiveDays = null;

    #[Validate('nullable|numeric|min:1400|max:1500')]
    public string $deadlineYear = '';

    #[Validate('nullable|numeric|min:1|max:12')]
    public string $deadlineMonth = '';

    #[Validate('nullable|numeric|min:1|max:31')]
    public string $deadlineDay = '';

    #[Validate(['customSchema' => 'array', 'customSchema.*.key' => 'required|string|regex:/^[a-z0-9_]+$/|max:40|distinct', 'customSchema.*.label' => 'required|string|max:80'])]
    public array $customSchema = [];

    public array $extraSettings = [];

    public ?int $updatedAt = null;

    public const EXTRA_KEY_MESSAGES = [
        'regex' => 'کلید تنظیم فقط می‌تواند شامل حروف کوچک لاتین، عدد و زیرخط باشد.',
        'distinct' => 'کلید تنظیم نباید تکراری باشد.',
        'not_in' => 'این نام مربوط به یکی از تنظیمات بالای فرم است؛ همان قسمت را تغییر دهید.',
    ];

    public static function extraKeyRules(): array
    {
        return ['string', 'regex:/^[a-z0-9_]+$/', 'max:40', 'distinct', Rule::notIn(Project::KNOWN_SETTING_KEYS)];
    }

    protected function rules(): array
    {
        return [
            'extraSettings' => 'array',
            'extraSettings.*.key' => ['required', ...self::extraKeyRules()],
            'extraSettings.*.value' => 'nullable|string|max:500',
        ];
    }

    protected array $validationAttributes = [
        'name' => 'نام پروژه',
        'memberIds' => 'اعضا',
        'ownerId' => 'مالک پروژه',
        'departments' => 'واحدهای دارای دسترسی',
        'requiresApproval' => 'تأیید مدیر پروژه',
        'slaHours' => 'مهلت پیش‌فرض انجام وظیفه',
        'labels' => 'برچسب‌های مجاز وظایف',
        'labels.*' => 'برچسب',
        'autoArchiveDays' => 'زمان بایگانی خودکار',
        'deadlineYear' => 'سال مهلت پروژه',
        'deadlineMonth' => 'ماه مهلت پروژه',
        'deadlineDay' => 'روز مهلت پروژه',
        'customSchema' => 'اطلاعات دلخواه وظایف',
        'customSchema.*.key' => 'نام انگلیسی قسمت اطلاعات دلخواه',
        'customSchema.*.label' => 'عنوان قسمت اطلاعات دلخواه',
        'extraSettings' => 'تنظیمات دیگر',
        'extraSettings.*.key' => 'کلید تنظیم',
        'extraSettings.*.value' => 'مقدار تنظیم',
    ];

    protected function messages(): array
    {
        return [
            'name.required' => 'وارد کردن نام پروژه الزامی است.',
            'name.max' => 'نام پروژه نباید بیش از ۲۵۵ کاراکتر باشد.',
            'deadlineMonth.min' => 'ماه باید بین ۱ تا ۱۲ باشد.',
            'deadlineMonth.max' => 'ماه باید بین ۱ تا ۱۲ باشد.',
            'deadlineDay.min' => 'روز باید بین ۱ تا ۳۱ باشد.',
            'deadlineDay.max' => 'روز باید بین ۱ تا ۳۱ باشد.',
            'labels.max' => 'حداکثر ۳۰ برچسب مجاز است.',
            'labels.*.max' => 'برچسب نباید بیش از :max کاراکتر باشد.',
            'autoArchiveDays.min' => 'تعداد روز باید حداقل ۱ باشد.',
            'slaHours.min' => 'مهلت پیش‌فرض باید حداقل ۱ ساعت باشد.',
            'customSchema.*.key.regex' => 'نام انگلیسی فقط می‌تواند شامل حروف کوچک انگلیسی، عدد و زیرخط (_) باشد؛ مانند order_number.',
            'customSchema.*.key.distinct' => 'برای هر قسمت، نام انگلیسی متفاوتی بنویسید.',
            'customSchema.*.key.max' => 'نام انگلیسی نباید بیش از :max کاراکتر باشد.',
            'customSchema.*.label.max' => 'عنوان نباید بیش از :max کاراکتر باشد.',
            ...collect(self::EXTRA_KEY_MESSAGES)->mapWithKeys(fn($message, $rule) => ["extraSettings.*.key.$rule" => $message])->all(),
            'extraSettings.*.value.max' => 'مقدار تنظیم نباید بیش از :max کاراکتر باشد.',
        ];
    }
}
