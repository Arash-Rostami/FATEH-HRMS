<?php

namespace App\Filament\Resources\EventResource\Schemas;

use App\Traits\FilamentFilters;
use Filament\Tables\Columns\{IconColumn, TextColumn};
use Filament\Tables\Filters\{Filter, SelectFilter, TernaryFilter};
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;

class EventTablePresenter
{
    use FilamentFilters;

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/event/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function countdown(): IconColumn
    {
        return IconColumn::make('countdown')
            ->label(__('resources/event/strings.table.countdown'))
            ->boolean()
            ->getStateUsing(fn ($record) => ($record->countdown['enabled'] ?? false) === true)
            ->trueIcon('heroicon-o-clock')
            ->trueColor('primary')
            ->falseColor('gray')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function date(): TextColumn
    {
        return TextColumn::make('date')
            ->label(__('resources/event/strings.fields.date'))
            ->formatStateUsing(fn ($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignCenter()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function dateRangeFilter(): Filter
    {
        return self::jalaliDateRangeFilter(
            'date_range',
            'date',
            __('resources/event/strings.filters.date_range'),
            __('resources/event/strings.filters.date_from'),
            __('resources/event/strings.filters.date_until'),
        );
    }

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label('ID')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function private(): IconColumn
    {
        return IconColumn::make('private')
            ->label(__('resources/event/strings.fields.private'))
            ->boolean()
            ->trueIcon('heroicon-o-lock-closed')
            ->falseIcon('heroicon-o-globe-alt')
            ->trueColor('warning')
            ->falseColor('success')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function title(): TextColumn
    {
        return TextColumn::make('title')
            ->label(__('resources/event/strings.fields.title'))
            ->searchable()
            ->sortable()
            ->limit(50)
            ->tooltip(fn($state) => strlen($state) > 50 ? $state : null)
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function upcomingFilter(): Filter
    {
        return Filter::make('upcoming')
            ->label(__('resources/event/strings.filters.upcoming'))
            ->query(fn(Builder $query) => $query->where('date', '>=', now()->toDateTimeString()))
            ->toggle();
    }

    public static function user(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label(__('resources/event/strings.fields.user'))
            ->searchable()
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function userFilter(): SelectFilter
    {
        return SelectFilter::make('user_id')
            ->label(__('resources/event/strings.fields.user'))
            ->relationship('user', 'name')
            ->searchable()
            ->preload();
    }

    public static function visibilityFilter(): TernaryFilter
    {
        return TernaryFilter::make('private')
            ->label(__('resources/event/strings.fields.private'))
            ->trueLabel(__('resources/event/strings.filters.private'))
            ->falseLabel(__('resources/event/strings.filters.public'));
    }

    public static function visibilityGroup(): Group
    {
        return Group::make('private')
            ->label(__('resources/event/strings.fields.private'))
            ->getTitleFromRecordUsing(fn($record) => $record?->private ? __('resources/event/strings.filters.private') : __('resources/event/strings.filters.public'))
            ->collapsible();
    }
}
