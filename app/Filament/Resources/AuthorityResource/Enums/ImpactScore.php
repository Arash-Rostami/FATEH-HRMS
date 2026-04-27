<?php

namespace App\Filament\Resources\AuthorityResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ImpactScore: string implements HasColor, HasIcon, HasLabel
{
    case VeryHigh = 'very_high';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    public function getLabel(): string
    {
        return match ($this) {
            self::VeryHigh => 'خیلی زیاد',
            self::High => 'زیاد',
            self::Medium => 'متوسط',
            self::Low => 'کم',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::VeryHigh => 'danger',
            self::High => 'primary',
            self::Medium => 'warning',
            self::Low => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::VeryHigh => 'heroicon-o-arrow-trending-up',
            self::High => 'heroicon-o-chart-bar',
            self::Medium => 'heroicon-o-bars-3',
            self::Low => 'heroicon-o-arrow-trending-down',
        };
    }
}
