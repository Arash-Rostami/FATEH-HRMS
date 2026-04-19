<?php

namespace App\Filament\Resources\UserResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum UserType: string implements HasColor, HasIcon, HasLabel
{
    case Employee   = 'employee';
    case Contractor = 'contractor';
    case Intern     = 'intern';
    case Guest      = 'guest';
    case VIP        = 'vip';

    public function getLabel(): string
    {
        return match ($this) {
            self::Employee   => 'کارمند',
            self::Contractor => 'پیمانکار',
            self::Intern     => 'کارآموز',
            self::Guest      => 'مهمان',
            self::VIP        => 'ویژه',
        };
    }

    public function getColor(): string|array
    {
        return match ($this) {
            self::Employee   => 'primary',
            self::Contractor => 'warning',
            self::Intern     => 'info',
            self::Guest      => 'gray',
            self::VIP        => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Employee   => 'heroicon-o-briefcase',
            self::Contractor => 'heroicon-o-wrench-screwdriver',
            self::Intern     => 'heroicon-o-academic-cap',
            self::Guest      => 'heroicon-o-user',
            self::VIP        => 'heroicon-o-star',
        };
    }
}
