<?php

return [
    'label'        => 'فید',
    'plural_label' => 'فیدها',
    'nav_group'         => 'محتوا و ارتباطات',

    'fields' => [
        'user'            => 'کامنت گذار',
        'creator'            => 'ثبت کننده',
        'category'        => 'دسته‌بندی',
        'content'         => 'محتوا',
        'poll_options'    => 'گزینه‌های نظرسنجی',
        'poll_option'     => 'گزینه',
        'media'           => 'رسانه',
        'media_images'    => 'تصاویر',
        'media_videos'    => 'ویدیوها',
        'video_count'     => ':count ویدیو',
        'comment_content' => 'متن نظر',
        'comments_count'  => 'نظرات',
        'reactions_count' => 'واکنش‌ها',
        'replies_count'   => 'تعداد  پاسخ به نظر ',
        'emoji'           => 'ایموجی',
        'in_reply_to'     => 'پاسخ به',
        'top_level_comment' => 'نظر اصلی',
        'type'            => 'نوع',
        'comment'         => 'نظر',
        'reply'           => ' پاسخ به نظر',
        'created_at'      => 'تاریخ ایجاد',
        'updated_at'      => 'آخرین بروزرسانی',
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
        'section_meta'    => 'اطلاعات سیستمی',
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
        'content' => 'از ابزار فرمت‌بندی برای افزودن تأکید، لیست و لینک استفاده کنید.',
    ],

    'actions' => [
        'add_poll_option' => 'افزودن گزینه',
    ],

    'validation' => [
        'content' => [
            'required'   => 'محتوای فید الزامی است.',
            'max_length' => 'محتوا نباید بیشتر از ۱۰۰۰۰ کاراکتر باشد.',
        ],
        'category' => [
            'required' => 'دسته‌بندی فید الزامی است.',
        ],
        'user_id' => [
            'required' => 'انتخاب ثبت‌کننده فید الزامی است.',
        ],
    ],

    'export' => [
        'completed' => ':count ردیف با موفقیت صادر شد.',
    ],
];
