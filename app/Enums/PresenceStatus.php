<?php

namespace App\Enums;

enum PresenceStatus: string
{
    case Onsite = 'onsite';
    case Remote = 'remote';
    case Mission = 'mission';
    case Busy = 'busy';
    case Grumpy = 'grumpy';
    case Angry = 'angry';

    public function activeClass(): string
    {
        return match ($this) {
            self::Onsite => 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/20 ring-1 ring-emerald-400/50',
            self::Remote => 'bg-blue-500 text-white shadow-lg shadow-blue-500/20 ring-1 ring-blue-400/50',
            self::Mission => 'bg-amber-500 text-white shadow-lg shadow-amber-500/20 ring-1 ring-amber-400/50',
            self::Busy => 'bg-rose-500 text-white shadow-lg shadow-rose-500/20 ring-1 ring-rose-400/50',
            self::Grumpy => 'bg-violet-600 text-white shadow-xl shadow-violet-600/40 ring-1 ring-violet-400/50',
            self::Angry => 'bg-red-600 text-white shadow-xl shadow-red-600/50 ring-1 ring-red-500/60 animate-pulse',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Angry => 'animate-bounce',
            default => '',
        };
    }

    public function cardClasses(): string
    {
        return match ($this) {
            self::Onsite => 'hover:-translate-y-0.5 text-emerald-400 bg-emerald-500/5 border-2 border-emerald-500/20 hover:border-emerald-500/50 hover:bg-emerald-500/10 hover:shadow-xl hover:shadow-emerald-500/10',
            self::Remote => 'hover:-translate-y-0.5 text-blue-400 bg-blue-500/5 border-2 border-blue-500/20 hover:border-blue-500/50 hover:bg-blue-500/10 hover:shadow-xl hover:shadow-blue-500/10',
            self::Mission => 'hover:-translate-y-0.5 text-amber-400 bg-amber-500/5 border-2 border-amber-500/20 hover:border-amber-500/50 hover:bg-amber-500/10 hover:shadow-xl hover:shadow-amber-500/10',
            self::Busy => 'hover:-translate-y-0.5 text-rose-400 bg-rose-500/5 border-2 border-rose-500/20 hover:border-rose-500/50 hover:bg-rose-500/10 hover:shadow-xl hover:shadow-rose-500/10',
            self::Grumpy => 'hover:-translate-y-0.5 text-violet-400 bg-violet-500/5 border-2 border-violet-500/40 hover:border-violet-500/60 hover:bg-violet-500/10 hover:shadow-xl hover:shadow-violet-500/20 opacity-75 saturate-50',
            self::Angry => 'hover:-translate-y-1 animate-pulse text-red-500 bg-red-600/10 border-2 border-red-600/60 hover:border-red-500/80 hover:bg-red-600/20 hover:shadow-2xl hover:shadow-red-600/30 ring-1 ring-red-500/30',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Onsite => 'emerald',
            self::Remote => 'blue',
            self::Mission => 'amber',
            self::Busy => 'rose',
            self::Grumpy => 'violet',
            self::Angry => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Onsite => 'apartment',
            self::Remote => 'laptop_chromebook',
            self::Mission => 'flight_takeoff',
            self::Busy => 'do_not_disturb_on',
            self::Grumpy => 'sentiment_dissatisfied',
            self::Angry => 'local_fire_department',
        };
    }

    public function iconBgClass(): string
    {
        return match ($this) {
            self::Onsite => 'bg-emerald-500/20 text-emerald-400',
            self::Remote => 'bg-blue-500/20 text-blue-400',
            self::Mission => 'bg-amber-500/20 text-amber-400',
            self::Busy => 'bg-rose-500/20 text-rose-400',
            self::Grumpy => 'bg-violet-500/20 text-violet-400',
            self::Angry => 'bg-red-600/30 text-red-500',
        };
    }

    public function imageClasses(): string
    {
        return match ($this) {
            self::Busy, self::Grumpy => 'animate-pulse',
            self::Angry => 'animate-pulse drop-shadow-[0_0_10px_rgba(220,38,38,0.8)]',
            default => '',
        };
    }

    public function inactiveClass(): string
    {
        return match ($this) {
            self::Onsite => 'text-emerald-400 hover:bg-emerald-500/10 hover:text-emerald-500 transition-colors',
            self::Remote => 'text-blue-400 hover:bg-blue-500/10 hover:text-blue-500 transition-colors',
            self::Mission => 'text-amber-400 hover:bg-amber-500/10 hover:text-amber-500 transition-colors',
            self::Busy => 'text-rose-400 hover:bg-rose-500/10 hover:text-rose-500 transition-colors',
            self::Grumpy => 'text-violet-400 hover:bg-violet-500/10 hover:text-violet-500 transition-colors',
            self::Angry => 'text-red-500 hover:bg-red-600/20 hover:text-red-400 transition-colors',
        };
    }

    public function isObscured(): bool
    {
        return match ($this) {
            self::Grumpy, self::Angry => true,
            default => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Onsite => 'در دفتر',
            self::Remote => 'دورکار',
            self::Mission => 'مأموریت',
            self::Busy => 'مشغول',
            self::Grumpy => __('dashboard.presence.grumpy.label'),
            self::Angry => __('dashboard.presence.angry.label'),
        };
    }

    public function ringClass(): string
    {
        return match ($this) {
            self::Onsite => 'border-emerald-500',
            self::Remote => 'border-blue-500',
            self::Mission => 'border-amber-500',
            self::Busy => 'border-rose-500',
            self::Grumpy => 'border-violet-500',
            self::Angry => 'border-red-600',
        };
    }

    public function sublabel(): string
    {
        return match ($this) {
            self::Onsite => 'حضور فیزیکی',
            self::Remote => 'کار از منزل',
            self::Mission => 'خارج از شرکت',
            self::Busy => 'عدم مزاحمت',
            self::Grumpy => __('dashboard.presence.grumpy.sublabel'),
            self::Angry => __('dashboard.presence.angry.sublabel'),
        };
    }
}
