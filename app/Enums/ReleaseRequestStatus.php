<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ReleaseRequestStatus: string implements HasColor, HasIcon, HasLabel
{
    case Open = 'open';
    case InReview = 'in_review';
    case Resolved = 'resolved';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open     => 'باز',
            self::InReview => 'در حال بررسی',
            self::Resolved => 'حل‌شده',
            default        => ucfirst(str_replace('_', ' ', $this->value)),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open     => 'info',
            self::InReview => 'warning',
            self::Resolved => 'success',
            default        => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Open     => 'heroicon-o-chat-bubble-left-right',
            self::InReview => 'heroicon-o-clock',
            self::Resolved => 'heroicon-o-check-circle',
            default        => 'heroicon-o-ellipsis-horizontal',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn(self $case) => [$case->value => $case->getLabel()])->all();
    }

    public function getMaterialIcon(): string
    {
        return match ($this) {
            self::Open     => 'forum',
            self::InReview => 'schedule',
            self::Resolved => 'check_circle',
        };
    }

    public function getMaterialColor(): string
    {
        return match ($this) {
            self::Open     => 'var(--md-sys-color-primary)',
            self::InReview => 'var(--md-sys-color-tertiary)',
            self::Resolved => 'var(--md-sys-color-secondary)',
        };
    }
}