<?php

namespace App\Filament\Resources\KnowledgeProgressResource\Schemas;

use App\Models\User;
use App\Traits\Filament\FilamentFilters;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class KnowledgeProgressTablePresenter
{
    use FilamentFilters;

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label(__('resources/media/strings.progress.table.id'))
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function user(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label(__('resources/media/strings.progress.table.user'))
            ->sortable()
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function item(): TextColumn
    {
        return TextColumn::make('item')
            ->label(__('resources/media/strings.progress.table.item'))
            ->state(fn(Model $record): string => $record->media?->title ?? '-')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function series(): TextColumn
    {
        return TextColumn::make('media.parent.title')
            ->label(__('resources/media/strings.progress.table.series'))
            ->state(fn(Model $record): string => $record->media?->parent?->title ?? '-')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function position(): TextColumn
    {
        return TextColumn::make('position')
            ->label(__('resources/media/strings.progress.table.position'))
            ->formatStateUsing(fn($state): string => convertToPersian((string) $state) . ' ' . __('resources/media/strings.progress.table.seconds'))
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function statusLabel(Model $record): string
    {
        return match (true) {
            $record->completed_at === null => __('resources/media/strings.progress.export.completed_no'),
            default => __('resources/media/strings.progress.export.completed_yes'),
        };
    }

    public static function completed(): TextColumn
    {
        return TextColumn::make('completed_at')
            ->label(__('resources/media/strings.progress.table.completed'))
            ->state(fn(Model $record): string => self::statusLabel($record))
            ->badge()
            ->color(fn(Model $record): string => $record->completed_at ? 'success' : 'gray')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function lastSeenAt(): TextColumn
    {
        return TextColumn::make('last_seen_at')
            ->label(__('resources/media/strings.progress.table.last_seen_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/media/strings.progress.table.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function userGroup(): Group
    {
        return Group::make('user.name')
            ->label(__('resources/media/strings.progress.table.user'))
            ->collapsible();
    }

    public static function completedGroup(): Group
    {
        return Group::make('completed_at')
            ->label(__('resources/media/strings.progress.table.completed'))
            ->getTitleFromRecordUsing(fn(Model $record): string => self::statusLabel($record))
            ->collapsible();
    }

    public static function completedFilter(): TernaryFilter
    {
        return TernaryFilter::make('completed_at')
            ->label(__('resources/media/strings.progress.table.completed'))
            ->trueLabel(__('resources/media/strings.progress.export.completed_yes'))
            ->falseLabel(__('resources/media/strings.progress.export.completed_no'))
            ->nullable()
            ->native(false);
    }

    public static function userFilter(): SelectFilter
    {
        return SelectFilter::make('user_id')
            ->label(__('resources/media/strings.progress.table.user'))
            ->options(fn() => User::getCachedAllOptions()->toArray())
            ->searchable();
    }
}
