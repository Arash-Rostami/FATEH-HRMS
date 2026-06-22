<?php

namespace App\Filament\Resources\TaskResource\Schemas;

use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\TextSize;

class TaskInfolistPresenter
{
    public static function assignee(): TextEntry
    {
        return TextEntry::make('assignee.name')
            ->label(__('resources/task/strings.fields.assignee'))
            ->placeholder(__('resources/task/strings.fields.self_assigned'))
            ->icon('heroicon-o-user-plus');
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/task/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->color('gray')
            ->icon('heroicon-o-clock');
    }

    public static function creator(): TextEntry
    {
        return TextEntry::make('creator.name')
            ->label(__('resources/task/strings.fields.creator'))
            ->placeholder('—')
            ->icon('heroicon-o-user');
    }

    public static function deadline(): TextEntry
    {
        return TextEntry::make('deadline')
            ->label(__('resources/task/strings.fields.deadline'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : null)
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->placeholder('—')
            ->color(fn($record) => match (true) {
                !$record->deadline => 'gray',
                $record->status === TaskStatus::Done->value => 'success',
                $record->status === TaskStatus::Pending->value => 'danger',
                $record->deadline->isPast() => 'danger',
                $record->deadline->diffInDays(now()) <= 2 => 'warning',
                default => 'primary',
            })
            ->icon('heroicon-o-calendar');
    }

    public static function delegatedIcon(): IconEntry
    {
        return IconEntry::make('delegated')
            ->label(__('resources/task/strings.fields.delegated'))
            ->getStateUsing(fn($record) => filled($record->assigned_to) && $record->assigned_to !== $record->user_id)
            ->boolean()
            ->trueIcon('heroicon-o-arrow-right-on-rectangle')
            ->falseIcon('heroicon-o-minus')
            ->trueColor('info')
            ->falseColor('gray');
    }

    public static function deletedAt(): TextEntry
    {
        return TextEntry::make('deleted_at')
            ->label(__('resources/task/strings.fields.deleted_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : null)
            ->placeholder('—')
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->hidden(fn($record) => !$record->deleted_at);
    }

    public static function description(): TextEntry
    {
        return TextEntry::make('description')
            ->label(__('resources/task/strings.fields.description'))
            ->placeholder('—')
            ->columnSpanFull();
    }

    public static function status(): TextEntry
    {
        return TextEntry::make('status')
            ->label(__('resources/task/strings.fields.status'))
            ->getStateUsing(fn($record) => TaskStatus::tryFrom($record->status))
            ->badge();
    }

    public static function title(): TextEntry
    {
        return TextEntry::make('title')
            ->label(__('resources/task/strings.fields.title'))
            ->size(TextSize::Large)
            ->columnSpanFull();
    }

    public static function updatedAt(): TextEntry
    {
        return TextEntry::make('updated_at')
            ->label(__('resources/task/strings.fields.updated_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->color('gray')
            ->icon('heroicon-o-arrow-path');
    }
}
