<?php

namespace App\Livewire\Dashboard\Ths\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class ReplyForm extends Form
{
    #[Validate('required_without:files|string|max:4000')]
    public string $body = '';

    public array $files = [];

    protected function rules(): array
    {
        return [
            'files' => 'nullable|array|max:3',
            'files.*' => 'file|max:' . config('file_upload_limits.document') . '|mimes:jpeg,png,gif,bmp,webp,svg,pdf,doc,docx,xls,xlsx,odt,ods',
        ];
    }

    public function validationAttributes(): array
    {
        return ['body' => 'متن پاسخ', 'files' => 'پیوست‌ها', 'files.*' => 'فایل پیوست'];
    }

    protected function messages(): array
    {
        return [
            'body.required_without' => 'متن پاسخ بنویسید یا یک فایل پیوست کنید.',
            'body.max' => 'متن پاسخ نباید بیشتر از ۴۰۰۰ کاراکتر باشد.',
            'files.max' => 'حداکثر ۳ فایل می‌توانید ضمیمه کنید.',
            'files.*.file' => 'فایل ضمیمه باید معتبر باشد.',
            'files.*.max' => 'حجم فایل نباید بیشتر از ۱۰ مگابایت باشد.',
            'files.*.mimes' => 'این نوع فایل پذیرفته نمی‌شود. تصویر، PDF، سند Word یا ODT، و فایل Excel یا ODS مجاز است.',
        ];
    }
}
