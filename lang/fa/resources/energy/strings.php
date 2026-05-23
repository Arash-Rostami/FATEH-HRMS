<?php

return [
    'label' => 'پرسشنامه انرژی',
    'plural_label' => 'پرسشنامه انرژی',
    'nav_group'    => 'سیستم‌ها و ابزارها',

    'fields' => [
        'id' => 'شناسه',
        'user' => 'کاربر',
        'overall_score' => 'امتیاز کلی',
        'mind_score' => '🧠ذهن ',
        'emotion_score' => '❤️احساس',
        'physique_score' => '🏋️‍♂جسم ️',
        'soul_score' => '✨روح ',
        'month_index' => 'ماه',
        'answers' => 'خلاصه پاسخ‌ها',
        'completed_at' => 'تکمیل شده در',
        'created_at' => 'تاریخ ثبت',
    ],

    'tabs' => [
        'all' => 'همه',
        'high' => 'پرخطر (۱۲+)',
        'medium' => 'متوسط (۹–۱۱)',
        'low' => 'مطلوب (زیر ۹)',
        'last_month' => '۳۰ روز اخیر',
    ],

    'filters' => [
        'last_month' => '۳۰ روز اخیر',
        'low_score' => 'امتیاز پایین (زیر ۴۵)',
        'score_range' => 'بازه امتیاز',
        'score_min' => 'حداقل',
        'score_max' => 'حداکثر',
        'date_range' => 'بازه تاریخ تکمیل',
        'date_from' => 'از تاریخ',
        'date_until' => 'تا تاریخ',
    ],

    'stats' => [
        'total' => 'کل تست‌ها',
        'avg_overall' => 'میانگین کلی',
        'out_of_100' => 'از 20',
        'dimension_averages' => 'میانگین ابعاد',
        'low_scores' => ' پرخطر',
        'above_12' => 'بالای 12',
        'last_month' => 'ماه اخیر',
    ],

    'export' => [
        'completed' => ':count ردیف با موفقیت صادر شد.',
    ],
];
