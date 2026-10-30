<?php

return [
    'label'        => 'فید',
    'plural_label' => 'فیدها',
    'nav_group'         => 'محتوا و ارتباطات',

    'fields' => [
        'user'            => 'کاربر',
        'creator'            => 'ثبت‌کننده',
        'category'        => 'دسته‌بندی',
        'content'         => 'محتوا',
        'poll_options'    => 'گزینه‌های نظرسنجی',
        'poll_option'     => 'گزینه',
        'poll_mode'            => 'نحوهٔ رأی‌دهی',
        'poll_comments_enabled'  => 'فعال بودن نظرات',
        'poll_reactions_enabled' => 'فعال بودن واکنش‌ها',
        'poll_settings'   => 'تنظیمات نظرسنجی',
        'media'           => 'رسانه',
        'media_images'    => 'تصاویر',
        'media_videos'    => 'ویدیوها',
        'video_count'     => ':count ویدیو',
        'comment_content' => 'متن نظر',
        'comments_count'  => 'نظرات',
        'reactions_count' => 'واکنش‌ها',
        'polls_count'     => 'آرای نظرسنجی',
        'replies_count'   => 'تعداد پاسخ‌ها',
        'emoji'           => 'ایموجی',
        'in_reply_to'     => 'پاسخ به',
        'top_level_comment' => 'نظر اصلی',
        'type'            => 'نوع',
        'comment'         => 'نظر',
        'reply'           => ' پاسخ به نظر',
        'created_at'      => 'تاریخ ایجاد',
        'updated_at'      => 'آخرین به‌روزرسانی',
    ],

    'form' => [
        'section_author'  => 'دسته‌بندی و محتوا',
        'section_content' => 'محتوا',
        'section_media'   => 'رسانه',
    ],

    'infolist' => [
        'section_author'  => 'اطلاعات انتشار',
        'section_content' => 'محتوا',
        'section_media'   => 'رسانه',
        'section_meta'    => 'اطلاعات ثبت',
    ],

    'filters' => [
        'date_range'  => 'بازه تاریخ',
        'date_from'   => 'از تاریخ',
        'date_until'  => 'تا تاریخ',
        'has_media'   => 'دارای رسانه',
    ],

    'placeholders' => [
        'content' => 'محتوای فید را بنویسید...',
    ],

    'helper_text' => [
        'content' => 'از ابزار قالب‌بندی برای پررنگ کردن، ساخت فهرست و افزودن پیوند استفاده کنید.',
    ],

    'actions' => [
        'add_poll_option' => 'افزودن گزینه',
    ],

    'hints' => [
        'category' => 'دسته‌بندی شکل نمایش فید را مشخص می‌کند؛ برای نظرسنجی حتماً گزینه‌های نظرسنجی را اضافه کنید.',
        'user_id' => 'نویسندهٔ فید؛ پس از انتشار قابل تغییر نیست و در ویرایش فقط نمایش داده می‌شود.',
        'poll_options' => 'حداقل ۲ و حداکثر ۱۰ گزینه برای نظرسنجی وارد کنید.',
        'poll_mode' => 'تک‌انتخابی: کاربر فقط یک گزینه را انتخاب می‌کند. چندانتخابی: کاربر می‌تواند هم‌زمان چند گزینه را انتخاب کند.',
        'poll_comments_enabled' => 'کاربران بتوانند برای این نظرسنجی نظر بنویسند.',
        'poll_reactions_enabled' => 'کاربران بتوانند با ایموجی به این نظرسنجی واکنش نشان دهند.',
        'media_images' => 'حداکثر ۸ تصویر با فرمت JPG، PNG، GIF یا WEBP قابل بارگذاری است. حجم هر تصویر حداکثر ۵ مگابایت باشد.',
        'media_videos' => 'یک ویدیو با فرمت MP4، WEBM، MOV یا AVI و حجم حداکثر ۹۵ مگابایت بارگذاری کنید.',
    ],

    'poll' => [
        'vote_unit'   => 'رأی',
        'total_votes' => 'کل آرا',
        'voter'       => 'رأی‌دهنده',
        'votes_count' => 'تعداد آرا',
        'mode_single'    => 'تک‌انتخابی',
        'mode_multiple'  => 'چندانتخابی',
        'comments_on'    => 'نظرات فعال',
        'comments_off'   => 'نظرات غیرفعال',
        'reactions_on'   => 'واکنش‌ها فعال',
        'reactions_off'  => 'واکنش‌ها غیرفعال',
        'filter_voter_type' => 'نوع رأی‌دهنده',
        'filter_voter_mode' => 'نحوهٔ رأی‌دهی',
        'delete_voter_heading' => 'حذف آرای کاربر',
        'delete_voter_body'    => 'همهٔ آرای این کاربر در این نظرسنجی حذف می‌شود. این کار قابل بازگشت نیست.',
        'voter_deleted'         => 'آرای کاربر با موفقیت حذف شد.',
    ],

    'export' => [
        'completed' => ':count مورد با موفقیت خروجی گرفته شد.',
    ],
];
