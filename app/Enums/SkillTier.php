<?php

namespace App\Enums;

enum SkillTier: string
{
    case Endorsed = 'endorsed';
    case Active = 'active';
    case Unused = 'unused';

    public function label(): string
    {
        return match ($this) {
            self::Endorsed => 'تأییدشده',
            self::Active => 'فعال',
            self::Unused => 'آماده مشارکت',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Endorsed => 'verified',
            self::Active => 'bolt',
            self::Unused => 'hourglass_empty',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Endorsed => 'bg-amber-500/10 text-amber-600',
            self::Active => 'bg-emerald-500/10 text-emerald-600',
            self::Unused => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)]',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Endorsed => 'amber',
            self::Active => 'emerald',
            self::Unused => 'gray',
        };
    }

    public function heroicon(): string
    {
        return match ($this) {
            self::Endorsed => 'heroicon-m-check-badge',
            self::Active => 'heroicon-m-bolt',
            self::Unused => 'heroicon-m-clock',
        };
    }
}
