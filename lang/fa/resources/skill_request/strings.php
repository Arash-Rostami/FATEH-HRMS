<?php

return [
    'label'        => 'تایید مهارت',
    'plural_label' => 'تایید مهارت‌ها',
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
        'stale'      => 'کهنه (بیش از ۱۴ روز)',
    ],

    'actions' => [
        'approve'         => 'تأیید',
        'reject'          => 'رد',
        'approve_selected' => 'تأیید انتخاب‌شده‌ها',
        'reject_selected'  => 'رد انتخاب‌شده‌ها',
        'reject_heading'   => 'رد درخواست مهارت',
        'reject_description' => 'در صورت تمایل دلیل رد را ثبت کنید.',
        'reason'           => 'دلیل رد (اختیاری)',
    ],

    'notifications' => [
        'approved' => 'درخواست مهارت «:skill» تأیید شد.',
        'rejected' => 'درخواست مهارت «:skill» رد شد.',
        'approve_done' => ':count درخواست تأیید شد.',
        'reject_done'  => ':count درخواست رد شد.',
        'nothing_pending' => 'هیچ درخواست در حال بررسی‌ای انتخاب نشده است.',
    ],
];