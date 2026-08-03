<?php

namespace App\Enums;

enum SkillRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در حال بررسی',
            self::Approved => 'تایید شده',
            self::Rejected => 'رد شده',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Approved => 'emerald',
            self::Rejected => 'rose',
        };
    }

    public function heroicon(): string
    {
        return match ($this) {
            self::Pending => 'heroicon-m-clock',
            self::Approved => 'heroicon-m-check-circle',
            self::Rejected => 'heroicon-m-x-circle',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'animate-pulse',
            self::Approved => '',
            self::Rejected => '',
        };
    }
}