<?php

namespace App\Livewire\Dashboard\Contact\Forms;

use Livewire\Form;
use Livewire\Attributes\Validate;

class MessageComposerForm extends Form
{
    #[Validate('required_without:attachments|string|max:2000', onUpdate: false)]
    public string $body = '';

    public array $attachments = [];

    #[Validate('nullable|exists:messages,id')]
    public ?int $replyToId = null;

    protected function rules(): array
    {
        return [
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:' . config('file_upload_limits.document') . '|mimes:jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip,rar,mp4,webm,mov',
        ];
    }

    protected function messages(): array
    {
        return [
            'body.required_without' => 'وارد کردن متن پیام یا افزودن فایل ضمیمه الزامی است.',
            'body.max' => 'متن پیام نباید بیشتر از ۲۰۰۰ کاراکتر باشد.',
            'attachments.max' => 'حداکثر ۵ فایل می‌توانید ضمیمه کنید.',
            'attachments.*.file' => 'فایل ضمیمه باید معتبر باشد.',
            'attachments.*.max' => 'حجم فایل نباید بیشتر از ۱۰ مگابایت باشد؛ برای ارسال فایل‌های بزرگ‌تر از ایمیل استفاده کنید.',
            'attachments.*.mimes' => 'فرمت فایل مجاز نیست.',
            'replyToId.exists' => 'پیام مرجع نامعتبر است.',
        ];
    }
}
