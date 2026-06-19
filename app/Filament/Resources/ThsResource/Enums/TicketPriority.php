<?php

namespace App\Filament\Resources\ThsResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TicketPriority: string implements HasColor, HasIcon, HasLabel
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function getLabel(): string
    {
        return match ($this) {
            self::Low => 'عادی',
            self::Medium => 'فوری',
            self::High => 'خیلی فوری',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Low => 'success',
            self::Medium => 'warning',
            self::High => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Low => 'heroicon-o-arrow-down-circle',
            self::Medium => 'heroicon-o-chart-bar-square',
            self::High => 'heroicon-o-arrow-up-circle',
        };
    }
}
