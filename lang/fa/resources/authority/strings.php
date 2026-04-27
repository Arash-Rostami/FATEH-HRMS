<?php

return [
    'label'        => 'اختیار',
    'plural_label' => 'اختیارات',
    'nav_group'    => 'مدیریت سازمانی',

    'fields' => [
        'department'            => 'واحد سازمانی',
        'user'                  => 'مسئول',
        'sub_duty'              => 'وظایف زیرمجموعه',
        'duty'                  => 'شرح وظیفه',
        'execution_procedure'   => 'روش اجرایی',
        'repeat_frequency'      => 'فراوانی تکرار',
        'impact_score'          => 'شاخص اثر',
        'proposed_delegation'   => 'تفویض پیشنهادی',
        'approved_delegation'   => 'تفویض مصوب',
        'co_delegate'           => 'تفویض مشترک',
        'created_at'            => 'تاریخ ایجاد',
        'updated_at'            => 'آخرین بروزرسانی',
        'not_set'               => 'داده نشده',
    ],

    'form' => [
        'section_general'    => 'اطلاعات کلی',
        'section_details'    => 'جزئیات ',
        'section_delegation' => 'تفویض اختیار',
    ],

    'infolist' => [
        'section_general'    => 'اطلاعات کلی',
        'section_details'    => 'جزئیات ',
    ],

    'filters' => [
        'sub_duty_true'  => 'وظایف زیرمجموعه',
        'sub_duty_false' => 'وظایف مستقیم',
    ],


    'placeholders' => [
        'description' => 'توضیح  خود را اینجا بنویسید... ',
    ],

    'helper_text' => [
        'description' => '.⚠️ می‌توانید از قالب‌بندی متن، لینک و جدول برای بهبود آن استفاده کنید.',
    ],

    'validation' => [
        'authority_required'      => 'شرح الزامی است و نمی‌تواند خالی باشد.',
        'authority_max_length'    => 'طول متن نباید بیشتر از :length کاراکتر باشد (تگ‌های HTML نیز محاسبه می‌شوند).',
    ],

    'export' => [
        'completed' => ':count ردیف با موفقیت صادر شد.',
    ],
];
