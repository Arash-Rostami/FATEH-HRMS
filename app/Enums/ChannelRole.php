<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ChannelRole: string implements HasColor, HasLabel
{
    case Member = 'member';
    case Admin = 'admin';
    case Viewer = 'viewer';

    public function getColor(): string
    {
        return match ($this) {
            self::Member => 'gray',
            self::Admin => 'info',
            self::Viewer => 'warning',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Member => 'عضو',
            self::Admin => 'مدیر',
            self::Viewer => 'همراه',
        };
    }
}
