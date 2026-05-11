<?php

namespace App\Filament\Resources\ThsResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TicketStatus: string implements HasColor, HasIcon, HasLabel
{
    case Open = 'open';
    case InProgress = 'in-progress';
    case Closed = 'closed';

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'info',
            self::InProgress => 'warning',
            self::Closed => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Open => 'heroicon-o-envelope-open',
            self::InProgress => 'heroicon-o-arrow-path',
            self::Closed => 'heroicon-o-check-circle',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'باز',
            self::InProgress => 'در حال بررسی',
            self::Closed => 'بسته‌شده',
        };
    }
}
