<?php

namespace App\Filament\Resources\ProfileResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum MaritalStatus: string implements HasColor, HasIcon, HasLabel
{
    case Single  = 'single';
    case Married = 'married';

    public function getLabel(): string
    {
        return match ($this) {
            self::Single  => 'مجرد',
            self::Married => 'متأهل',
        };
    }

    public function getColor(): string|array
    {
        return match ($this) {
            self::Single  => 'gray',
            self::Married => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Single  => 'heroicon-o-user',
            self::Married => 'heroicon-o-heart',
        };
    }
}
