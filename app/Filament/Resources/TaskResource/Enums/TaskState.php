<?php

namespace App\Filament\Resources\TaskResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TaskState: string implements HasColor, HasIcon, HasLabel
{
    case Extension = 'extension';
    case Suspension = 'suspension';
    case Completion = 'completion';

    public function getColor(): string
    {
        return match ($this) {
            self::Extension => 'warning',
            self::Suspension => 'danger',
            self::Completion => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Extension => 'heroicon-o-calendar-days',
            self::Suspension => 'heroicon-o-pause-circle',
            self::Completion => 'heroicon-o-check-badge',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Extension => 'تمدید',
            self::Suspension => 'توقف',
            self::Completion => 'تکمیل',
        };
    }
}
