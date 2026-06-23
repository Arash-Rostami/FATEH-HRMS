<?php

return [

    'table' => [
        'action_view' => 'مشاهده',
        'action_edit' => 'ویرایش',
        'action_restore' => 'بازگرداندن',
        'action_delete' => 'حذف',
        'action_delete_confirm' => 'آیا از حذف این رکورد اطمینان دارید؟',
        'action_delete_body' => 'این عملیات قابل بازگشت نیست.',

        'action_assign' => 'تخصیص',
        'action_assign_heading' => 'تخصیص رکورد',
        'action_assign_description' => 'آیا از تخصیص این رکورد اطمینان دارید؟',

        'action_unassign' => 'حذف تخصیص',
        'action_unassign_heading' => 'حذف تخصیص رکورد',
        'action_unassign_description' => 'آیا از حذف تخصیص این رکورد اطمینان دارید؟',

        'action_bulk_unassign' => 'حذف تخصیص انتخاب‌شده‌ها',

        'action_create' => 'افزودن رکورد',

        'bulk_delete' => 'حذف انتخاب‌شده‌ها',
        'bulk_export' => 'خروجی Excel',
    ],

    'notifications' => [
        'created' => 'رکورد با موفقیت ایجاد شد.',
        'saved' => 'اطلاعات رکورد با موفقیت ذخیره شد.',
        'assigned' => 'رکورد با موفقیت تخصیص یافت.',
        'unassigned' => 'تخصیص رکورد با موفقیت حذف شد.',
        'bulk_unassigned' => 'تخصیص رکوردهای انتخاب‌شده با موفقیت حذف شد.',
    ],

    'filters' => [
        'date_range' => 'بازه تاریخ',
        'date_from' => 'از تاریخ',
        'date_until' => 'تا تاریخ',
    ],


    'validation' => [
        'required' => 'وارد کردن «:attribute» الزامی است.',
        'exists'   => '«:attribute» انتخاب شده نامعتبر است.',
        'in'       => '«:attribute» انتخاب شده نامعتبر است.',
        'unique'   => 'این «:attribute» قبلاً ثبت شده است.',
        'max'      => 'طول «:attribute» بیش از حد مجاز است.',
        'min'      => 'طول «:attribute» کمتر از حد مجاز است.',
        'mimes'    => 'فرمت فایل «:attribute» نامعتبر است.',
        'email'    => 'فرمت ایمیل «:attribute» نامعتبر است.',
        'url'      => 'فرمت لینک «:attribute» نامعتبر است.',
        'numeric'  => '«:attribute» باید یک عدد باشد.',
        'date'     => 'تاریخ «:attribute» نامعتبر است.',
    ],

];
