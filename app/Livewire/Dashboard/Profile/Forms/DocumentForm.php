<?php

namespace App\Livewire\Dashboard\Profile\Forms;

use Illuminate\Http\UploadedFile;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class DocumentForm extends Form
{

    #[Validate('required')]
    #[Validate('array')]
    #[Validate('file')]
    #[Validate('mimes:pdf,jpg,jpeg,png')]
    #[Validate('max:5120')]
    public array $files = [];

    #[Validate('nullable|string')]
    #[Validate('max:100')]
    public string $customType = '';

    #[Validate('required')]
    #[Validate('file')]
    #[Validate('mimes:pdf,jpg,jpeg,png')]
    #[Validate('max:5120')]
    public ?UploadedFile $customFile = null;

    protected function messages(): array
    {
        return [
            'files.required' => 'لطفاً حداقل یک فایل انتخاب کنید.',
            'files.*.file' => 'یک فایل معتبر بارگذاری کنید.',
            'files.*.mimes' => 'فرمت فایل باید PDF، JPG، JPEG یا PNG باشد.',
            'files.*.max' => 'حجم فایل نباید بیشتر از 5 مگابایت باشد.',

            'customType.max' => 'عنوان نباید بیشتر از 100 کاراکتر باشد.',

            'customFile.required' => 'لطفاً فایل مدرک سفارشی را انتخاب کنید.',
            'customFile.file' => 'فایل بارگذاری شده معتبر نیست.',
            'customFile.mimes' => 'فرمت فایل باید PDF، JPG، JPEG یا PNG باشد.',
            'customFile.max' => 'حجم فایل نباید بیشتر از 5 مگابایت باشد.',
        ];
    }

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

    public function messagesForCustomUpload(): array
    {
        return [
            'customType.required' => 'لطفاً عنوان مدرک سفارشی را وارد کنید.',
            'customType.max' => 'عنوان مدرک نباید بیشتر از 100 کاراکتر باشد.',
            'customFile.required' => 'لطفاً فایل مدرک سفارشی را انتخاب کنید.',
            'customFile.mimes' => 'فرمت فایل باید PDF, JPG, JPEG یا PNG باشد.',
            'customFile.max' => 'حجم فایل نباید بیشتر از 5 مگابایت باشد.',
        ];
    }

    public function messagesForStandardUpload(string $key): array
    {
        return [
            "files.{$key}.required" => 'لطفاً یک فایل انتخاب کنید.',
            "files.{$key}.file" => 'یک فایل معتبر بارگذاری کنید.',
            "files.{$key}.mimes" => 'فرمت فایل باید PDF, JPG, JPEG یا PNG باشد.',
            "files.{$key}.max" => 'حجم فایل نباید بیشتر از 5 مگابایت باشد.',
        ];
    }

    public function validateStandardUpload(string $key): void
    {
        $this->validate(
            $this->rulesForStandardUpload($key),
            $this->messagesForStandardUpload($key)
        );
    }

    public function validateCustomUpload(): void
    {
        $this->validate(
            $this->rulesForCustomUpload(),
            $this->messagesForCustomUpload()
        );
    }
}
