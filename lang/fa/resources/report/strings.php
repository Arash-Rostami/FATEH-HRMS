<?php

return [
    'label' => 'گزارش',
    'plural_label' => 'گزارشات',
    'nav_group'      => 'عملیات و منابع',

    'fields' => [
        'title' => 'عنوان',
        'description' => 'توضیحات',
        'cover_image' => 'تصویر جلد',
        'department' => 'واحد سازمانی',
        'file_path' => 'فایل گزارش',
        'file_type' => 'نوع فایل',
        'active' => 'وضعیت',
        'user' => 'کاربر',
        'thumbnail' => 'پیش‌نمایش',
        'created_at' => 'تاریخ ایجاد',
        'updated_at' => 'تاریخ ویرایش',
    ],

    'form' => [
        'section_main' => 'اطلاعات گزارش',
        'section_description' => 'اطلاعات پایه گزارش را وارد کنید.',
        'section_files' => 'فایل‌ها',
        'tab_main' => 'اطلاعات اصلی',
        'tab_files' => 'فایل‌ها',
    ],

    'placeholders' => [
        'description' => 'توضیح گزارش خود را اینجا بنویسید... ',
    ],

    'helper_text' => [
        'description' => '.⚠️ می‌توانید از قالب‌بندی متن، لینک و جدول برای بهبود آن استفاده کنید.',
    ],

    'table' => [
        'id' => 'شناسه',
        'title' => 'عنوان',
        'department' => 'واحد',
        'user' => 'کاربر',
        'file_type' => 'نوع فایل',
        'active' => 'وضعیت',
        'created_at' => 'تاریخ ایجاد',
    ],

    'infolist' => [
        'section_main' => 'جزئیات گزارش',
    ],

    'filters' => [
        'active' => 'وضعیت',
        'active_active' => 'فعال',
        'active_inactive' => 'غیرفعال',
        'department' => 'واحد سازمانی',
        'created_from' => 'از تاریخ',
        'created_until' => 'تا تاریخ',
    ],

    'groups' => [
        'active' => 'وضعیت',
        'department' => 'واحد سازمانی',
    ],

    'navigation' => [
        'singular' => 'گزارش',
    ],

    'hints' => [
        'title' => 'عنوان گزارش که در فهرست گزارشات و موتور جستجو نمایش داده می‌شود.',
        'department_id' => 'واحد سازمانی مرتبط با گزارش؛ در صورت خالی‌گذاشتن، گزارش عمومی تلقی می‌شود.',
        'user_id' => 'نویسنده یا مسئول ارائه این گزارش.',
        'active' => 'غیرفعال کردن گزارش آن را از دسترس عموم کاربران خارج می‌کند بدون اینکه حذف شود.',
        'cover_image' => 'تصویر جلد گزارش که در فهرست گزارش‌ها نمایش داده می‌شود؛ حداکثر حجم ۲ مگابایت.',
        'file_path' => 'فایل اصلی گزارش به فرمت PDF یا Word؛ حداکثر حجم ۵ مگابایت.',
    ],

    'export' => [
        'id' => 'شناسه',
        'title' => 'عنوان',
        'description' => 'توضیحات',
        'department' => 'واحد سازمانی',
        'user' => 'کاربر',
        'file_type' => 'نوع فایل',
        'active' => 'وضعیت',
        'created_at' => 'تاریخ ایجاد',
    ],
];
