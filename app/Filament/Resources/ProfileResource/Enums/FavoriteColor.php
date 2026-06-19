<?php

namespace App\Filament\Resources\ProfileResource\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;

enum FavoriteColor: string implements HasLabel, HasColor
{
    case DarkRed = '#b82224';
    case Maroon = '#881951';
    case LightPink = '#f9bbd2';
    case LightPurple = '#d2c4de';
    case DarkPurple = '#4c2d8a';
    case NavyBlue = '#002060';
    case MediumBlue = '#11499c';
    case Blue = '#0070c0';
    case LightBlue = '#00b0f0';
    case VeryLightBlue = '#d1e7ef';
    case TealBlue = '#156082';
    case Teal = '#57bdb8';
    case DarkGreen = '#0c4e42';
    case Green = '#00b050';
    case Yellow = '#ffff00';
    case Gold = '#ffc000';
    case Orange = '#f26f1f';
    case Red = '#ff0000';
    case Brown = '#513531';
    case TailwindRed = '#ef4444';
    case TailwindBlue = '#3b82f6';
    case TailwindEmerald = '#10b981';
    case TailwindAmber = '#f59e0b';
    case Black = '#000000';
    case White = '#ffffff';
    case TailwindViolet = '#8b5cf6';
    case TailwindPink = '#ec4899';
    case TailwindSlate = '#64748b';
    case TailwindTeal = '#14b8a6';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DarkRed => 'DarkRed',
            self::Maroon => 'Maroon',
            self::LightPink => 'LightPink',
            self::LightPurple => 'LightPurple',
            self::DarkPurple => 'DarkPurple',
            self::NavyBlue => 'NavyBlue',
            self::MediumBlue => 'MediumBlue',
            self::Blue => 'Blue',
            self::LightBlue => 'LightBlue',
            self::VeryLightBlue => 'VeryLightBlue',
            self::TealBlue => 'TealBlue',
            self::Teal => 'Teal',
            self::DarkGreen => 'DarkGreen',
            self::Green => 'Green',
            self::Yellow => 'Yellow',
            self::Gold => 'Gold',
            self::Orange => 'Orange',
            self::Red => 'Red',
            self::Brown => 'Brown',
            self::TailwindRed => 'TailwindRed',
            self::TailwindBlue => 'TailwindBlue',
            self::TailwindEmerald => 'TailwindEmerald',
            self::TailwindAmber => 'TailwindAmber',
            self::Black => 'Black',
            self::White => 'White',
            self::TailwindViolet => 'TailwindViolet',
            self::TailwindPink => 'TailwindPink',
            self::TailwindSlate => 'TailwindSlate',
            self::TailwindTeal => 'TailwindTeal',
        };
    }

    public function getColor(): string|array|null
    {
        return $this->value;
    }
}
