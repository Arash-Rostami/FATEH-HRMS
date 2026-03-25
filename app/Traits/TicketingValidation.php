<?php

namespace App\Traits;

trait TicketingValidation
{
    protected function validateTicketData(): array
    {
        return $this->validate([
            'ticket.requestType' => 'required|string',
            'ticket.requestArea' => 'required|string',
            'ticket.priority' => 'required|string',
            'ticket.subject' => 'required|string|max:255',
            'ticket.description' => 'required|string',
            'files' => 'array',
            'files.*' => 'nullable|file|max:4096|mimes:jpeg,png,gif,bmp,svg,webp,pdf,doc,docx,xls,xlsx,ods,odt'
        ], [
            'ticket.requestType.required' => 'نوع درخواست را انتخاب کنید.',
            'ticket.requestArea.required' => 'حوزه درخواست را انتخاب کنید.',
            'ticket.priority.required' => 'اولویت را انتخاب کنید.',
            'ticket.subject.required' => 'موضوع تیکت را وارد کنید.',
            'ticket.subject.max' => 'حداکثر طول مجاز ۲۵۵ کاراکتر است.',
            'ticket.description.required' => 'توضیحات تیکت را وارد کنید.',
            'files.*.file' => 'فایل ضمیمه باید معتبر باشد.',
            'files.*.max' => 'حجم فایل نباید بیشتر از ۴ مگابایت باشد.',
            'files.*.mimes' => 'فرمت فایل مجاز نیست.',
        ]);
    }

    protected function validateRateData(): void
    {
        $this->validate([
            'satisfactionScore' => 'required|integer|min:1|max:5',
            'satisfactionComment' => 'nullable|string|max:1000',
        ], [
            'satisfactionScore.required' => 'لطفا امتیاز رضایت را انتخاب کنید.',
            'satisfactionScore.integer' => 'امتیاز باید یک عدد صحیح باشد.',
            'satisfactionScore.min' => 'امتیاز باید حداقل ۱ باشد.',
            'satisfactionScore.max' => 'امتیاز نمی‌تواند بیشتر از ۵ باشد.',
            'satisfactionComment.max' => 'توضیحات نمی‌تواند بیش از ۱۰۰۰ کاراکتر باشد.',
        ]);
    }
}
