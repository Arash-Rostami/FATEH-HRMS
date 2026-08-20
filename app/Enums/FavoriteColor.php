<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FavoriteColor: string implements HasLabel, HasColor
{
    case DarkRed = '#b82224';
    case Maroon = '#881951';
    case LightPink = '#f9bbd2';
    case LightPurple = '#d2c4de';
    case DarkPurple = '#4c2d8a';
    case NavyBlue = '#002060';
    case MediumBlue = '#11499c';
    case Blue = '#0070c0';
    case LightBlue = '#00b0f0';
    case VeryLightBlue = '#d1e7ef';
    case TealBlue = '#156082';
    case Teal = '#57bdb8';
    case DarkGreen = '#0c4e42';
    case Green = '#00b050';
    case Yellow = '#ffff00';
    case Gold = '#ffc000';
    case Orange = '#f26f1f';
    case Red = '#ff0000';
    case Brown = '#513531';
    case TailwindRed = '#ef4444';
    case TailwindBlue = '#3b82f6';
    case TailwindEmerald = '#10b981';
    case TailwindAmber = '#f59e0b';
    case Black = '#000000';
    case White = '#ffffff';
    case TailwindViolet = '#8b5cf6';
    case TailwindPink = '#ec4899';
    case TailwindSlate = '#64748b';
    case TailwindTeal = '#14b8a6';

    public function getColor(): string|array|null
    {
        return $this->value;
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DarkRed => 'قرمز تیره',
            self::Maroon => 'زرشکی',
            self::LightPink => 'صورتی روشن',
            self::LightPurple => 'بنفش روشن',
            self::DarkPurple => 'بنفش تیره',
            self::NavyBlue => 'سرمه‌ای',
            self::MediumBlue => 'آبی متوسط',
            self::Blue => 'آبی',
            self::LightBlue => 'آبی روشن',
            self::VeryLightBlue => 'آبی خیلی روشن',
            self::TealBlue => 'سبزآبی تیره',
            self::Teal => 'فیروزه‌ای',
            self::DarkGreen => 'سبز تیره',
            self::Green => 'سبز',
            self::Yellow => 'زرد',
            self::Gold => 'طلایی',
            self::Orange => 'نارنجی',
            self::Red => 'قرمز',
            self::Brown => 'قهوه‌ای',
            self::TailwindRed => 'قرمز (تیلویند)',
            self::TailwindBlue => 'آبی (تیلویند)',
            self::TailwindEmerald => 'زمردی (تیلویند)',
            self::TailwindAmber => 'کهربایی (تیلویند)',
            self::Black => 'مشکی',
            self::White => 'سفید',
            self::TailwindViolet => 'بنفش (تیلویند)',
            self::TailwindPink => 'صورتی (تیلویند)',
            self::TailwindSlate => 'طوسی (تیلویند)',
            self::TailwindTeal => 'سبزآبی (تیلویند)',
        };
    }
}
