<?php

namespace App\Livewire\Dashboard\Channel\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class ChannelMessageComposerForm extends Form
{
    #[Validate('required_without:attachments|string|min:1|max:4000', onUpdate: false)]
    public string $body = '';

    public array $attachments = [];

    #[Validate('nullable|exists:channel_messages,id')]
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
            'body.required_without' => 'متن پیام را بنویسید یا یک فایل پیوست کنید.',
            'body.min' => 'متن پیام نباید خالی باشد.',
            'body.max' => 'متن پیام نباید بیشتر از ۴۰۰۰ کاراکتر باشد.',
            'attachments.max' => 'حداکثر ۵ فایل می‌توانید ضمیمه کنید.',
            'attachments.*.file' => 'این فایل باز نشد. دوباره آن را انتخاب کنید.',
            'attachments.*.max' => 'حجم هر فایل باید حداکثر ۱۰ مگابایت باشد. فایل کوچک‌تری انتخاب کنید.',
            'attachments.*.mimes' => 'این نوع فایل پذیرفته نمی‌شود. تصویر، ویدئو، PDF، Word، Excel، ZIP یا RAR انتخاب کنید.',
            'replyToId.exists' => 'پیامی که به آن پاسخ می‌دهید دیگر در دسترس نیست. پاسخ را ببندید و دوباره تلاش کنید.',
        ];
    }
}
