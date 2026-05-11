<?php

namespace App\Filament\Resources\FeedResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum FeedCategory: string implements HasColor, HasIcon, HasLabel
{
    case General     = 'General';
    case Event       = 'Event';
    case Birthday    = 'Birthday';
    case Anniversary = 'Work Anniversary';
    case Poll        = 'Poll';

    public function getLabel(): string
    {
        return match ($this) {
            self::General     => 'عمومی',
            self::Event       => 'رویداد',
            self::Birthday    => 'تولد',
            self::Anniversary => 'سالگرد کاری',
            self::Poll        => 'نظرسنجی',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::General     => 'gray',
            self::Event       => 'primary',
            self::Birthday    => 'warning',
            self::Anniversary => 'info',
            self::Poll        => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::General     => 'heroicon-o-chat-bubble-left-right',
            self::Event       => 'heroicon-o-calendar-days',
            self::Birthday    => 'heroicon-o-cake',
            self::Anniversary => 'heroicon-o-star',
            self::Poll        => 'heroicon-o-chart-bar',
        };
    }
}
