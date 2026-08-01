<?php

return [
    'label'             => 'پشتیبانی',
    'plural_label'      => 'پشتیبانی',
    'nav_group'         => 'سیستم‌ها و ابزارها',
    'deleted_user'      => 'کاربر حذف‌شده',

    'fields' => [
        'user'       => 'ثبت‌کننده',
        'type'       => 'نوع درخواست',
        'title'      => 'عنوان',
        'body'       => 'متن درخواست',
        'status'     => 'وضعیت',
        'id'     => 'شناسه',
        'created_at' => 'تاریخ ایجاد',
    ],

    'filters' => [
        'user' => 'ثبت‌کننده',
    ],

    'placeholders' => [
        'title' => 'عنوان درخواست را وارد کنید',
        'body'  => 'درخواست خود را شرح دهید...',
    ],

    'hint' => [
        'user_locked' => 'ثبت‌کننده به‌طور خودکار شما هستید و قابل تغییر نیست.',
    ],

    'form' => [
        'section_meta'                 => 'اطلاعات درخواست',
        'section_meta_description'     => 'نوع، وضعیت و ثبت‌کننده درخواست',
        'section_content'              => 'محتوای درخواست',
        'section_content_description'  => 'عنوان و متن کامل درخواست را وارد کنید.',
    ],

    'action' => [
        'submit'            => 'ثبت درخواست',
        'submit_heading'     => 'ثبت درخواست پشتیبانی / پیشنهاد / باگ',
        'submit_description' => 'درخواست پشتیبانی، پیشنهاد ماژول یا گزارش باگ را ثبت کنید.',
        'create'             => 'ایجاد درخواست',
    ],

    'notifications' => [
        'submitted' => 'درخواست شما با موفقیت ثبت شد.',
    ],

    'export' => [
        'completed' => ':count ردیف با موفقیت خروجی گرفته شد.',
    ],
];
