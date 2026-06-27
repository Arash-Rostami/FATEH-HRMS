<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ResourceType: string implements HasColor, HasIcon, HasLabel
{
    case Seat = 'seat';
    case Spot = 'spot';
    case Car = 'car';
    case Meeting = 'meeting';

    public function getColor(): string
    {
        return match ($this) {
            self::Seat => 'primary',
            self::Spot => 'success',
            self::Car => 'warning',
            self::Meeting => 'info',
        };
    }

    public function getEmoji(): string
    {
        return match ($this) {
            self::Seat    => '🖥️',
            self::Spot    => '🅿️',
            self::Car     => '🚗',
            self::Meeting => '🤝🏽',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Seat => 'heroicon-o-computer-desktop',
            self::Spot => 'heroicon-o-map-pin',
            self::Car => 'heroicon-o-truck',
            self::Meeting => 'heroicon-o-users',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Seat => 'میز کار',
            self::Spot => 'پارکینگ',
            self::Car => 'خودرو',
            self::Meeting => 'ملاقات',
        };
    }

    public function getMaterialIcon(): string
    {
        return match ($this) {
            self::Seat => 'desk',
            self::Spot => 'local_parking',
            self::Car => 'directions_car',
            self::Meeting => 'person',
        };
    }

    public function isFullDay(): bool
    {
        return $this !== self::Meeting;
    }

    public static function tabs(): array
    {
        return array_map(
            fn(self $type) => [
                'id' => $type->value,
                'icon' => $type->getMaterialIcon(),
                'label' => $type->getLabel(),
            ],
            self::cases(),
        );
    }
}
