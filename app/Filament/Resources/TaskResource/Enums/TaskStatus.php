<?php

namespace App\Filament\Resources\TaskResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TaskStatus: string implements HasColor, HasIcon, HasLabel
{
    case Todo = 'todo';
    case InProgress = 'in-progress';
    case Pending = 'pending';
    case Done = 'done';

    public function getColor(): string
    {
        return match ($this) {
            self::Todo => 'gray',
            self::InProgress => 'warning',
            self::Pending => 'danger',
            self::Done => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Todo => 'heroicon-o-clock',
            self::InProgress => 'heroicon-o-arrow-path',
            self::Pending => 'heroicon-o-pause-circle',
            self::Done => 'heroicon-o-check-circle',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Todo => 'انجام نشده',
            self::InProgress => 'در حال انجام',
            self::Pending => 'متوقف / در انتظار / بازنگری',
            self::Done => 'انجام شده',
        };
    }
}
