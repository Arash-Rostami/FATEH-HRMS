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
            'requestArea.required' => 'حوزه درخواست را انتخاب کنید.',
            'requestType.in' => 'نوع درخواست نامعتبر است.',
            'requestArea.in' => 'حوزه درخواست نامعتبر است.',
            'targetDepartment.in' => 'واحد سازمانی انتخاب‌شده نامعتبر است.',
            'priority.required' => 'اولویت را انتخاب کنید.',
            'priority.in' => 'اولویت انتخاب‌شده نامعتبر است.',
            'subject.required' => 'موضوع تیکت را وارد کنید.',
            'subject.max' => 'حداکثر طول مجاز ۲۵۵ کاراکتر است.',
            'description.required' => 'توضیحات تیکت را وارد کنید.',
            'description.max' => 'حداکثر طول مجاز توضیحات ۵۰۰۰ کاراکتر است.',
            'files.max' => 'حداکثر ۳ فایل می‌توانید ضمیمه کنید.',
            'files.*.file' => 'فایل ضمیمه باید معتبر باشد.',
            'files.*.max' => 'حجم فایل نباید بیشتر از ۱۰ مگابایت باشد.',
            'files.*.mimes' => 'فرمت فایل مجاز نیست.',
        ];
    }


    public function validated(): array
    {
        return $this->validate();
    }
}
