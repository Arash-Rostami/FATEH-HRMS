<?php

namespace App\Filament\Resources\AuthorityResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum RepeatFrequency: string implements HasColor, HasIcon, HasLabel
{
    case Yearly = 'yearly';
    case Biyearly = 'biyearly';
    case Quarterly = 'quarterly';
    case FiveTimesYear = '5_times_a_year';
    case Frequent = 'frequent';
    case Regular = 'regular';
    case Occasional = 'occasional';

    public function getColor(): string
    {
        return match ($this) {
            self::Frequent => 'danger',
            self::Regular => 'primary',
            self::FiveTimesYear => 'warning',
            self::Quarterly,
            self::Biyearly,
            self::Yearly => 'info',
            self::Occasional => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Yearly => 'heroicon-o-calendar',
            self::Biyearly => 'heroicon-o-calendar-days',
            self::Quarterly => 'heroicon-o-chart-bar',
            self::FiveTimesYear => 'heroicon-o-squares-2x2',
            self::Frequent => 'heroicon-o-arrow-path',
            self::Regular => 'heroicon-o-clock',
            self::Occasional => 'heroicon-o-star',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Yearly => 'یک بار در سال',
            self::Biyearly => 'دو بار در سال',
            self::Quarterly => 'فصلی',
            self::FiveTimesYear => 'پنج بار در سال',
            self::Frequent => 'بیشتر از ۴ بار در ماه',
            self::Regular => 'بین ۱ تا ۴ بار در ماه',
            self::Occasional => 'کمتر از ۱ بار در ماه',
        };
    }
}
