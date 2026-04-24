<?php

namespace App\Filament\Resources\ProfileResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum EmploymentStatus: string implements HasColor, HasIcon, HasLabel
{
    case Probational = 'probational';
    case Working     = 'working';
    case Terminated  = 'terminated';

    public function getLabel(): string
    {
        return match ($this) {
            self::Probational => 'آزمایشی',
            self::Working     => 'در حال کار',
            self::Terminated  => 'خاتمه‌یافته',
        };
    }

    public function getColor(): string|array
    {
        return match ($this) {
            self::Probational => 'warning',
            self::Working     => 'success',
            self::Terminated  => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Probational => 'heroicon-o-clock',
            self::Working     => 'heroicon-o-check-circle',
            self::Terminated  => 'heroicon-o-x-circle',
        };
    }
}
