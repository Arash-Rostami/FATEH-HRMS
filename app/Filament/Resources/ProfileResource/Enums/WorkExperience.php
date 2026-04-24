<?php

namespace App\Filament\Resources\ProfileResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum WorkExperience: string implements HasColor, HasIcon, HasLabel
{
    case Student       = 'student';
    case ZeroToOne     = '0-1';
    case OneToTwo      = '1-2';
    case TwoToThree    = '2-3';
    case ThreeToFive   = '3-5';
    case FiveToSeven   = '5-7';
    case SevenToTen    = '7-10';
    case TenToFifteen  = '10-15';
    case FifteenToTwenty = '15-20';
    case TwentyPlus    = '20+';
    case Freelance     = 'freelance';
    case CareerChange  = 'career_change';

    public function getLabel(): string
    {
        return match ($this) {
            self::Student        => 'دانشجو / بدون سابقه',
            self::ZeroToOne      => 'کمتر از ۱ سال',
            self::OneToTwo       => '۱ تا ۲ سال',
            self::TwoToThree     => '۲ تا ۳ سال',
            self::ThreeToFive    => '۳ تا ۵ سال',
            self::FiveToSeven    => '۵ تا ۷ سال',
            self::SevenToTen     => '۷ تا ۱۰ سال',
            self::TenToFifteen   => '۱۰ تا ۱۵ سال',
            self::FifteenToTwenty => '۱۵ تا ۲۰ سال',
            self::TwentyPlus     => 'بیشتر از ۲۰ سال',
            self::Freelance      => 'فریلنس / پروژه‌ای',
            self::CareerChange   => 'تغییر مسیر شغلی / شروع مجدد',
        };
    }

    public function getColor(): string|array
    {
        return match ($this) {
            self::Student         => 'gray',
            self::ZeroToOne       => 'warning',
            self::OneToTwo        => 'warning',
            self::TwoToThree      => 'primary',
            self::ThreeToFive     => 'primary',
            self::FiveToSeven     => 'info',
            self::SevenToTen      => 'info',
            self::TenToFifteen    => 'success',
            self::FifteenToTwenty => 'success',
            self::TwentyPlus      => 'danger',
            self::Freelance       => 'secondary',
            self::CareerChange    => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Student         => 'heroicon-o-academic-cap',
            self::ZeroToOne       => 'heroicon-o-sparkles',
            self::OneToTwo        => 'heroicon-o-sparkles',
            self::TwoToThree      => 'heroicon-o-bolt',
            self::ThreeToFive     => 'heroicon-o-bolt',
            self::FiveToSeven     => 'heroicon-o-fire',
            self::SevenToTen      => 'heroicon-o-fire',
            self::TenToFifteen    => 'heroicon-o-trophy',
            self::FifteenToTwenty => 'heroicon-o-trophy',
            self::TwentyPlus      => 'heroicon-o-star',
            self::Freelance       => 'heroicon-o-globe-alt',
            self::CareerChange    => 'heroicon-o-arrow-path',
        };
    }
}
