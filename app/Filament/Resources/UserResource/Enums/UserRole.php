<?php

namespace App\Filament\Resources\UserResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasIcon, HasLabel
{
    case User      = 'user';
    case Admin     = 'admin';
    case Developer = 'developer';

    public function getLabel(): string
    {
        return match ($this) {
            self::User      => 'کاربر',
            self::Admin     => 'مدیر',
            self::Developer => 'توسعه‌دهنده',
        };
    }

    public function getColor(): string|array
    {
        return match ($this) {
            self::User      => 'gray',
            self::Admin     => 'danger',
            self::Developer => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::User      => 'heroicon-o-user',
            self::Admin     => 'heroicon-o-shield-check',
            self::Developer => 'heroicon-o-code-bracket',
        };
    }
}
