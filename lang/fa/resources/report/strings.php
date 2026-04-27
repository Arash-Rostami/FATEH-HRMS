<?php

return [
    'label'        => 'گزارش',
    'plural_label' => 'گزارش‌ها',
    'nav_group'    => 'مدیریت محتوا',

    'fields' => [
        'title'       => 'عنوان',
        'description' => 'توضیحات',
        'cover_image' => 'تصویر جلد',
        'department'  => 'واحد سازمانی',
        'file_path'   => 'فایل گزارش',
        'file_type'   => 'نوع فایل',
        'active'      => 'وضعیت',
        'user'        => 'کاربر',
        'thumbnail'   => 'پیش‌نمایش',
        'created_at'  => 'تاریخ ایجاد',
        'updated_at'  => 'تاریخ ویرایش',
    ],

    'form' => [
        'section_main'        => 'اطلاعات گزارش',
        'section_description' => 'اطلاعات پایه گزارش را وارد کنید.',
        'section_files'       => 'فایل‌ها',
        'tab_main'            => 'اطلاعات اصلی',
        'tab_files'           => 'فایل‌ها',
        'title_required'      => 'عنوان گزارش الزامی است.',
        'file_path_required'  => 'فایل گزارش الزامی است.',
    ],

    'placeholders' => [
        'description' => 'توضیح گزارش خود را اینجا بنویسید... ',
    ],

    'helper_text' => [
        'description' => '.⚠️ می‌توانید از قالب‌بندی متن، لینک و جدول برای بهبود آن استفاده کنید.',
    ],

    'validation' => [
        'report_required'      => 'شرح گزارش الزامی است و نمی‌تواند خالی باشد.',
        'report_max_length'    => 'طول متن گزارش نباید بیشتر از :length کاراکتر باشد (تگ‌های HTML نیز محاسبه می‌شوند).',
    ],

    'table' => [
        'title'      => 'عنوان',
        'department' => 'واحد',
        'user'       => 'کاربر',
        'file_type'  => 'نوع فایل',
        'active'     => 'وضعیت',
        'created_at' => 'تاریخ ایجاد',
    ],

    'infolist' => [
        'section_main' => 'جزئیات گزارش',
    ],

    'filters' => [
        'active'          => 'وضعیت',
        'active_active'   => 'فعال',
        'active_inactive' => 'غیرفعال',
        'department'      => 'واحد سازمانی',
        'created_from'    => 'از تاریخ',
        'created_until'   => 'تا تاریخ',
    ],

    'groups' => [
        'active'     => 'وضعیت',
        'department' => 'واحد سازمانی',
    ],

    'navigation' => [
        'singular' => 'گزارش',
    ],

    'export' => [
        'id'          => 'شناسه',
        'title'       => 'عنوان',
        'description' => 'توضیحات',
        'department'  => 'واحد سازمانی',
        'user'        => 'کاربر',
        'file_type'   => 'نوع فایل',
        'active'      => 'وضعیت',
        'created_at'  => 'تاریخ ایجاد',
    ],
];
