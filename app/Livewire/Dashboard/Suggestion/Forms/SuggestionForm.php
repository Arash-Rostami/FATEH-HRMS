<?php

namespace App\Livewire\Dashboard\Suggestion\Forms;

use App\Models\Suggestion;
use Livewire\Attributes\Validate;
use Livewire\Form;

class SuggestionForm extends Form
{
    #[Validate('required|string|min:3|max:255')]
    public string $title = '';

    #[Validate('required|string|min:10')]
    public string $descriptionSelf = '';

    #[Validate('array')]
    public array $departments = [];

    #[Validate('required|array|min:1')]
    public array $purpose = [];

    #[Validate('required|array|min:1')]
    public array $rule = [];

    #[Validate('required|in:low,medium,high')]
    public string $priority = 'medium';

    public $attachment = null;

    public bool $selfFill = false;
    public string $feedbackTeam = '';
    public string $descriptionTeam = '';
    public array $feedback = [];
    public array $descriptionDepts = [];

    public function validated(): array
    {
        return $this->validate();
    }

    protected function rules(): array
    {
        return [
            'attachment' => 'nullable|file|mimes:pdf,png,jpg,jpeg,webp|max:' . config('file_upload_limits.document'),
            'feedbackTeam' => ['in:,agree,neutral,disagree'],
            'feedback.*' => ['in:agree,neutral,disagree'],
            'descriptionDepts.*' => ['nullable', 'string'],
            'purpose.*' => ['in:' . implode(',', array_keys(Suggestion::PURPOSES))],
            'rule.*' => ['in:' . implode(',', array_keys(Suggestion::RULES))],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'title' => 'عنوان پیشنهاد',
            'descriptionSelf' => 'شرح پیشنهاد',
            'departments' => 'واحدهای مرتبط',
            'purpose' => 'هدف',
            'rule' => 'اصول سازمان',
            'priority' => 'اولویت',
            'attachment' => 'پیوست',
            'feedbackTeam' => 'نظر واحد شما',
            'feedback.*' => 'نظر واحد مرتبط',
            'descriptionTeam' => 'توضیح نظر واحد شما',
            'descriptionDepts.*' => 'توضیح نظر واحد مرتبط',
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required' => 'برای پیشنهاد یک عنوان کوتاه بنویسید.',
            'title.string' => 'عنوان باید متن باشد.',
            'title.min' => 'عنوان باید حداقل ۳ کاراکتر باشد.',
            'title.max' => 'عنوان نباید بیشتر از ۲۵۵ کاراکتر باشد.',
            'descriptionSelf.required' => 'شرح پیشنهاد را بنویسید.',
            'descriptionSelf.string' => 'توضیحات باید متن باشد.',
            'descriptionSelf.min' => 'توضیحات باید حداقل ۱۰ کاراکتر باشد.',
            'departments.array' => 'واحدهای مرتبط را از فهرست انتخاب کنید.',
            'purpose.required' => 'دست‌کم یک هدف انتخاب کنید.',
            'purpose.array' => 'هدف را از گزینه‌های فرم انتخاب کنید.',
            'purpose.min' => 'حداقل یک هدف باید انتخاب شود.',
            'rule.required' => 'دست‌کم یکی از اصول سازمان را انتخاب کنید.',
            'rule.array' => 'اصول سازمان را از گزینه‌های فرم انتخاب کنید.',
            'rule.min' => 'دست‌کم یکی از اصول سازمان را انتخاب کنید.',
            'priority.required' => 'انتخاب اولویت الزامی است.',
            'priority.in' => 'اولویت انتخابی معتبر نیست.',
            'attachment.file' => 'فایل انتخاب شده معتبر نیست.',
            'attachment.mimes' => 'فقط PDF یا تصویر PNG، JPG، JPEG و WEBP انتخاب کنید.',
            'attachment.max' => 'حجم فایل باید حداکثر ' . convertToPersian((string) (config('file_upload_limits.document') / 1024)) . ' مگابایت باشد.',
            'feedbackTeam.in' => 'نظر واحد خود را از گزینه‌های فرم انتخاب کنید.',
            'feedback.*.in' => 'نظر هر واحد را از گزینه‌های فرم انتخاب کنید.',
        ];
    }
}
