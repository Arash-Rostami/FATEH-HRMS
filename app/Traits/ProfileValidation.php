<?php

namespace App\Traits;

trait ProfileValidation
{
    protected function messages(): array
    {
        return [
            'state.gender.required' => 'انتخاب جنسیت الزامی می‌باشد.',
            'state.marital_status.required' => 'انتخاب وضعیت تأهل الزامی می‌باشد.',
            'state.number_of_children.required' => 'وارد کردن تعداد فرزندان الزامی است.',
            'state.id_booklet_number.required' => 'وارد کردن شماره شناسنامه الزامی است.',
            'state.degree.required' => 'انتخاب مقطع تحصیلی الزامی می‌باشد.',
            'state.field.required' => 'وارد کردن رشته تحصیلی الزامی است.',
            'state.cellphone.required' => 'وارد کردن شماره تلفن همراه الزامی است.',
            'state.zip_code.required' => 'وارد کردن کد پستی الزامی است.',
            'state.address.required' => 'وارد کردن آدرس الزامی است.',
            'state.insurance.required' => 'وارد کردن شماره بیمه الزامی است.',
            'state.emergency_phone.required' => 'تلفن تماس ضروری الزامی است.',
            'state.emergency_relationship.required' => 'نسبت تماس ضروری الزامی است.',
            'state.work_experience.required' => 'وارد کردن سوابق کاری الزامی است.',
            'birthYear.required' => 'سال تولد الزامی است.',
            'birthMonth.required' => 'ماه تولد الزامی است.',
            'birthDay.required' => 'روز تولد الزامی است.',
            'image.image' => 'فایل باید یک تصویر باشد.',
            'image.max' => 'حجم تصویر نباید بیشتر از ۲ مگابایت باشد.',
        ];
    }

    protected function rules(): array
    {
        return [
            'state.gender' => 'required|in:male,female',
            'state.marital_status' => 'required|in:single,married',
            'state.number_of_children' => 'required|integer|min:0',
            'state.id_card_number' => 'nullable|string|max:20',
            'state.id_booklet_number' => 'required|string|max:20',
            'state.degree' => 'required|string|max:255',
            'state.field' => 'required|string|max:255',
            'state.landline' => 'nullable|string|max:20',
            'state.cellphone' => 'required|string|max:20',
            'state.license_plate' => 'nullable|string|max:20',
            'state.zip_code' => 'required|string|max:20',
            'state.address' => 'required|string|max:1000',
            'state.accessibility' => 'nullable|string|max:500',
            'state.insurance' => 'required|string|max:50',
            'state.emergency_phone' => 'required|string|max:20',
            'state.emergency_relationship' => 'required|string|max:50',
            'state.work_experience' => 'required|string|max:50',
            'state.interests' => 'nullable|string|max:1000',
            'state.email' => 'nullable|string',
            'birthYear' => 'required|integer',
            'birthMonth' => 'required|integer',
            'birthDay' => 'required|integer',
            'favoriteColors' => 'nullable|array',
            'image' => 'nullable|image|max:2048',
        ];
    }
}
