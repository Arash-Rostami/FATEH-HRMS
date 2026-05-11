<?php

namespace App\Livewire\Dashboard\Ths\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class TicketForm extends Form
{
    public string $requester = '';
    public string $department = '';
    public array $fileInputs = [];
    public array $requestTypeOptions = [];

    #[Validate('required|string')]
    public string $requestType = 'support';

    #[Validate('required|string')]
    public string $requestArea = '';

    #[Validate('required|string')]
    public string $priority = 'low';

    #[Validate('required|string|max:255')]
    public string $subject = '';

    #[Validate('required|string')]
    public string $description = '';


    #[Validate([
        'files' => 'nullable|array',
        'files.*' => 'file|max:4096|mimes:jpeg,png,gif,bmp,svg,webp,pdf,doc,docx,xls,xlsx,ods,odt',
    ])]
    public array $files = [];

    protected function messages(): array
    {
        return [
            'requestType.required' => 'نوع درخواست را انتخاب کنید.',
            'requestArea.required' => 'حوزه درخواست را انتخاب کنید.',
            'priority.required' => 'اولویت را انتخاب کنید.',
            'subject.required' => 'موضوع تیکت را وارد کنید.',
            'subject.max' => 'حداکثر طول مجاز ۲۵۵ کاراکتر است.',
            'description.required' => 'توضیحات تیکت را وارد کنید.',
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
