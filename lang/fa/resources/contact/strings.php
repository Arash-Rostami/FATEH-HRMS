<?php

return [
    'label'             => 'پیام',
    'plural_label'      => 'پیام‌ها',
    'nav_group'         => 'محتوا و ارتباطات',
    'nav_badge_tooltip' => 'پیام‌های خوانده‌نشده',

    'fields' => [
        'sender'     => 'فرستنده',
        'recipient'  => 'گیرنده',
        'body'       => 'متن پیام',
        'reply_to'   => 'در پاسخ به',
        'has_reply'  => 'پاسخ',
        'is_edited'  => 'ویرایش‌شده',
        'read_at'    => 'زمان خواندن',
        'unread'     => 'خوانده‌نشده',
        'created_at' => 'تاریخ ارسال',
        'updated_at' => 'آخرین بروزرسانی',
        'deleted_at' => 'تاریخ حذف',
        'prune_status' => 'وضعیت حذف خودکار',
    ],

    'form' => [
        'section_meta'    => 'طرفین گفتگو',
        'section_content' => 'محتوای پیام',
    ],

    'infolist' => [
        'section_meta'    => 'اطلاعات پیام',
        'section_content' => 'محتوا',
        'section_meta2'   => 'تاریخچه سیستمی',
    ],

    'tabs' => [
        'all'          => 'همه',
        'unread'       => 'خوانده‌نشده',
        'edited'       => 'ویرایش‌شده',
        'trashed'      => 'حذف‌شده',
        'pruning_soon' => 'در آستانه حذف',
    ],

    'filters' => [
        'read_status'   => 'وضعیت خواندن',
        'read'          => 'خوانده‌شده',
        'unread'        => 'خوانده‌نشده',
        'is_edited'     => 'وضعیت ویرایش',
        'edited'        => 'ویرایش‌شده',
        'not_edited'    => 'ویرایش‌نشده',
        'has_reply'     => 'نوع پیام',
        'is_reply'      => 'پاسخ',
        'not_reply'     => 'پیام اصلی',
        'date_range'    => 'بازه تاریخ',
        'date_from'     => 'از تاریخ',
        'date_until'    => 'تا تاریخ',
        'pruning_soon'  => 'در آستانه حذف خودکار',
    ],

    'validation' => [
        'body_required' => 'متن پیام الزامی است.',
    ],

    'export' => [
        'completed' => ':count ردیف با موفقیت صادر شد.',
    ],
];
