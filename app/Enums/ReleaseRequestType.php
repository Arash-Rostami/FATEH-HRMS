<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ReleaseRequestType: string implements HasColor, HasIcon, HasLabel
{
    case Support = 'support';
    case Recommendation = 'recommendation';
    case Bug = 'bug';

    public function getLabel(): string
    {
        return match ($this) {
            self::Support        => 'پشتیبانی نرم افزار',
            self::Recommendation => 'پیشنهاد ماژول',
            self::Bug            => 'گزارش باگ',
            default              => ucfirst(str_replace('_', ' ', $this->value)),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Support        => 'info',
            self::Recommendation => 'success',
            self::Bug             => 'danger',
            default              => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Support        => 'heroicon-o-lifebuoy',
            self::Recommendation => 'heroicon-o-light-bulb',
            self::Bug             => 'heroicon-o-shield-exclamation',
            default              => 'heroicon-o-ellipsis-horizontal',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn(self $case) => [$case->value => $case->getLabel()])->all();
    }

    public function getMaterialIcon(): string
    {
        return match ($this) {
            self::Support        => 'support_agent',
            self::Recommendation => 'lightbulb',
            self::Bug             => 'bug_report',
        };
    }

    public function getMaterialColor(): string
    {
        return match ($this) {
            self::Support        => 'var(--md-sys-color-primary)',
            self::Recommendation => 'var(--md-sys-color-secondary)',
            self::Bug             => 'var(--md-sys-color-error)',
        };
    }
}
