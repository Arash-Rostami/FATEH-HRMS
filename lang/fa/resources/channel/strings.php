<?php

return [
    'label'        => 'گروه',
    'plural_label' => 'گروه‌ها',
    'nav_group'    => 'محتوا و ارتباطات',

    'fields' => [
        'name'              => 'نام گروه',
        'slug'              => 'کد گروه',
        'description'       => 'توضیحات',
        'type'              => 'نوع گروه',
        'owner'             => 'مالک',
        'members_count'     => 'تعداد اعضا',
        'messages_count'    => 'تعدادپیام‌ها',
        'messages'    => 'پیام‌ها',
        'body'              => 'متن پیام',
        'sender'            => 'فرستنده',
        'user'              => 'کاربر',
        'member'              => 'اعضا',
        'is_edited'         => 'ویرایش‌شده',
        'joined_at'         => 'تاریخ عضویت',
        'entered_at'        => 'تاریخ ورود به گروه',
        'last_read_message' => 'آخرین پیام خوانده‌شده',
        'created_at'        => 'تاریخ ایجاد',
        'updated_at'        => 'آخرین به‌روزرسانی',
        'deleted_at'        => 'تاریخ حذف',
        'prune_status'      => 'وضعیت حذف خودکار',
    ],

    'form' => [
        'section_meta'    => 'اطلاعات گروه',
        'section_content' => 'محتوا',
    ],

    'filters' => [
        'type' => 'نوع گروه',
        'pruning_soon' => 'در آستانهٔ حذف خودکار',
    ],

    'hints' => [
        'slug' => 'کد انگلیسی یکتای گروه که در جستجو هم کاربرد دارد؛ فقط حروف انگلیسی، عدد، خط تیره و زیرخط.',
    ],

    'export' => [
        'completed' => ':count مورد با موفقیت صادر شد.',
    ],

    'notifications' => [
        'slug_copied' => 'کد گروه کپی شد.',
    ],
];
