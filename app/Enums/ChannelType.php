<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ChannelType: string implements HasColor, HasIcon, HasLabel
{
    case Open = 'open';
    case Private = 'private';

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'success',
            self::Private => 'warning',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Open => 'heroicon-o-lock-open',
            self::Private => 'heroicon-o-lock-closed',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'عمومی',
            self::Private => 'خصوصی',
        };
    }

    public function getMaterialIcon(): string
    {
        return match ($this) {
            self::Open => 'campaign',
            self::Private => 'lock',
        };
    }



    public function getMaterialColor(): string
    {
        return match ($this) {
            self::Open => 'var(--md-sys-color-primary)',
            self::Private => 'var(--md-sys-color-tertiary)',
        };
    }
}
