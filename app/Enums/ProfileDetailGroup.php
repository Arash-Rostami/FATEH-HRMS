<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ProfileDetailGroup: string implements HasColor, HasIcon, HasLabel
{
    case Identity = 'identity';
    case Employment = 'employment';
    case Experience = 'experience';
    case Education = 'education';
    case Skills = 'skills';
    case Family = 'family';
    case Health = 'health';
    case Military = 'military';
    case Financial = 'financial';
    case Other = 'other';

    public function getColor(): string|array
    {
        return match ($this) {
            self::Identity => 'info',
            self::Employment => 'warning',
            self::Experience => 'amber',
            self::Education => 'success',
            self::Skills => 'violet',
            self::Family => 'pink',
            self::Health => 'rose',
            self::Military => 'slate',
            self::Financial => 'emerald',
            self::Other => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Identity => 'heroicon-o-identification',
            self::Employment => 'heroicon-o-briefcase',
            self::Experience => 'heroicon-o-clock',
            self::Education => 'heroicon-o-academic-cap',
            self::Skills => 'heroicon-o-sparkles',
            self::Family => 'heroicon-o-users',
            self::Health => 'heroicon-o-heart',
            self::Military => 'heroicon-o-shield-check',
            self::Financial => 'heroicon-o-banknotes',
            self::Other => 'heroicon-o-ellipsis-horizontal',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Identity => 'اطلاعات هویتی تکمیلی',
            self::Employment => 'اطلاعات شغلی تکمیلی',
            self::Experience => 'سوابق کاری و بیمه',
            self::Education => 'تحصیلات',
            self::Skills => 'مهارت‌ها و دستاوردها',
            self::Family => 'خانواده و افراد تحت تکفل',
            self::Health => 'سلامت و وضعیت جسمانی',
            self::Military => 'نظام وظیفه',
            self::Financial => 'مالی و رفاهی',
            self::Other => 'سایر اطلاعات',
        };
    }

    public function materialIcon(): string
    {
        return match ($this) {
            self::Identity => 'badge',
            self::Employment => 'work',
            self::Experience => 'history',
            self::Education => 'school',
            self::Skills => 'auto_awesome',
            self::Family => 'family_restroom',
            self::Health => 'health_and_safety',
            self::Military => 'military_tech',
            self::Financial => 'payments',
            self::Other => 'more_horiz',
        };
    }

    public static function ordered(): array
    {
        return [
            self::Identity, self::Employment, self::Experience, self::Education,
            self::Skills, self::Family, self::Health, self::Military,
            self::Financial, self::Other,
        ];
    }
}
