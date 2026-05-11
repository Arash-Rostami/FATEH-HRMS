<?php

return [
    'label'        => 'تیکت',
    'plural_label' => 'تیکت‌ها',
    'nav_group'    => 'پشتیبانی',

    'fields' => [
        'ticket_id'        => 'شناسه تیکت',
        'click_to_copy'    => 'کلیک برای کپی',
        'requester'        => 'درخواست‌دهنده',
        'department'       => 'واحد سازمانی',
        'request_type'     => 'نوع درخواست',
        'request_area'     => 'حوزه درخواست',
        'priority'         => 'اولویت',
        'subject'          => 'موضوع',
        'description'      => 'شرح درخواست',
        'requester_files'  => 'فایل‌های درخواست‌دهنده',
        'assignee'         => 'مسئول رسیدگی',
        'unassigned'       => 'تخصیص‌نیافته',
        'status'           => 'وضعیت',
        'deadline'         => 'مهلت رسیدگی',
        'deadline_date'    => 'تاریخ مهلت',
        'deadline_time'    => 'ساعت مهلت',
        'completion_date'  => 'تاریخ بسته‌شدن',
        'effectiveness'    => 'اثربخشی',
        'satisfaction'     => 'رضایت',
        'action_result'    => 'نتیجه اقدام',
        'additional_notes' => 'یادداشت‌های تکمیلی',
        'assignee_files'   => 'فایل‌های مسئول',
        'file'             => 'فایل',
        'created_at'       => 'تاریخ ثبت',
        'updated_at'       => 'آخرین بروزرسانی',
    ],

    'form' => [
        'tab_request'        => 'درخواست',
        'tab_response'       => 'پاسخ',
        'section_requester'  => 'اطلاعات درخواست‌دهنده',
        'section_request'    => 'جزئیات درخواست',
        'section_assignment' => 'تخصیص و زمان‌بندی',
        'section_response'   => 'نتیجه و بازخورد',
    ],

    'infolist' => [
        'section_header'   => 'اطلاعات تیکت',
        'section_request'  => 'درخواست',
        'section_response' => 'پاسخ و نتیجه',
        'section_meta'     => 'اطلاعات سیستمی',
    ],

    'tabs' => [
        'all'         => 'همه',
        'open'        => 'باز',
        'in_progress' => 'در حال بررسی',
        'closed'      => 'بسته‌شده',
        'unassigned'  => 'تخصیص‌نیافته',
        'overdue'     => 'سررسید گذشته',
    ],

    'filters' => [
        'open_or_in_progress' => 'باز یا در حال بررسی',
        'overdue'             => 'سررسید گذشته',
        'assigned'            => 'وضعیت تخصیص',
        'assigned_yes'        => 'تخصیص‌یافته',
        'assigned_no'         => 'تخصیص‌نیافته',
        'date_range'          => 'بازه تاریخ ثبت',
        'date_from'           => 'از تاریخ',
        'date_until'          => 'تا تاریخ',
    ],

    'export' => [
        'completed' => ':count ردیف با موفقیت صادر شد.',
    ],

    'validation' => [
        'requester_id' => [
            'required' => 'انتخاب درخواست‌دهنده الزامی است.',
        ],

        'request_type' => [
            'required' => 'انتخاب نوع درخواست الزامی است.',
        ],

        'request_area' => [
            'required' => 'انتخاب حوزه درخواست الزامی است.',
        ],

        'priority' => [
            'required' => 'تعیین اولویت الزامی است.',
        ],

        'status' => [
            'required' => 'انتخاب وضعیت تیکت الزامی است.',
        ],

        'request_subject' => [
            'required'   => 'وارد کردن موضوع درخواست الزامی است.',
            'max_length' => 'موضوع درخواست نباید بیش از ۵۰۰ کاراکتر باشد.',
        ],

        'description' => [
            'required'   => 'وارد کردن شرح درخواست الزامی است.',
            'max_length' => 'شرح درخواست نباید بیش از ۵۰۰۰ کاراکتر باشد.',
        ],

        'action_result' => [
            'max_length' => 'نتیجه اقدام نباید بیش از ۵۰۰۰ کاراکتر باشد.',
        ],

        'additional_notes' => [
            'max_length' => 'یادداشت‌های تکمیلی نباید بیش از ۲۰۰۰ کاراکتر باشد.',
        ],

        'requester_files' => [
            'max_items' => 'حداکثر ۳ فایل از سوی درخواست‌دهنده مجاز است.',
            'max_size'  => 'حجم هر فایل نباید بیش از ۴ مگابایت باشد.',
            'mime_types' => 'فرمت فایل مجاز نیست. فرمت‌های قابل قبول: JPEG، PNG، GIF، WebP، SVG، PDF، Word، Excel.',
        ],

        'assignee_files' => [
            'max_items'  => 'حداکثر ۳ فایل از سوی مسئول رسیدگی مجاز است.',
            'max_size'   => 'حجم هر فایل نباید بیش از ۴ مگابایت باشد.',
            'mime_types' => 'فرمت فایل مجاز نیست. فرمت‌های قابل قبول: JPEG، PNG، GIF، WebP، SVG، PDF، Word، Excel.',
        ],

        'completion_deadline_time' => [
            'invalid' => 'فرمت ساعت مهلت رسیدگی معتبر نیست.',
        ],
    ],
];
