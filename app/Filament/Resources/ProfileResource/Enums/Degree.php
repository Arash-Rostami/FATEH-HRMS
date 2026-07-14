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
    case Doctorate     = 'doctorate';

    public function getLabel(): string
    {
        return match ($this) {
            self::Undergraduate => 'دیپلم یا کاردانی',
            self::Graduate      => 'کارشناسی',
            self::Postgraduate  => 'کارشناسی ارشد',
            self::Doctorate     => 'دکترا',
        };
    }

    public function getColor(): string|array
    {
        return match ($this) {
            self::Undergraduate => 'gray',
            self::Graduate      => 'info',
            self::Postgraduate  => 'warning',
            self::Doctorate     => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return 'heroicon-o-academic-cap';
    }
}