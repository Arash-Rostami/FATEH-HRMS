<?php

return [
    'label'        => 'دپارتمان',
    'plural_label' => 'دپارتمان‌ها',
    'nav_group'    => 'مدیریت کاربران',

    'fields' => [
        'code'        => 'کد دپارتمان',
        'name'        => 'نام دپارتمان',
        'description' => 'توضیحات',
        'users_count' => 'تعداد پرسنل',
        'created_at'  => 'تاریخ ایجاد',
        'updated_at'  => 'آخرین ویرایش',
    ],

    'form' => [
        'section_main'        => 'اطلاعات دپارتمان',
        'section_description' => 'مشخصات دپارتمان را وارد کنید.',
    ],

    'infolist' => [
        'section_main' => 'جزئیات دپارتمان',
    ],

    'filters' => [
        'has_users'  => 'دارای پرسنل',
        'created_at' => 'بازه تاریخ ایجاد',
        'from'       => 'از تاریخ',
        'until'      => 'تا تاریخ',
    ],

    'relations' => [
        'users'        => 'پرسنل دپارتمان',
        'user_details' => 'مشخصات پرسنل',
    ],

    'user_fields' => [
        'name'      => 'نام',
        'email'     => 'ایمیل',
        'position'  => 'سمت',
        'status'    => 'وضعیت',
        'role'      => 'نقش',
        'last_seen' => 'آخرین بازدید',
    ],

    'user_status' => [
        'active'   => 'فعال',
        'inactive' => 'غیرفعال',
    ],

    'notifications' => [
        'unassigned'      => 'کاربر از دپارتمان حذف شد.',
        'bulk_unassigned' => 'کاربران از دپارتمان حذف شدند.',
    ],

    'validation' => [
        'code_required'   => 'کد دپارتمان الزامی است.',
        'code_max'        => 'کد دپارتمان نباید بیشتر از ۱۰ کاراکتر باشد.',
        'code_unique'     => 'این کد قبلاً ثبت شده است.',
        'code_alpha_dash' => 'کد دپارتمان فقط می‌تواند شامل حروف، اعداد، خط تیره و زیرخط باشد.',
        'name_required'   => 'نام دپارتمان الزامی است.',
        'name_max'        => 'نام دپارتمان نباید بیشتر از ۲۵۵ کاراکتر باشد.',
    ],

    'export' => [
        'id'          => 'شناسه',
        'code'        => 'کد دپارتمان',
        'name'        => 'نام دپارتمان',
        'description' => 'توضیحات',
        'users_count' => 'تعداد پرسنل',
        'created_at'  => 'تاریخ ایجاد',
        'filename'    => 'دپارتمان‌ها',
    ],
];
