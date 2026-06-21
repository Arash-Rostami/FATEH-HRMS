<?php

namespace App\Livewire\Dashboard\Ths\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class TicketForm extends Form
{
    public string $requester = '';
    public string $department = '';
    public string $targetDepartment = '';

    public array $fileInputs = [];
    public array $requestTypeOptions = [];

    #[Validate('required|string')]
    public string $requestType = 'support';

    #[Validate('required|string')]
    public string $requestArea = '';

    #[Validate('required|in:low,medium,high')]
    public string $priority = 'low';

    #[Validate('required|string|max:255')]
    public string $subject = '';

    #[Validate('required|string|max:5000')]
    public string $description = '';


    #[Validate([
        'files' => 'nullable|array|max:3',
        'files.*' => 'file|max:4096|mimes:jpeg,png,gif,bmp,svg,webp,pdf,doc,docx,xls,xlsx,ods,odt',
    ])]
    public array $files = [];

    protected function messages(): array
    {
        return [
            'requestType.required' => 'نوع درخواست را انتخاب کنید.',
            'requestArea.required' => 'حوزه درخواست را انتخاب کنید.',
            'priority.required' => 'اولویت را انتخاب کنید.',
            'priority.in' => 'اولویت انتخاب‌شده نامعتبر است.',
            'subject.required' => 'موضوع تیکت را وارد کنید.',
            'subject.max' => 'حداکثر طول مجاز ۲۵۵ کاراکتر است.',
            'description.required' => 'توضیحات تیکت را وارد کنید.',
            'description.max' => 'حداکثر طول مجاز توضیحات ۵۰۰۰ کاراکتر است.',
            'files.max' => 'حداکثر ۳ فایل می‌توانید ضمیمه کنید.',
            'files.*.file' => 'فایل ضمیمه باید معتبر باشد.',
            'files.*.max' => 'حجم فایل نباید بیشتر از ۴ مگابایت باشد.',
            'files.*.mimes' => 'فرمت فایل مجاز نیست.',
        ];
    }


    public function validated(): array
    {
        return $this->validate();
    }
}
