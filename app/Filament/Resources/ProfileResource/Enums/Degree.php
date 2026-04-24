<?php

namespace App\Filament\Resources\ProfileResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum Degree: string implements HasColor, HasIcon, HasLabel
{
    case Undergraduate = 'undergraduate';
    case Graduate      = 'graduate';
    case Postgraduate  = 'postgraduate';

    public function getLabel(): string
    {
        return match ($this) {
            self::Undergraduate => 'کاردانی / کارشناسی',
            self::Graduate      => 'کارشناسی ارشد',
            self::Postgraduate  => 'دکترا',
        };
    }

    public function getColor(): string|array
    {
        return match ($this) {
            self::Undergraduate => 'gray',
            self::Graduate      => 'info',
            self::Postgraduate  => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Undergraduate => 'heroicon-o-academic-cap',
            self::Graduate      => 'heroicon-o-academic-cap',
            self::Postgraduate  => 'heroicon-o-academic-cap',
        };
    }
}
