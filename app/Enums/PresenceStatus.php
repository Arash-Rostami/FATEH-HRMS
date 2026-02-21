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
        $c = $this->color();
        return "bg-{$c}-500 text-white shadow-lg shadow-{$c}-500/20 ring-1 ring-{$c}-400/50";
    }

    public function cardClasses(): string
    {
        $c = $this->color();
        return "text-{$c}-400 bg-{$c}-500/5 border-2 border-{$c}-500/20 hover:border-{$c}-500/50 hover:bg-{$c}-500/10 hover:shadow-xl hover:shadow-{$c}-500/10";
    }

    public function color(): string
    {
        return match ($this) {
            self::Onsite => 'emerald',
            self::Remote => 'sky',
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
        return "bg-{$this->color()}-500/20 text-{$this->color()}-400";
    }

    public function inactiveClass(): string
    {
        $c = $this->color();
        return "text-{$c}-400 hover:bg-{$c}-500/10 hover:text-{$c}-500 transition-colors";
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
            self::Remote => 'border-sky-500',
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
