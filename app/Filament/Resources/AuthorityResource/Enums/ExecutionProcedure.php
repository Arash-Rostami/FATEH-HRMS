<?php

namespace App\Filament\Resources\AuthorityResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ExecutionProcedure: string implements HasColor, HasIcon, HasLabel
{
    case Yes     = 'yes';
    case No      = 'no';
    case Pending = 'pending';

    public function getLabel(): string
    {
        return match ($this) {
            self::Yes     => 'دارد',
            self::No      => 'ندارد',
            self::Pending => 'در انتظار (تصویب / بروزرسانی)',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Yes     => 'success',
            self::No      => 'danger',
            self::Pending => 'warning',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Yes     => 'heroicon-o-check-circle',
            self::No      => 'heroicon-o-x-circle',
            self::Pending => 'heroicon-o-clock',
        };
    }
}
