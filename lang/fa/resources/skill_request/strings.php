<?php

return [
    'label'        => 'تأیید مهارت',
    'plural_label' => 'تأیید مهارت‌ها',
    'nav_group'    => 'کاربران و سازمان',

    'fields' => [
        'user'               => 'درخواست‌کننده',
        'skill'              => 'مهارت',
        'requested_name'     => 'نام درخواستی',
        'status'             => 'وضعیت',
        'created_at'         => 'تاریخ درخواست',
        'last_used_at'       => 'آخرین استفاده',
        'endorsements_count' => 'تأیید همکاران',
        'rejected_reason'    => 'دلیل رد',
        'approver'           => 'تأییدکننده',
        'department'         => 'واحد',
        'tier'               => 'سطح',
    ],

    'filters' => [
        'status'     => 'وضعیت',
        'department' => 'واحد',
        'stale'      => 'قدیمی (بیش از ۱۴ روز)',
    ],

    'actions' => [
        'approve'         => 'تأیید',
        'reject'          => 'رد',
        'approve_selected' => 'تأیید موارد انتخاب‌شده',
        'reject_selected'  => 'رد موارد انتخاب‌شده',
        'reject_heading'   => 'رد درخواست مهارت',
        'reject_description' => 'اگر می‌خواهید، دلیل رد را بنویسید.',
        'reason'           => 'دلیل رد (اختیاری)',
    ],

    'notifications' => [
        'approved' => 'درخواست مهارت «:skill» تأیید شد.',
        'rejected' => 'درخواست مهارت «:skill» رد شد.',
        'approve_done' => ':count درخواست تأیید شد.',
        'reject_done'  => ':count درخواست رد شد.',
        'nothing_pending' => 'هیچ درخواست «در حال بررسی»ای انتخاب نشده است.',
    ],
];