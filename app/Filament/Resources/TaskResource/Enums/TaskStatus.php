<?php

namespace App\Filament\Resources\TaskResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TaskStatus: string implements HasColor, HasIcon, HasLabel
{
    case Todo = 'todo';
    case InProgress = 'in-progress';
    case Done = 'done';

    public function getColor(): string
    {
        return match ($this) {
            self::Todo => 'gray',
            self::InProgress => 'warning',
            self::Done => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Todo => 'heroicon-o-clock',
            self::InProgress => 'heroicon-o-arrow-path',
            self::Done => 'heroicon-o-check-circle',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Todo => 'انجام نشده',
            self::InProgress => 'در حال انجام',
            self::Done => 'انجام شده',
        };
    }
}
