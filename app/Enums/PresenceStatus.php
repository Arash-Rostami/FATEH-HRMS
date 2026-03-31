<?php

namespace App\Enums;

enum PresenceStatus: string
{
    case Onsite = 'onsite';
    case Remote = 'remote';
    case Mission = 'mission';
    case Busy = 'busy';

    public function activeClass(): string
    {
        return match ($this) {
            self::Onsite => 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/20 ring-1 ring-emerald-400/50',
            self::Remote => 'bg-blue-500 text-white shadow-lg shadow-blue-500/20 ring-1 ring-blue-400/50',
            self::Mission => 'bg-amber-500 text-white shadow-lg shadow-amber-500/20 ring-1 ring-amber-400/50',
            self::Busy => 'bg-rose-500 text-white shadow-lg shadow-rose-500/20 ring-1 ring-rose-400/50',
        };
    }

    public function cardClasses(): string
    {
        return match ($this) {
            self::Onsite => 'text-emerald-400 bg-emerald-500/5 border-2 border-emerald-500/20 hover:border-emerald-500/50 hover:bg-emerald-500/10 hover:shadow-xl hover:shadow-emerald-500/10',
            self::Remote => 'text-blue-400 bg-blue-500/5 border-2 border-blue-500/20 hover:border-blue-500/50 hover:bg-blue-500/10 hover:shadow-xl hover:shadow-blue-500/10',
            self::Mission => 'text-amber-400 bg-amber-500/5 border-2 border-amber-500/20 hover:border-amber-500/50 hover:bg-amber-500/10 hover:shadow-xl hover:shadow-amber-500/10',
            self::Busy => 'text-rose-400 bg-rose-500/5 border-2 border-rose-500/20 hover:border-rose-500/50 hover:bg-rose-500/10 hover:shadow-xl hover:shadow-rose-500/10',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Onsite => 'emerald',
            self::Remote => 'blue',
            self::Mission => 'amber',
            self::Busy => 'rose',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Onsite => 'apartment',
            self::Remote => 'laptop_chromebook',
            self::Mission => 'flight_takeoff',
            self::Busy => 'do_not_disturb_on',
        };
    }

    public function iconBgClass(): string
    {
        return match ($this) {
            self::Onsite => 'bg-emerald-500/20 text-emerald-400',
            self::Remote => 'bg-blue-500/20 text-blue-400',
            self::Mission => 'bg-amber-500/20 text-amber-400',
            self::Busy => 'bg-rose-500/20 text-rose-400',
        };
    }

    public function inactiveClass(): string
    {
        return match ($this) {
            self::Onsite => 'text-emerald-400 hover:bg-emerald-500/10 hover:text-emerald-500 transition-colors',
            self::Remote => 'text-blue-400 hover:bg-blue-500/10 hover:text-blue-500 transition-colors',
            self::Mission => 'text-amber-400 hover:bg-amber-500/10 hover:text-amber-500 transition-colors',
            self::Busy => 'text-rose-400 hover:bg-rose-500/10 hover:text-rose-500 transition-colors',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Onsite => 'در دفتر',
            self::Remote => 'دورکار',
            self::Mission => 'مأموریت',
            self::Busy => 'مشغول',
        };
    }

    public function ringClass(): string
    {
        return match ($this) {
            self::Onsite => 'border-emerald-500',
            self::Remote => 'border-blue-500',
            self::Mission => 'border-amber-500',
            self::Busy => 'border-rose-500',
        };
    }

    public function sublabel(): string
    {
        return match ($this) {
            self::Onsite => 'حضور فیزیکی',
            self::Remote => 'کار از منزل',
            self::Mission => 'خارج از شرکت',
            self::Busy => 'عدم مزاحمت',
        };
    }
}
