<?php

namespace App\Enums;

enum PresenceStatus: string
{
    case Onsite = 'onsite';
    case Remote = 'remote';
    case Busy = 'busy';
    case Mission = 'mission';

    public function activeClass(): string
    {
        $c = $this->color();
        return "bg-{$c}-500 text-white shadow-{$c}-500/30";
    }

    public function cardClasses(): string
    {
        $c = $this->color();
        return "text-{$c}-400 bg-{$c}-500/10 border-{$c}-500/20 hover:border-{$c}-500/40";
    }

    public function color(): string
    {
        return match ($this) {
            self::Onsite => 'emerald',
            self::Remote => 'sky',
            self::Busy => 'rose',
            self::Mission => 'amber',
        };
    }

    public static function default(): string
    {
        return 'text-gray-400 bg-gray-500/10 border-gray-500/20 hover:border-gray-500/40';
    }

    public static function defaultCardClasses(): string
    {
        return 'text-gray-400 bg-gray-500/10 border-gray-500/20 hover:border-gray-500/40';
    }

    public function icon(): string
    {
        return match ($this) {
            self::Onsite => 'apartment',
            self::Remote => 'laptop_chromebook',
            self::Busy => 'do_not_disturb_on',
            self::Mission => 'flight_takeoff',
        };
    }

    public function iconBgClass(): string { return "bg-{$this->color()}-500"; }

    public function inactiveClass(): string
    {
        $c = $this->color();
        return "text-{$c}-400 hover:bg-{$c}-500/10";
    }

    public function label(): string
    {
        return match ($this) {
            self::Onsite => 'حاضر',
            self::Remote => 'دورکار',
            self::Busy => 'مشغول',
            self::Mission => 'مأموریت',
        };
    }

    public function ringClass(): string { return "ring-{$this->color()}-500"; }

    public function sublabel(): string
    {
        return match ($this) {
            self::Onsite => 'حضور فیزیکی',
            self::Remote => 'کار از منزل',
            self::Busy => 'عدم مزاحمت',
            self::Mission => 'خارج از شرکت',
        };
    }
}
