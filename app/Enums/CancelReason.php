<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CancelReason: string implements HasLabel
{
    case UserRequest = 'user_request';
    case ScheduleChange = 'schedule_change';
    case DuplicateBooking = 'duplicate_booking';
    case NoLongerNeeded = 'no_longer_needed';
    case Emergency = 'emergency';
    case Sickness = 'sickness';
    case TravelChange = 'travel_change';
    case ResourceUnavailable = 'resource_unavailable';
    case Maintenance = 'maintenance';
    case AdminAction = 'admin_action';
    case PolicyViolation = 'policy_violation';
    case Overbooking = 'overbooking';
    case WrongTimeSelected = 'wrong_time_selected';
    case WrongResourceSelected = 'wrong_resource_selected';
    case SystemError = 'system_error';
    case TestBooking = 'test_booking';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::UserRequest => 'انصراف کاربر',
            self::ScheduleChange => 'تغییر برنامه زمانی',
            self::DuplicateBooking => 'رزرو تکراری',
            self::NoLongerNeeded => 'عدم نیاز',
            self::Emergency => 'موقعیت اضطراری',
            self::Sickness => 'بیماری و کسالت',
            self::TravelChange => 'تغییر برنامه سفر',
            self::ResourceUnavailable => 'خارج از دسترس بودن منبع',
            self::Maintenance => 'در دست تعمیر و نگهداری',
            self::AdminAction => 'لغو توسط مدیر سیستم',
            self::PolicyViolation => 'عدم رعایت قوانین مجموعه',
            self::Overbooking => 'تداخل با رزروهای دیگر',
            self::WrongTimeSelected => 'اشتباه در انتخاب تاریخ و ساعت',
            self::WrongResourceSelected => 'اشتباه در انتخاب منبع',
            self::SystemError => ' خطای سیستمی',
            self::TestBooking => 'تست عملکرد سیستم',
            self::Other => 'سایر موارد',
        };
    }
}
