<?php

namespace App\Filament\Resources\ProfileResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum EmploymentType: string implements HasColor, HasIcon, HasLabel
{
    case Fulltime  = 'fulltime';
    case Parttime  = 'parttime';
    case Contract  = 'contract';

    public function getLabel(): string
    {
        return match ($this) {
            self::Fulltime => 'تمام‌وقت',
            self::Parttime => 'پاره‌وقت',
            self::Contract => 'قراردادی',
        };
    }

    public function getColor(): string|array
    {
        return match ($this) {
            self::Fulltime => 'success',
            self::Parttime => 'warning',
            self::Contract => 'info',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Fulltime => 'heroicon-o-briefcase',
            self::Parttime => 'heroicon-o-clock',
            self::Contract => 'heroicon-o-document-text',
        };
    }
}
