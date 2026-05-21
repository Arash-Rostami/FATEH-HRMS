<?php

return [
    'label' => 'آنبوردینگ',
    'plural_label' => 'آنبوردینگ‌ها',
    'nav_group'    => 'کاربران و سازمان',
    'nav_badge_tooltip' => 'تعداد آنبوردینگ‌های فعال',

    'fields' => [
        'user_id' => 'کاربر اختصاصی',
        'user_id_hint' => 'در صورت خالی ماندن، این آنبوردینگ برای همه کاربران نمایش داده می‌شود.',
        'global_audience' => 'همه کاربران',
        'is_active' => 'فعال',
        'is_active_hint' => 'فقط یک آنبوردینگ برای هر مخاطب می‌تواند فعال باشد. فعال‌سازی این مورد، بقیه را غیرفعال می‌کند.',
        'welcome' => 'خوش‌آمدگویی',
        'mission' => 'مأموریت',
        'vision' => 'چشم‌انداز',
        'schedule' => 'برنامه زمانی',
        'videos' => 'ویدیوها',
        'guides' => 'راهنماها',
        'extras' => 'بخش‌های اضافی',
        'sections' => 'بخش‌های تکمیل‌شده',
        'created_at' => 'تاریخ ایجاد',
        'updated_at' => 'آخرین بروزرسانی',
    ],

    'repeater' => [
        'video_title' => 'عنوان ویدیو',
        'video_url' => 'فایل ویدیو',
        'video_thumbnail' => 'تصویر بندانگشتی',
        'video_duration' => 'مدت زمان',
        'guide_title' => 'عنوان راهنما',
        'guide_file' => 'فایل راهنما',
        'guide_ext' => 'پسوند',
        'guide_size' => 'حجم فایل',
        'extra_key' => 'کلید (شناسه)',
        'extra_key_hint' => 'یک شناسه یکتا به انگلیسی مثل: parking, dress_code',
        'extra_display_title' => 'عنوان نمایشی',
        'extra_value' => 'محتوا',
    ],

    'form' => [
        'section_settings' => 'تنظیمات',
    ],

    'infolist' => [
        'section_meta' => 'اطلاعات سیستمی',
    ],

    'filters' => [
        'active' => 'فعال',
        'inactive' => 'غیرفعال',
        'audience' => 'نوع مخاطب',
        'personal' => 'اختصاصی',
        'global' => 'عمومی',
    ],

    'actions' => [
        'add_video' => 'افزودن ویدیو',
        'add_guide' => 'افزودن راهنما',
        'add_extra' => 'افزودن بخش اضافی',
    ],

    'validation' => [
        'welcome' => [
            'required' => 'متن خوش‌آمدگویی الزامی است.',
            'max_length' => 'متن خوش‌آمدگویی نباید بیش از ۵۰۰۰۰ کاراکتر باشد.',
        ],
        'mission' => [
            'max_length' => 'متن مأموریت نباید بیش از ۵۰۰۰۰ کاراکتر باشد.',
        ],
        'vision' => [
            'max_length' => 'متن چشم‌انداز نباید بیش از ۵۰۰۰۰ کاراکتر باشد.',
        ],
        'schedule' => [
            'max_length' => 'متن برنامه زمانی نباید بیش از ۵۰۰۰۰ کاراکتر باشد.',
        ],
        'extra_value' => [
            'max_length' => 'محتوای بخش اضافی نباید بیش از ۲۰۰۰۰ کاراکتر باشد.',
        ],
    ],

    'export' => [
        'completed' => ':count ردیف با موفقیت صادر شد.',
    ],
];
