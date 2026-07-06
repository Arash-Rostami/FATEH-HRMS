<?php

namespace App\Livewire\Dashboard\Channel\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class ChannelMessageComposerForm extends Form
{
    #[Validate('required_without:attachments|string|min:1|max:4000')]
    public string $body = '';

    #[Validate([
        'attachments' => 'nullable|array|max:5',
        'attachments.*' => 'file|max:10240|mimes:jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip,rar',
    ])]
    public array $attachments = [];

    #[Validate('nullable|exists:channel_messages,id')]
    public ?int $replyToId = null;

    protected function messages(): array
    {
        return [
            'body.required_without' => 'وارد کردن متن پیام یا افزودن فایل ضمیمه الزامی است.',
            'body.min' => 'متن پیام نباید خالی باشد.',
            'body.max' => 'متن پیام نباید بیشتر از ۴۰۰۰ کاراکتر باشد.',
            'attachments.max' => 'حداکثر ۵ فایل می‌توانید ضمیمه کنید.',
            'attachments.*.file' => 'فایل ضمیمه باید معتبر باشد.',
            'attachments.*.max' => 'حجم فایل نباید بیشتر از ۱۰ مگابایت باشد.',
            'attachments.*.mimes' => 'فرمت فایل مجاز نیست.',
            'replyToId.exists' => 'پیام مرجع نامعتبر است.',
        ];
    }
}