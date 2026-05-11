<?php

namespace App\Filament\Resources\ThsResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum RequestType: string implements HasColor, HasIcon, HasLabel
{
    case Support = 'support';
    case Access = 'access';
    case Development = 'development';

    public function getColor(): string
    {
        return match ($this) {
            self::Support => 'primary',
            self::Access => 'warning',
            self::Development => 'info',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Support => 'heroicon-o-wrench-screwdriver',
            self::Access => 'heroicon-o-key',
            self::Development => 'heroicon-o-code-bracket',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Support => 'پشتیبانی',
            self::Access => 'دسترسی',
            self::Development => 'توسعه',
        };
    }
}
