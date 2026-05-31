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
    case Delegated = 'delegated';

    public function getColor(): string
    {
        return match ($this) {
            self::Todo => 'gray',
            self::InProgress => 'warning',
            self::Done => 'success',
            self::Delegated => 'info',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Todo => 'heroicon-o-clock',
            self::InProgress => 'heroicon-o-arrow-path',
            self::Done => 'heroicon-o-check-circle',
            self::Delegated => 'heroicon-o-arrow-right-on-rectangle',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Todo => __('resources/task/strings.status.todo') ?? 'انجام نشده',
            self::InProgress => __('resources/task/strings.status.in_progress') ?? 'در حال انجام',
            self::Done => __('resources/task/strings.status.done') ?? 'انجام شده',
            self::Delegated => __('resources/task/strings.status.delegated') ?? 'تفویض‌شده',
        };
    }
}
