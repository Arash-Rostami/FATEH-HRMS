<?php

return [
    'label'        => 'رویداد',
    'plural_label' => 'رویدادها',
    'nav_group'    => 'مدیریت محتوا',

    'navigation' => [
        'singular' => 'رویداد جدید',
    ],

    'fields' => [
        'title'        => 'عنوان',
        'description'  => 'توضیحات',
        'date'         => 'تاریخ',
        'time'         => 'ساعت',
        'private'      => 'خصوصی',
        'private_hint' => 'رویداد خصوصی فقط برای کاربر انتخابی نمایش داده می‌شود.',
        'user'         => 'مخاطب',
        'user_hint'    => 'رویداد خصوصی برای این کاربر نمایش داده خواهد شد.',
        'created_at'   => 'تاریخ ایجاد',
        'updated_at'   => 'آخرین بروزرسانی',
    ],

    'form' => [
        'section_info'     => 'ثبت رویداد خاص',
        'section_schedule' => 'زمان‌بندی',
        'section_access'   => 'سطح دسترسی',
    ],

    'infolist' => [
        'section_info'     => 'اطلاعات رویداد',
        'section_schedule' => 'زمان‌بندی',
        'section_meta'     => 'اطلاعات سیستمی',
    ],

    'filters' => [
        'private'     => 'خصوصی',
        'public'      => 'عمومی',
        'upcoming'    => 'رویدادهای آینده',
        'date_range'  => 'بازه تاریخ',
        'date_from'   => 'از تاریخ',
        'date_until'  => 'تا تاریخ',
    ],

    'validation' => [
        'title' => [
            'required'   => 'عنوان رویداد الزامی است.',
            'max_length' => 'عنوان رویداد نباید بیش از ۲۵۵ کاراکتر باشد.',
        ],
        'description' => [
            'max_length' => 'توضیحات نباید بیش از ۳۰۰۰ کاراکتر باشد.',
        ],
        'date_time_part' => [
            'required' => 'ساعت رویداد الزامی است.',
        ],
        'user_id' => [
            'required' => 'انتخاب مخاطب برای رویداد خصوصی الزامی است.',
        ],
    ],

    'export' => [
        'completed' => ':count ردیف با موفقیت صادر شد.',
    ],
];
