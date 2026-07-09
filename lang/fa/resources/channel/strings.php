<?php

return [
    'label'        => 'کانال',
    'plural_label' => 'کانال‌ها',
    'nav_group'    => 'محتوا و ارتباطات',

    'fields' => [
        'name'              => 'نام کانال',
        'slug'              => 'شناسه یکتا',
        'description'       => 'توضیحات',
        'type'              => 'نوع کانال',
        'owner'             => 'مالک',
        'members_count'     => 'تعداد اعضا',
        'messages_count'    => 'تعداد پیام‌ها',
        'messages'    => ' پیام‌ها',
        'body'              => 'متن پیام',
        'sender'            => 'فرستنده',
        'user'              => 'کاربر',
        'member'              => 'اعضا',
        'is_edited'         => 'ویرایش‌شده',
        'joined_at'         => 'تاریخ عضویت',
        'entered_at'        => 'تاریخ ورود',
        'last_read_message' => 'آخرین پیام خوانده‌شده',
        'created_at'        => 'تاریخ ایجاد',
        'updated_at'        => 'آخرین بروزرسانی',
        'deleted_at'        => 'تاریخ حذف',
    ],

    'form' => [
        'section_meta'    => 'اطلاعات کانال',
        'section_content' => 'محتوا',
    ],

    'filters' => [
        'type' => 'نوع کانال',
    ],

    'hints' => [
        'slug' => 'شناسه یکتای انگلیسی برای مسیر کانال؛ فقط حروف، اعداد و خط‌تیره.',
    ],

    'export' => [
        'completed' => ':count ردیف با موفقیت صادر شد.',
    ],

    'notifications' => [
        'slug_copied' => 'شناسه کپی شد.',
    ],
];
