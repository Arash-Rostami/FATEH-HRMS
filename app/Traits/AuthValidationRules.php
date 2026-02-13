<?php

namespace App\Traits;

trait AuthValidationRules
{
    /**
     * Get the validation rules for email.
     *
     * @param bool $unique Whether the email must be unique (for registration).
     * @return array
     */
    protected function emailRules(bool $unique = false): array
    {
        $rules = ['required', 'string', 'email', 'max:255'];

        if ($unique) {
            $rules[] = 'unique:users,email';
        }

        return $rules;
    }

    /**
     * Get the validation rules for password.
     *
     * @param bool $confirmed Whether the password must be confirmed (for registration/reset).
     * @return array
     */
    protected function passwordRules(bool $confirmed = false): array
    {
        $rules = ['required', 'string', 'min:8'];

        if ($confirmed) {
            $rules[] = 'confirmed';
        }

        return $rules;
    }

    /**
     * Get the standard validation messages in Persian.
     *
     * @return array
     */
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
