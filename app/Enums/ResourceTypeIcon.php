<?php

namespace App\Enums;

enum ResourceTypeIcon: string
{
    case Desk = 'heroicon-o-computer-desktop';
    case Vehicle = 'heroicon-o-truck';
    case Parking = 'heroicon-o-map-pin';
    case Meeting = 'heroicon-o-users';
    case Projector = 'heroicon-o-video-camera';
    case Printer = 'heroicon-o-printer';
    case Tools = 'heroicon-o-wrench-screwdriver';
    case Key = 'heroicon-o-key';
    case Box = 'heroicon-o-cube';
    case Building = 'heroicon-o-building-office';
    case Device = 'heroicon-o-device-phone-mobile';
    case Storage = 'heroicon-o-archive-box';
    case Briefcase = 'heroicon-o-briefcase';
    case Ticket = 'heroicon-o-ticket';

    public function getLabel(): string
    {
        return match ($this) {
            self::Desk => 'میز',
            self::Vehicle => 'وسیله نقلیه',
            self::Parking => 'پارکینگ',
            self::Meeting => 'جلسه',
            self::Projector => 'پروژکتور',
            self::Printer => 'چاپگر',
            self::Tools => 'ابزار',
            self::Key => 'کلید',
            self::Box => 'جعبه',
            self::Building => 'ساختمان',
            self::Device => 'دستگاه',
            self::Storage => 'انبار',
            self::Briefcase => 'کیف',
            self::Ticket => 'بلیت',
        };
    }

    public function getMaterialIcon(): string
    {
        return match ($this) {
            self::Desk => 'desk',
            self::Vehicle => 'directions_car',
            self::Parking => 'local_parking',
            self::Meeting => 'group',
            self::Projector => 'videocam',
            self::Printer => 'print',
            self::Tools => 'build',
            self::Key => 'key',
            self::Box => 'inventory_2',
            self::Building => 'apartment',
            self::Device => 'smartphone',
            self::Storage => 'archive',
            self::Briefcase => 'work',
            self::Ticket => 'confirmation_number',
        };
    }

    public static function default(): self
    {
        return self::Box;
    }
}
