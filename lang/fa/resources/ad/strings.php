<?php

return [
    'label'        => 'آگهی',
    'plural_label' => 'آگهی‌ها',
    'nav_group'    => 'مدیریت محتوا',
    'untitled'     => 'بدون عنوان',

    'fields' => [
        'position'       => 'عنوان موقعیت',
        'position_empty' => 'تعریف نشده',
        'certificate'    => 'مدرک تحصیلی',
        'skill'          => 'مهارت‌های مورد نیاز',
        'experience'     => 'سابقه کاری',
        'gender'         => 'جنسیت',
        'link'           => 'لینک آگهی',
        'active'         => 'وضعیت',
        'created_at'     => 'تاریخ ایجاد',
        'updated_at'     => 'آخرین ویرایش',
    ],

    'gender' => [
        'male'   => 'آقایان',
        'female' => 'خانم‌ها',
        'any'    => 'همه',
    ],

    'status' => [
        'active'   => 'فعال',
        'inactive' => 'غیرفعال',
    ],

    'form' => [
        'section_main'                    => 'اطلاعات اصلی',
        'section_main_description'        => 'مشخصات کلی آگهی و نحوه دسترسی به آن',
        'section_requirements'            => 'شرایط احراز',
        'section_requirements_description'=> 'مدرک، مهارت و سابقه مورد نیاز برای این موقعیت',
    ],

    'infolist' => [
        'tab_main'         => 'اطلاعات اصلی',
        'section_main'     => 'مشخصات آگهی',
        'tab_requirements' => 'شرایط احراز',
        'tab_meta'         => 'اطلاعات ثبت',
    ],

    'filters' => [
        'has_experience'   => 'دارای سابقه کاری',
        'has_certificate'  => 'دارای شرط مدرک',
        'has_skill'        => 'دارای شرط مهارت',
        'from'             => 'از تاریخ',
        'until'            => 'تا تاریخ',
    ],

    'validation' => [
        'link_required'   => 'لینک آگهی الزامی است.',
        'link_url'        => 'لینک وارد شده معتبر نیست.',
        'gender_required' => 'جنسیت الزامی است.',
    ],

    'export' => [
        'completed' => ':count ردیف با موفقیت خروجی گرفته شد.',
    ],
];
