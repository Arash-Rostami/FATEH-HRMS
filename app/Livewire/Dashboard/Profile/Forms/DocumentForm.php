<?php

namespace App\Livewire\Dashboard\Profile\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class DocumentForm extends Form
{
    public array $files = [];

    #[Validate('nullable|string|max:100')]
    public string $customType = '';

    public mixed $customFile = null;

    public function rulesForStandardUpload(string $key): array
    {
        return [
            "files.{$key}" => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }

    public function rulesForCustomUpload(): array
    {
        return [
            'customType' => 'required|string|max:100',
            'customFile' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }

    public function messagesForStandardUpload(string $key): array
    {
        return [
            "files.{$key}.required" => 'لطفاً یک فایل انتخاب کنید.',
            "files.{$key}.file"     => 'یک فایل معتبر بارگذاری کنید.',
            "files.{$key}.mimes"    => 'فرمت فایل باید PDF، JPG، JPEG یا PNG باشد.',
            "files.{$key}.max"      => 'حجم فایل نباید بیشتر از 5 مگابایت باشد.',
        ];
    }

    public function messagesForCustomUpload(): array
    {
        return [
            'customType.required' => 'لطفاً عنوان مدرک سفارشی را وارد کنید.',
            'customType.max'      => 'عنوان مدرک نباید بیشتر از 100 کاراکتر باشد.',
            'customFile.required' => 'لطفاً فایل مدرک سفارشی را انتخاب کنید.',
            'customFile.mimes'    => 'فرمت فایل باید PDF، JPG، JPEG یا PNG باشد.',
            'customFile.max'      => 'حجم فایل نباید بیشتر از 5 مگابایت باشد.',
        ];
    }

    public function validateStandardUpload(string $key): void
    {
        $this->validate($this->rulesForStandardUpload($key), $this->messagesForStandardUpload($key));
    }

    public function validateCustomUpload(): void
    {
        $this->validate($this->rulesForCustomUpload(), $this->messagesForCustomUpload());
    }
}
