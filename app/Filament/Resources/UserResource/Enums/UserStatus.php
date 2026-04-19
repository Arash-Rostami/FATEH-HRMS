<?php

namespace App\Filament\Resources\UserResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum UserStatus: string implements HasColor, HasIcon, HasLabel
{
    case Active    = 'active';
    case Inactive  = 'inactive';
    case Suspended = 'suspended';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active    => 'فعال',
            self::Inactive  => 'غیرفعال',
            self::Suspended => 'تعلیق‌شده',
        };
    }

    public function getColor(): string|array
    {
        return match ($this) {
            self::Active    => 'success',
            self::Inactive  => 'gray',
            self::Suspended => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Active    => 'heroicon-o-check-circle',
            self::Inactive  => 'heroicon-o-x-circle',
            self::Suspended => 'heroicon-o-no-symbol',
        };
    }
}
