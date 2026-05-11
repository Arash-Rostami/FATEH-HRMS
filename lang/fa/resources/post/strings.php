<?php

return [
    'label'             => 'پست',
    'plural_label'      => 'پست‌ها',
    'nav_group'         => 'مدیریت محتوا',
    'nav_badge_tooltip' => 'تعداد پست‌های سنجاق‌شده',

    'fields' => [
        'title'        => 'عنوان',
        'user_locked'  => 'عنوان',
        'body'         => 'محتوا',
        'image'        => 'تصویر',
        'pinned'       => 'سنجاق‌شده',
        'pinned_hint'  => 'پست سنجاق‌شده همیشه به کاربران نمایش داده می‌شود.',
        'user'         => 'نویسنده',
        'created_at'   => 'تاریخ انتشار',
        'updated_at'   => 'آخرین بروزرسانی',
    ],

    'hint' => [
        'user_locked' => 'ثبت‌کننده به‌طور خودکار شما هستید و قابل تغییر نیست.',
    ],

    'form' => [
        'section_meta'    => 'نویسنده و تنظیمات',
        'section_content' => 'محتوای پست',
        'section_image'   => 'تصویر',
    ],

    'filters' => [
        'date_range'  => 'بازه تاریخ',
        'date_from'   => 'از تاریخ',
        'date_until'  => 'تا تاریخ',
        'has_image'   => 'دارای تصویر',
    ],

    'validation' => [
        'user_id' => [
            'required' => 'انتخاب نویسنده پست الزامی است.',
        ],
        'title' => [
            'required'   => 'عنوان پست الزامی است.',
            'max_length' => 'عنوان پست نباید بیش از ۷۰۰ کاراکتر باشد.',
        ],
        'body' => [
            'required'   => 'محتوای پست الزامی است.',
            'max_length' => 'محتوای پست نباید بیش از ۵۰۰۰۰ کاراکتر باشد.',
        ],
    ],

    'export' => [
        'completed' => ':count ردیف با موفقیت صادر شد.',
    ],
];
