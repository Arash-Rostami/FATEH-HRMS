<?php

return [
    'label' => 'گالری',
    'plural_label' => 'گالری تصاویر',
    'nav_group'         => 'محتوا و ارتباطات',

    'fields' => [
        'title' => 'عنوان',
        'description' => 'توضیحات',
        'department' => 'واحد سازمانی',
        'department_hint' => 'در صورت انتخاب، گالری فقط برای کاربران آن واحد نمایش داده می‌شود.',
        'public_gallery' => 'عمومی',
        'event_date' => 'تاریخ رویداد',
        'photos' => 'تصاویر',
        'preview' => 'پیش‌نمایش',
        'count' => 'تعداد تصاویر',
        'created_at' => 'تاریخ ایجاد',
        'updated_at' => 'آخرین بروزرسانی',
    ],

    'form' => [
        'section_info' => 'اطلاعات گالری',
        'section_photos' => 'تصاویر',
    ],

    'filters' => [
        'visibility' => 'دسترسی',
        'private' => 'خصوصی (واحد سازمانی)',
        'public' => 'عمومی',
        'event_date_range' => 'بازه تاریخ رویداد',
        'date_from' => 'از تاریخ',
        'date_until' => 'تا تاریخ',
    ],

    'hints' => [
        'title' => 'عنوان گالری که در صفحه نمایش و جستجو استفاده می‌شود.',
        'description' => 'توضیح کوتاه درباره محتوا یا مناسبت این گالری (اختیاری).',
        'path' => 'تصاویر با فرمت‌های JPG، PNG، GIF یا WEBP؛ حداکثر ۵ مگابایت هر تصویر و سقف ۵۰ تصویر در یک گالری.',
    ],

    'export' => [
        'completed' => ':count ردیف با موفقیت صادر شد.',
    ],
];
