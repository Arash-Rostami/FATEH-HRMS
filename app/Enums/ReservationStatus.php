<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ReservationStatus: string implements HasColor, HasIcon, HasLabel
{
    case Active = 'active';
    case Released = 'released';
    case CancelledUser = 'cancelled_user';
    case CancelledAdmin = 'cancelled_admin';

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Released => 'info',
            self::CancelledUser => 'warning',
            self::CancelledAdmin => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Active => 'heroicon-o-check-circle',
            self::Released => 'heroicon-o-arrow-path',
            self::CancelledUser => 'heroicon-o-x-circle',
            self::CancelledAdmin => 'heroicon-o-shield-exclamation',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'فعال',
            self::Released => 'آزادشده',
            self::CancelledUser => 'لغو توسط کاربر',
            self::CancelledAdmin => 'لغو توسط ادمین',
        };
    }

    public function isCancelled(): bool
    {
        return in_array($this, [self::CancelledUser, self::CancelledAdmin]);
    }
}
