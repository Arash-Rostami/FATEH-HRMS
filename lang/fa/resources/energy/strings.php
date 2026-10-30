<?php

return [
    'label' => 'پرسشنامه انرژی',
    'plural_label' => 'پرسشنامه انرژی',
    'nav_group'    => 'سیستم‌ها و ابزارها',

    'fields' => [
        'id' => 'شناسه',
        'user' => 'کاربر',
        'overall_score' => 'امتیاز کلی',
        'mind_score' => '🧠 ذهن ',
        'emotion_score' => '❤️ احساس',
        'physique_score' => '🏋️‍♂️ جسم',
        'soul_score' => '✨ روح ',
        'month_index' => 'ماه',
        'answers' => 'خلاصهٔ پاسخ‌ها',
        'completed_at' => 'تاریخ تکمیل',
        'created_at' => 'تاریخ ثبت',
    ],

    'tabs' => [
        'all' => 'همه',
        'high' => 'پرخطر (۱۲ و بالاتر)',
        'medium' => 'متوسط (۹–۱۱)',
        'low' => 'مطلوب (زیر ۹)',
        'last_month' => '۳۰ روز اخیر',
    ],

    'filters' => [
        'last_month' => '۳۰ روز اخیر',
        'low_score' => 'امتیاز مطلوب (۸ یا کمتر)',
        'score_range' => 'بازهٔ امتیاز',
        'score_min' => 'حداقل',
        'score_max' => 'حداکثر',
        'date_range' => 'بازهٔ تاریخ تکمیل',
        'date_from' => 'از تاریخ',
        'date_until' => 'تا تاریخ',
    ],

    'stats' => [
        'total' => 'کل پرسشنامه‌ها',
        'avg_overall' => 'میانگین کلی',
        'out_of_100' => 'از 20',
        'dimension_averages' => 'میانگین ابعاد',
        'low_scores' => 'پرخطر',
        'above_12' => '۱۲ و بالاتر',
        'last_month' => '۳۰ روز اخیر',
    ],

    'export' => [
        'completed' => ':count مورد با موفقیت صادر شد.',
    ],
];
