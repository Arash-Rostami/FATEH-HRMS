<?php

namespace App\Filament\Resources\ProfileResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum Gender: string implements HasColor, HasIcon, HasLabel
{
    case Female = 'female';
    case Male   = 'male';

    public function getLabel(): string
    {
        return match ($this) {
            self::Female => 'زن',
            self::Male   => 'مرد',
        };
    }

    public function getColor(): string|array
    {
        return match ($this) {
            self::Female => 'pink',
            self::Male   => 'info',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Female => 'heroicon-o-user',
            self::Male   => 'heroicon-o-user',
        };
    }
}
