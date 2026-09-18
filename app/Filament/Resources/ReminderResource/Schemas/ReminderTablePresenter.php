<?php

namespace App\Filament\Resources\ReminderResource\Schemas;

use App\Enums\ReminderRecurrence;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Model;

class ReminderTablePresenter
{
    public static function completed(): IconColumn
    {
        return IconColumn::make('completed_at')
            ->label(__('resources/reminder/strings.fields.completed'))
            ->getStateUsing(fn(Model $record) => filled($record->completed_at))
            ->boolean()
            ->trueIcon('heroicon-o-check-circle')
            ->falseIcon('heroicon-o-minus')
            ->trueColor('success')
            ->falseColor('gray')
            ->sortable(query: fn($query, string $direction) => $query->orderBy('completed_at', $direction))
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function completedFilter(): TernaryFilter
    {
        return TernaryFilter::make('completed')
            ->label(__('resources/reminder/strings.filters.completed'))
            ->queries(
                true: fn($query) => $query->whereNotNull('completed_at'),
                false: fn($query) => $query->whereNull('completed_at'),
            );
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/reminder/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '-')
            ->sortable()
            ->color('gray')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function dueAt(): TextColumn
    {
        return TextColumn::make('due_at')
            ->label(__('resources/reminder/strings.fields.due_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '-')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label('ID')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function recurs(): TextColumn
    {
        return TextColumn::make('recurs')
            ->label(__('resources/reminder/strings.fields.recurs'))
            ->badge()
            ->formatStateUsing(fn($state) => $state instanceof ReminderRecurrence ? $state->label() : (ReminderRecurrence::tryFrom((string) $state)?->label() ?? $state))
            ->color(fn($state) => ($state instanceof ReminderRecurrence ? $state : ReminderRecurrence::tryFrom((string) $state)) === ReminderRecurrence::None ? 'gray' : 'info')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function recursFilter(): SelectFilter
    {
        return SelectFilter::make('recurs')
            ->label(__('resources/reminder/strings.filters.recurs'))
            ->options(collect(ReminderRecurrence::cases())->mapWithKeys(fn(ReminderRecurrence $case) => [$case->value => $case->label()]));
    }

    public static function title(): TextColumn
    {
        return TextColumn::make('title')
            ->label(__('resources/reminder/strings.fields.title'))
            ->limit(60)
            ->tooltip(fn($state) => strlen((string) $state) > 60 ? $state : null)
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function user(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label(__('resources/reminder/strings.fields.user'))
            ->formatStateUsing(fn(?Model $record): string => $record?->user?->name
                ?? __('resources/reminder/strings.deleted_user'))
            ->sortable()
            ->searchable()
            ->placeholder('-')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function userFilter(): SelectFilter
    {
        return SelectFilter::make('user_id')
            ->label(__('resources/reminder/strings.filters.user'))
            ->relationship('user', 'name')
            ->searchable()
            ->preload();
    }
}
