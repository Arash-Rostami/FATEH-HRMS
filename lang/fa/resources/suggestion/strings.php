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
        'updated_at'       => 'آخرین بروزرسانی',
        'deadline'         => 'مهلت بررسی',
        'agree_count'      => 'موافق',
        'disagree_count'   => 'مخالف',
        'neutral_count'    => 'نیمه موافق',
        'sent_to_ceo'      => 'ارجاع به مدیریت ارشد',
        'referral_depts'   => 'واحدهای موظف به اقدام',
        'referral_actions' => 'دستورالعمل اجرا',
        'user_locked' => 'پیشنهاد دهنده به‌طور خودکار شما هستید و قابل تغییر نیست.',
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
        'section_overview' => 'خلاصه پیشنهاد',
        'section_content'  => 'محتوای پیشنهاد',
        'section_workflow' => 'گردش کار',
        'section_reviews'  => 'بازخوردها',
        'section_decision' => 'تصمیم مدیریت ارشد',
        'no_reviews'       => 'هنوز بازخوردی ثبت نشده است.',
        'no_referral'      => 'بدون ارجاع برای اقدام',
    ],

    'tabs' => [
        'all'               => 'همه',
        'pending'           => 'ارسال شده',
        'team_remarks'      => 'منتظر هم‌تیمی‌ها',
        'dept_remarks'      => 'منتظر ذینفعان',
        'awaiting_decision' => 'منتظر تصمیم',
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
        'decision_description'      => 'پس از ثبت تصمیم، در صورت پذیرش می‌توان واحدهای موظف به اقدام را تعیین کرد.',
        'mark_complete'             => 'تکمیل اقدام',
        'mark_complete_heading'     => 'تکمیل اقدام واحد',
        'mark_complete_description' => 'این اقدام را به عنوان «انجام‌شده» علامت می‌زنید؟',
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

    'validation' => [
        'title' => [
            'required' => 'وارد کردن عنوان الزامی است.',
            'min'      => 'عنوان باید حداقل ۳ کاراکتر باشد.',
            'max'      => 'عنوان نباید بیشتر از ۲۵۵ کاراکتر باشد.',
        ],
        'description' => [
            'required' => 'وارد کردن توضیحات الزامی است.',
            'min'      => 'توضیحات باید حداقل ۱۰ کاراکتر باشد.',
        ],
        'purpose' => [
            'required' => 'حداقل یک هدف باید انتخاب شود.',
        ],
        'rule' => [
            'required' => 'حداقل یک اصل باید انتخاب شود.',
        ],
        'attachment' => [
            'mimes' => 'فرمت فایل باید pdf، png یا jpg باشد.',
            'max'   => 'حجم فایل نباید بیشتر از ۲ مگابایت باشد.',
        ],
        'decision' => [
            'required' => 'انتخاب تصمیم الزامی است.',
        ],
        'feedback' => [
            'required' => 'انتخاب بازخورد الزامی است.',
        ],
        'user_id' => [
            'required' => 'انتخاب پیشنهاددهنده الزامی است.',
        ],
    ],

    'export' => [
        'completed' => ':count ردیف با موفقیت صادر شد.',
    ],
];
