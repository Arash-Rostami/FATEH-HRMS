<?php

namespace App\Enums;

enum LinkIcon: string
{
    case Link = 'link';
    case OpenInNew = 'open_in_new';
    case Public = 'public';
    case Apartment = 'apartment';
    case Language = 'language';
    case Hub = 'hub';
    case DatasetLinked = 'dataset_linked';
    case Share = 'share';
    case Launch = 'launch';
    case AltRoute = 'alt_route';
    case Cloud = 'cloud';
    case Storage = 'storage';
    case Dashboard = 'dashboard';
    case AccountTree = 'account_tree';

    public function getLabel(): string
    {
        return match ($this) {
            self::Link => 'لینک عمومی',
            self::OpenInNew => 'باز شدن در تب جدید',
            self::Public => 'وب / اینترنت',
            self::Apartment => 'سازمانی / داخلی',
            self::Language => 'زبان / وب‌سایت',
            self::Hub => 'هاب / مرکز',
            self::DatasetLinked => 'سامانهٔ متصل',
            self::Share => 'اشتراک‌گذاری',
            self::Launch => 'اجرا / راه‌اندازی',
            self::AltRoute => 'مسیر جایگزین',
            self::Cloud => 'ابری',
            self::Storage => 'ذخیره‌سازی',
            self::Dashboard => 'داشبورد',
            self::AccountTree => 'ساختار / نمودار',
        };
    }

    public static function default(): self
    {
        return self::Link;
    }
}
