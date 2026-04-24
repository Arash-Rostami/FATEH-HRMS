<?php

namespace App\Filament\Resources\ProfileResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum Position: string implements HasColor, HasIcon, HasLabel
{
    case ChiefManager  = 'c-manager';
    case Manager       = 'manager';
    case Supervisor    = 'supervisor';
    case Senior        = 'senior';
    case Expert        = 'expert';
    case Employee      = 'employee';

    public function getLabel(): string
    {
        return match ($this) {
            self::ChiefManager => 'مدیر ارشد',
            self::Manager      => 'مدیر',
            self::Supervisor   => 'سرپرست',
            self::Senior       => 'کارشناس ارشد',
            self::Expert       => 'کارشناس',
            self::Employee     => 'کارمند',
        };
    }

    public function getColor(): string|array
    {
        return match ($this) {
            self::ChiefManager => 'danger',
            self::Manager      => 'warning',
            self::Supervisor   => 'info',
            self::Senior       => 'success',
            self::Expert       => 'primary',
            self::Employee     => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::ChiefManager => 'heroicon-o-crown',
            self::Manager      => 'heroicon-o-briefcase',
            self::Supervisor   => 'heroicon-o-eye',
            self::Senior       => 'heroicon-o-academic-cap',
            self::Expert       => 'heroicon-o-wrench-screwdriver',
            self::Employee     => 'heroicon-o-user',
        };
    }
}
