<?php

return [
    'label'        => 'گالری',
    'plural_label' => 'گالری‌ها',
    'nav_group'    => 'مدیریت محتوا',

    'fields' => [
        'title'          => 'عنوان',
        'description'    => 'توضیحات',
        'department'     => 'واحد سازمانی',
        'department_hint'=> 'در صورت انتخاب، گالری فقط برای کاربران آن واحد نمایش داده می‌شود.',
        'public_gallery' => 'عمومی',
        'event_date'     => 'تاریخ رویداد',
        'photos'         => 'تصاویر',
        'preview'        => 'پیش‌نمایش',
        'count'          => 'تعداد تصاویر',
        'created_at'     => 'تاریخ ایجاد',
        'updated_at'     => 'آخرین بروزرسانی',
    ],

    'form' => [
        'section_info'   => 'اطلاعات گالری',
        'section_photos' => 'تصاویر',
    ],

    'infolist' => [
        'section_info'   => 'اطلاعات گالری',
        'section_photos' => 'تصاویر',
        'section_meta'   => 'اطلاعات سیستمی',
    ],

    'filters' => [
        'visibility'       => 'دسترسی',
        'private'          => 'خصوصی (واحد سازمانی)',
        'public'           => 'عمومی',
        'event_date_range' => 'بازه تاریخ رویداد',
        'date_from'        => 'از تاریخ',
        'date_until'       => 'تا تاریخ',
    ],

    'validation' => [
        'title_required' => 'عنوان گالری الزامی است.',
    ],

    'export' => [
        'completed' => ':count ردیف با موفقیت صادر شد.',
    ],
];
