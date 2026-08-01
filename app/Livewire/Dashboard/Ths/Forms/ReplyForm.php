<?php

namespace App\Livewire\Dashboard\Ths\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class ReplyForm extends Form
{
    #[Validate('required_without:files|string|max:4000')]
    public string $body = '';

    #[Validate([
        'files' => 'nullable|array|max:3',
        'files.*' => 'file|max:4096|mimes:jpeg,png,gif,bmp,webp,svg,pdf,doc,docx,xls,xlsx,odt,ods',
    ])]
    public array $files = [];

    protected function messages(): array
    {
        return [
            'body.required_without' => 'وارد کردن متن پاسخ یا افزودن فایل ضمیمه الزامی است.',
            'body.max' => 'متن پاسخ نباید بیشتر از ۴۰۰۰ کاراکتر باشد.',
            'files.max' => 'حداکثر ۳ فایل می‌توانید ضمیمه کنید.',
            'files.*.file' => 'فایل ضمیمه باید معتبر باشد.',
            'files.*.max' => 'حجم فایل نباید بیشتر از ۴ مگابایت باشد.',
            'files.*.mimes' => 'فرمت فایل مجاز نیست.',
        ];
    }
}
