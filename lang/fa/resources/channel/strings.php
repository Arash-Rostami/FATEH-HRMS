<?php

return [
    'label'        => 'گروه',
    'plural_label' => 'گروه‌ها',
    'nav_group'    => 'محتوا و ارتباطات',

    'fields' => [
        'name'              => 'نام گروه',
        'slug'              => 'شناسه یکتا',
        'description'       => 'توضیحات',
        'type'              => 'نوع گروه',
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
        'prune_status'      => 'وضعیت حذف خودکار',
    ],

    'form' => [
        'section_meta'    => 'اطلاعات گروه',
        'section_content' => 'محتوا',
    ],

    'filters' => [
        'type' => 'نوع گروه',
        'pruning_soon' => 'در آستانه حذف خودکار',
    ],

    'hints' => [
        'slug' => 'شناسه یکتای انگلیسی برای مسیر گروه؛ فقط حروف، اعداد و خط‌تیره.',
    ],

    'export' => [
        'completed' => ':count ردیف با موفقیت صادر شد.',
    ],

    'notifications' => [
        'slug_copied' => 'شناسه کپی شد.',
    ],
];
