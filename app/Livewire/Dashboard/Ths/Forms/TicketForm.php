<?php

namespace App\Livewire\Dashboard\Ths\Forms;

use App\Models\Department;
use App\Models\Ticket;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class TicketForm extends Form
{
    public string $department = '';
    public string $targetDepartment = '';

    public array $fileInputs = [];
    public array $requestTypeOptions = [];

    public string $requestType = 'support';

    public string $requestArea = '';

    #[Validate('required|in:low,medium,high')]
    public string $priority = 'low';

    #[Validate('required|string|max:255')]
    public string $subject = '';

    #[Validate('required|string|max:5000')]
    public string $description = '';


    public array $files = [];

    protected function rules(): array
    {
        $dept = in_array($this->targetDepartment, ['', 'N/A'], true) ? null : $this->targetDepartment;
        $areas = array_keys(array_filter(Ticket::getCustomRequestAreaOptions($dept, $this->requestType), fn($label, $key) => $key !== '', ARRAY_FILTER_USE_BOTH));

        return [
            'targetDepartment' => ['nullable', 'string', Rule::in([...Department::getOfferedTicketOptions()->keys(), 'N/A', ''])],
            'requestType' => ['required', 'string', Rule::in(array_keys(Ticket::getCustomRequestTypeOptions($dept)))],
            'requestArea' => ['required', 'string', Rule::in($areas)],
            'files' => 'nullable|array|max:3',
            'files.*' => 'file|max:' . config('file_upload_limits.document') . '|mimes:jpeg,png,gif,bmp,svg,webp,pdf,doc,docx,xls,xlsx,ods,odt',
        ];
    }

    protected function messages(): array
    {
        return [
            'requestType.required' => 'نوع درخواست را انتخاب کنید.',
            'requestArea.required' => 'حوزهٔ درخواست را انتخاب کنید.',
            'requestType.in' => 'نوع درخواست را از فهرست انتخاب کنید.',
            'requestArea.in' => 'حوزهٔ درخواست را از گزینه‌های همین نوع درخواست انتخاب کنید.',
            'targetDepartment.in' => 'یک واحد رسیدگی‌کننده از فهرست انتخاب کنید.',
            'priority.required' => 'اولویت را انتخاب کنید.',
            'priority.in' => 'اولویت انتخاب‌شده نامعتبر است.',
            'subject.required' => 'موضوع تیکت را وارد کنید.',
            'subject.max' => 'موضوع را در حداکثر ۲۵۵ نویسه بنویسید.',
            'description.required' => 'شرح درخواست را بنویسید.',
            'description.max' => 'شرح درخواست را در حداکثر ۵۰۰۰ نویسه بنویسید.',
            'files.max' => 'می‌توانید حداکثر ۳ فایل پیوست کنید.',
            'files.*.file' => 'فایل پیوست خوانده نشد. دوباره آن را انتخاب کنید.',
            'files.*.max' => 'حجم فایل نباید بیشتر از ۱۰ مگابایت باشد.',
            'files.*.mimes' => 'این نوع فایل پذیرفته نمی‌شود. تصویر، PDF، سند Word یا ODT، و فایل Excel یا ODS مجاز است.',
        ];
    }


    public function validationAttributes(): array
    {
        return [
            'targetDepartment' => 'واحد رسیدگی‌کننده',
            'requestType' => 'نوع درخواست',
            'requestArea' => 'حوزهٔ درخواست',
            'priority' => 'اولویت',
            'subject' => 'موضوع تیکت',
            'description' => 'شرح درخواست',
            'files' => 'پیوست‌ها',
            'files.*' => 'فایل پیوست',
        ];
    }

    public function validated(): array
    {
        return $this->validate();
    }
}
