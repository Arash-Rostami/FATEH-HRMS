<?php

return [
    'label'             => 'تسک بورد',
    'plural_label'      => 'تسک بورد',
    'nav_group'      => 'عملیات و منابع',
    'nav_badge_tooltip' => 'وظایف سررسید گذشته',

    'fields' => [
        'id' => 'شناسه',
        'title'         => 'عنوان',
        'description'   => 'توضیحات',
        'status'        => 'وضعیت',
        'creator'       => 'ایجادکننده',
        'assignee'      => 'مسئول انجام',
        'assignee_hint' => 'در صورت خالی ماندن، وظیفه به ایجادکننده تعلق دارد.',
        'self_assigned' => 'خود ایجادکننده',
        'delegated'     => 'تفویض‌شده',
        'deadline'      => 'سررسید',
        'deadline_date' => 'تاریخ سررسید',
        'deadline_time' => 'ساعت سررسید',
        'created_at'    => 'تاریخ ایجاد',
        'updated_at'    => 'آخرین بروزرسانی',
        'deleted_at'    => 'تاریخ حذف',
    ],

    'form' => [
        'section_meta'     => 'کاربران و وضعیت',
        'section_content'  => 'محتوای وظیفه',
        'section_deadline' => 'سررسید',
    ],

    'infolist' => [
        'section_meta'     => 'اطلاعات وظیفه',
        'section_content'  => 'محتوا',
        'section_deadline' => 'سررسید',
        'section_meta2'    => 'تاریخچه سیستمی',
    ],

    'tabs' => [
        'all'         => 'همه',
        'todo'        => 'انجام نشده',
        'in_progress' => 'در حال انجام',
        'done'        => 'انجام شده',
        'overdue'     => 'سررسید گذشته',
        'delegated'   => 'تفویض‌شده',
        'trashed'     => 'حذف‌شده',
    ],

    'filters' => [
        'delegated'      => 'تفویض‌شده به دیگری',
        'not_delegated'  => 'تفویض‌نشده',
        'overdue'        => 'سررسید گذشته',
        'deadline_range' => 'بازه سررسید',
        'date_from'      => 'از تاریخ',
        'date_until'     => 'تا تاریخ',
    ],

    'validation' => [
        'user_id' => [
            'required' => 'انتخاب ایجادکننده وظیفه الزامی است.',
        ],

        'status' => [
            'required' => 'انتخاب وضعیت وظیفه الزامی است.',
        ],

        'title' => [
            'required'   => 'عنوان وظیفه الزامی است.',
            'max_length' => 'عنوان وظیفه نباید بیش از ۲۵۵ کاراکتر باشد.',
        ],

        'description' => [
            'max_length' => 'توضیحات نباید بیش از ۵۰۰۰ کاراکتر باشد.',
        ],

        'deadline_time' => [
            'invalid' => 'فرمت ساعت سررسید معتبر نیست.',
        ],
    ],

    'export' => [
        'completed' => ':count ردیف با موفقیت صادر شد.',
    ],
];
