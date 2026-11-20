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
        'messages_count'    => 'تعداد پیام‌ها',
        'messages'    => 'پیام‌ها',
        'body'              => 'متن پیام',
        'sender'            => 'فرستنده',
        'user'              => 'کاربر',
        'member'              => 'اعضا',
        'is_edited'         => 'ویرایش‌شده',
        'role'              => 'نقش',
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
        'slug' => 'با این کد می‌توانید گروه را در جستجو پیدا کنید. کد باید متفاوت از گروه‌های دیگر باشد و فقط حرف انگلیسی، عدد، خط تیره یا زیرخط داشته باشد.',
    ],

    'export' => [
        'completed' => 'فایل اکسل برای :count گروه آماده شد.',
    ],

    'notifications' => [
        'slug_copied' => 'کد گروه کپی شد.',
    ],
];
