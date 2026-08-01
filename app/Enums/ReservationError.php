<?php

namespace App\Enums;

enum ReservationError: string
{
    case UserInactive = 'ERR-001';
    case NoPermission = 'ERR-002';
    case WindowExceeded = 'ERR-003';
    case EarlyBooking = 'ERR-004';
    case DayNotAllowed = 'ERR-005';
    case FullDayForbidden = 'ERR-006';
    case TooShort = 'ERR-007';
    case TooLong = 'ERR-008';
    case HourNotAllowed = 'ERR-009';
    case RecurForbidden = 'ERR-010';
    case CancelLimitBooking = 'ERR-011';
    case ActiveLimitReached = 'ERR-012';
    case ResourceTaken = 'ERR-013';
    case TimeConflict = 'ERR-014';
    case NotActive = 'ERR-015';
    case CancelForbidden = 'ERR-016';
    case CancelLimitReached = 'ERR-017';
    case InvalidTimeRange   = 'ERR-018';
    case PastBooking        = 'ERR-019';
    case ResourceInactive   = 'ERR-020';
    case DataCorruption     = 'ERR-021';
    case TypeInactive       = 'ERR-022';

    public static function legend(): array
    {
        return [
            'ERR-001' => ['policy' => '—', 'hint' => 'وضعیت حساب کاربری را در پنل کاربران فعال کنید'],
            'ERR-002' => ['policy' => '—', 'hint' => 'مجوز رزرو کاربر را از پنل کاربران تنظیم کنید'],
            'ERR-003' => ['policy' => __('resources/policy/strings.fields.window_days'), 'hint' => 'مقدار را افزایش داده یا فیلد را خالی بگذارید'],
            'ERR-004' => ['policy' => __('resources/policy/strings.fields.window_hours'), 'hint' => 'مقدار را کاهش داده یا روی ۰ تنظیم کنید'],
            'ERR-005' => ['policy' => __('resources/policy/strings.fields.allowed_days'), 'hint' => 'روز مورد نظر را به لیست اضافه کرده یا همه را انتخاب کنید'],
            'ERR-006' => ['policy' => __('resources/policy/strings.fields.allow_full_day'), 'hint' => 'سوئیچ مربوطه را فعال کنید'],
            'ERR-007' => ['policy' => __('resources/policy/strings.fields.min_duration_minutes'), 'hint' => 'مقدار را کاهش داده یا فیلد را خالی بگذارید'],
            'ERR-008' => ['policy' => __('resources/policy/strings.fields.max_duration_minutes'), 'hint' => 'مقدار را افزایش داده یا فیلد را خالی بگذارید'],
            'ERR-009' => ['policy' => __('resources/policy/strings.fields.allowed_hours_start') . ' / ' . __('resources/policy/strings.fields.allowed_hours_end'), 'hint' => 'بازه ساعتی فعالیت را گسترش دهید'],
            'ERR-010' => ['policy' => __('resources/policy/strings.fields.allow_repeat'), 'hint' => 'سوئیچ مربوطه را فعال کنید'],
            'ERR-011' => ['policy' => __('resources/policy/strings.fields.max_cancel_count'), 'hint' => 'مقدار را افزایش داده یا فیلد را خالی بگذارید'],
            'ERR-012' => ['policy' => __('resources/policy/strings.fields.max_per_user'), 'hint' => 'مقدار را افزایش داده یا فیلد را خالی بگذارید'],
            'ERR-013' => ['policy' => __('resources/policy/strings.fields.allow_overlap_release'), 'hint' => 'در صورت نیاز به جایگزینی رزروهای لغو شده، این سوئیچ را فعال کنید'],
            'ERR-014' => ['policy' => '—', 'hint' => 'رزرو متداخل کاربر را از پنل مدیریت بررسی کنید'],
            'ERR-015' => ['policy' => '—', 'hint' => 'وضعیت رزرو را در پنل مدیریت بررسی کنید (احتمالاً قبلاً لغو شده)'],
            'ERR-016' => ['policy' => '—', 'hint' => 'مالکیت رزرو را از پنل مدیریت بررسی کنید'],
            'ERR-017' => ['policy' => __('resources/policy/strings.fields.max_cancel_count'), 'hint' => 'مقدار را افزایش داده یا فیلد را خالی بگذارید'],
            'ERR-018' => ['policy' => '—', 'hint' => 'زمان شروع و پایان را بررسی کنید'],
            'ERR-019' => ['policy' => '—', 'hint' => 'تاریخ رزرو باید در آینده باشد'],
            'ERR-020' => ['policy' => '—', 'hint' => 'وضعیت منبع را از پنل مدیریت بررسی کنید'],
            'ERR-021' => ['policy' => '—', 'hint' => 'داده‌های پایگاه داده را بررسی کنید (ارتباط خودارجاعی)'],
            'ERR-022' => ['policy' => __('resources/policy/strings.fields.window_days') . ' / ' . __('resources/policy/strings.fields.window_hours'), 'hint' => 'این نوع منبع غیرفعال است؛ برای فعال‌سازی حداقل یکی از این دو مقدار را بزرگ‌تر از صفر تنظیم کنید'],
        ];
    }

    public function message(mixed ...$args): string
    {
        $text = match ($this) {
            self::UserInactive => 'حساب کاربری شما در حال حاضر فعال نیست.',
            self::NoPermission => 'شما اجازه رزرو این نوع گزینه را ندارید.',
            self::WindowExceeded => "تاریخ انتخابی خارج از بازه مجاز (حداکثر {$args[0]} روز آینده) است.",
            self::EarlyBooking => "رزرو باید حداقل {$args[0]} ساعت قبل از زمان شروع ثبت شود.",
            self::DayNotAllowed => "رزرو در روز {$args[0]} برای این نوع منبع مجاز نیست.",
            self::FullDayForbidden => 'رزرو تمام‌روز برای این نوع منبع مجاز نیست.',
            self::TooShort => "حداقل مدت رزرو {$args[0]} دقیقه است.",
            self::TooLong => "حداکثر مدت رزرو {$args[0]} دقیقه است.",
            self::HourNotAllowed => "رزرو فقط بین ساعت {$args[0]} تا {$args[1]} مجاز است.",
            self::RecurForbidden => 'رزرو تکراری برای این نوع منبع مجاز نیست.',
            self::CancelLimitBooking => 'تعداد لغو رزروهای شما بیش از حد مجاز است؛ امکان ثبت رزرو جدید وجود ندارد.',
            self::ActiveLimitReached => 'سقف رزرو فعال شما برای این نوع به پایان رسیده است.',
            self::ResourceTaken => 'گزینه مورد نظر در بازه زمانی انتخاب شده در دسترس نیست.',
            self::TimeConflict => 'شما در این بازه زمانی رزرو فعال دیگری از همین نوع دارید.',
            self::NotActive => 'این رزرو در حال حاضر فعال نیست.',
            self::CancelForbidden => 'شما اجازه لغو این رزرو را ندارید.',
            self::CancelLimitReached => 'تعداد لغو رزروهای شما در این ماه به حداکثر مجاز رسیده است.',
            self::InvalidTimeRange   => 'زمان پایان نمی‌تواند قبل یا مساوی زمان شروع باشد.',
            self::PastBooking        => 'امکان رزرو در گذشته وجود ندارد.',
            self::ResourceInactive   => 'این منبع در حال حاضر در دسترس یا فعال نیست.',
            self::DataCorruption     => 'خطای سیستمی: ساختار داده‌ای این رزرو دچار مشکل شده است.',
            self::TypeInactive       => 'این نوع منبع در حال حاضر توسط مدیریت غیرفعال شده است.',
        };

        return "[{$this->value}] {$text}";
    }

    public function throw(mixed ...$args): never
    {
        throw new \Exception($this->message(...$args));
    }
}
