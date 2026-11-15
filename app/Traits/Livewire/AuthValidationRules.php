<?php

namespace App\Traits\Livewire;

trait AuthValidationRules
{

    protected function emailRules(bool $unique = false): array
    {
        $rules = ['required', 'string', 'email', 'max:255'];

        if ($unique) {
            $rules[] = 'unique:users,email';
        }

        return $rules;
    }


    protected function passwordRules(bool $confirmed = false): array
    {
        $rules = ['required', 'string', 'min:8'];

        if ($confirmed) {
            $rules[] = 'confirmed';
        }

        return $rules;
    }

    protected function phoneRules(): array
    {
        return ['required', 'string', 'regex:/^(\+98|0)?9\d{9}$/'];
    }

    protected function codeRules(): array
    {
        return ['required', 'digits:5'];
    }

    protected function validationMessages(): array
    {
        return [
            'email.required' => 'لطفاً آدرس ایمیل خود را وارد کنید.',
            'email.email' => 'فرمت آدرس ایمیل وارد شده صحیح نمی‌باشد.',
            'email.unique' => 'این ایمیل قبلاً در سیستم ثبت شده است.',
            'password.required' => 'لطفاً رمز عبور را وارد کنید.',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
            'password.confirmed' => 'تکرار رمز عبور با رمز وارد شده مطابقت ندارد.',
            'phone.required' => 'لطفاً شمارهٔ موبایل خود را وارد کنید.',
            'phone.regex' => 'شمارهٔ موبایل واردشده معتبر نیست.',
            'code.required' => 'لطفاً کد دریافتی را وارد کنید.',
            'code.digits' => 'کد ورود باید ۵ رقم باشد.',
            'name.required' => 'لطفاً نام و نام خانوادگی خود را وارد کنید.',
            'name.string' => 'نام وارد شده معتبر نمی‌باشد.',
            'name.max' => 'طول نام وارد شده بیش از حد مجاز است.',
        ];
    }
}
