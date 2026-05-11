<?php

namespace App\Filament\Resources\LinkResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum LinkType: string implements HasColor, HasIcon, HasLabel
{
    case Internal = 'internal';
    case External = 'external';

    public function getColor(): string
    {
        return match ($this) {
            self::Internal => 'success',
            self::External => 'primary',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Internal => 'heroicon-o-building-office',
            self::External => 'heroicon-o-globe-alt',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Internal => 'داخلی',
            self::External => 'خارجی',
        };
    }
}
