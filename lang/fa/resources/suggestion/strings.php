<?php

return [
    'label'        => 'پیشنهاد',
    'plural_label' => 'پیشنهادات',
    'nav_group'         => 'محتوا و ارتباطات',

    'fields' => [
        'serial'           => 'شناسه',
        'title'            => 'عنوان',
        'description'      => 'متن پیشنهاد',
        'departments'      => 'واحدهای ذی‌نفع',
        'purpose'          => 'اهداف پیشنهاد',
        'rule'             => 'اصول حاکم',
        'attachment'       => 'پیوست',
        'self_fill'        => 'تکمیل شخصی نظرات واحدها',
        'priority'         => 'اولویت',
        'stage'            => 'مرحله',
        'submitter'        => 'پیشنهاددهنده',
        'submitter_dept'   => 'واحد پیشنهاددهنده',
        'comments'         => 'یادداشت مدیریت',
        'created_at'       => 'تاریخ ثبت',
        'updated_at'       => 'آخرین به‌روزرسانی',
        'deadline'         => 'مهلت بررسی',
        'agree_count'      => 'موافق',
        'disagree_count'   => 'مخالف',
        'neutral_count'    => 'نیمه موافق',
        'sent_to_ceo'      => 'ارجاع به مدیریت ارشد',
        'referral_depts'   => 'واحدهای مسئول اجرا',
        'referral_actions' => 'دستورالعمل اجرا',
        'user_locked' => 'پیشنهاددهنده همیشه خود شما هستید و قابل تغییر نیست. برای ثبت پیشنهاد از طرف کاربر دیگر، به صفحهٔ همان کاربر بروید (کاربران ← کاربر مورد نظر ← زبانهٔ پیشنهادها) و پیشنهاد را از آنجا ثبت کنید.',
    ],

    'form' => [
        'section_main'       => 'محتوای پیشنهاد',
        'section_meta'       => 'دسته‌بندی و قواعد',
        'section_targets'    => 'واحدهای ذی‌نفع',
        'section_attachment' => 'پیوست',
        'self_fill_helper' => 'استفاده از این قابلیت در پنل مدیریت امکان‌پذیر نیست.',
        'departments_helper' => 'واحد پیشنهاددهنده به‌صورت خودکار به فهرست اضافه می‌شود.',
    ],

    'infolist' => [
        'section_overview' => 'خلاصهٔ پیشنهاد',
        'section_content'  => 'محتوای پیشنهاد',
        'section_workflow' => 'گردش کار',
        'section_reviews'  => 'بازخوردها',
        'section_decision' => 'تصمیم مدیریت ارشد',
        'no_reviews'       => 'هنوز بازخوردی ثبت نشده است.',
        'no_referral'      => 'بدون ارجاع برای اقدام',
        'tab_overview'     => 'پیشنهاد',
        'tab_reviews'      => 'بازخوردها و تصمیم',
    ],

    'tabs' => [
        'all'               => 'همه',
        'pending'           => 'ارسال شده',
        'team_remarks'      => 'منتظر واحد پیشنهاددهنده',
        'dept_remarks'      => 'منتظر واحدهای ذی‌نفع',
        'awaiting_decision' => 'منتظر تصمیم نهایی',
        'accepted'          => 'پذیرفته شده',
        'rejected'          => 'پذیرفته نشده',
        'under_review'      => 'نیازمند تکمیل',
        'closed'            => 'پایان‌یافته',
    ],

    'filters' => [
        'stage'        => 'مرحله',
        'department'   => 'واحد ذی‌نفع',
        'submitter'    => 'پیشنهاددهنده',
        'has_referral' => 'دارای ارجاع برای اقدام',
        'self_fill'    => 'تکمیل شخصی',
        'has_file'     => 'دارای پیوست',
    ],

    'actions' => [
        'feedback'                  => 'ثبت بازخورد',
        'feedback_heading'          => 'ثبت بازخورد واحد',
        'feedback_description'      => 'پس از ثبت بازخورد، مرحله پیشنهاد به‌روزرسانی می‌شود.',
        'decision'                  => 'تصمیم نهایی',
        'decision_heading'          => 'تصمیم مدیریت ارشد',
        'decision_description'      => 'پس از ثبت تصمیم، در صورت پذیرش می‌توان واحدهای مسئول اجرا را تعیین کرد.',
        'mark_complete'             => 'تکمیل اقدام',
        'mark_complete_heading'     => 'تکمیل اقدام واحد',
        'mark_complete_description' => 'این اقدام را «انجام‌شده» اعلام می‌کنید؟',
    ],

    'feedback' => [
        'label'      => 'بازخورد',
        'comment'    => 'توضیحات بازخورد',
        'agree'      => 'موافق',
        'neutral'    => 'نیمه موافق',
        'disagree'   => 'مخالف',
        'incomplete' => 'ناقص',
        'unknown'    => 'نامشخص',
    ],

    'decision' => [
        'decision'         => 'تصمیم',
        'comment'          => 'یادداشت مدیریت',
        'referral_depts'   => 'ارجاع به واحدها',
        'referral_actions' => 'دستورالعمل اجرا',
        'options' => [
            'accepted'     => 'پذیرفته شده',
            'rejected'     => 'پذیرفته نشده',
            'under_review' => 'نیازمند تکمیل',
        ],
    ],

    'notifications' => [
        'feedback_saved'   => 'بازخورد ثبت شد.',
        'decision_saved'   => 'تصمیم نهایی ثبت شد.',
        'completion_saved' => 'اقدام تکمیل‌شده ثبت شد.',
    ],

    'hints' => [
        'title'       => 'عنوانی کوتاه و گویا برای پیشنهاد بنویسید (حداکثر ۲۵۵ نویسه).',
        'description' => 'متن کامل پیشنهاد را اینجا بنویسید. می‌توانید متن را قالب‌بندی کنید و جدول هم بگذارید.',
        'priority'    => 'میزان اهمیت و فوریت اجرای این پیشنهاد را مشخص کنید.',
        'purpose'     => 'اهدافی که این پیشنهاد دنبال می‌کند را بنویسید (حداقل یک هدف لازم است).',
        'rule'        => 'اصول و چارچوب‌هایی که این پیشنهاد بر پایهٔ آن‌ها نوشته شده را بنویسید (حداقل یک مورد لازم است).',
        'attachment'  => 'فایل مرتبط با پیشنهاد را پیوست کنید (فقط PDF، PNG یا JPG، حداکثر ۱۰ مگابایت).',
    ],

    'export' => [
        'completed' => ':count مورد با موفقیت صادر شد.',
    ],

    'errors' => [
        'ma_restricted' => 'اعضای واحد مدیریت ارشد امکان ثبت پیشنهاد ندارند.',
    ],
];
