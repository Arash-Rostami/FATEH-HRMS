<?php

namespace App\Traits;

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

    protected function validationMessages(): array
    {
        return [
            'email.required' => 'لطفاً آدرس ایمیل خود را وارد کنید.',
            'email.email' => 'فرمت آدرس ایمیل وارد شده صحیح نمی‌باشد.',
            'email.unique' => 'این ایمیل قبلاً در سیستم ثبت شده است.',
            'password.required' => 'لطفاً رمز عبور را وارد کنید.',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
            'password.confirmed' => 'تکرار رمز عبور با رمز وارد شده مطابقت ندارد.',
            'name.required' => 'لطفاً نام و نام خانوادگی خود را وارد کنید.',
            'name.string' => 'نام وارد شده معتبر نمی‌باشد.',
            'name.max' => 'طول نام وارد شده بیش از حد مجاز است.',
        ];
    }
}
