<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ChannelRole: string implements HasColor, HasLabel
{
    case Member = 'member';
    case Admin = 'admin';

    public function getColor(): string
    {
        return match ($this) {
            self::Member => 'gray',
            self::Admin => 'info',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Member => 'عضو',
            self::Admin => 'مدیر',
        };
    }
}
