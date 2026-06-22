<?php

namespace App\Filament\Resources\ResourceResource\Schemas;

use App\Enums\ResourceType;
use App\Filament\Resources\ResourceResource\Enums\ResourceStatus;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ResourceTablePresenter
{
    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/resource/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->sortable()
            ->color('gray')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function floor(): TextColumn
    {
        return TextColumn::make('metadata.floor')
            ->label(__('resources/resource/strings.fields.floor'))
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function hasReservationsFilter(): TernaryFilter
    {
        return TernaryFilter::make('has_reservations')
            ->label(__('resources/resource/strings.filters.has_reservations'))
            ->queries(
                true: fn(Builder $q) => $q->has('reservations'),
                false: fn(Builder $q) => $q->doesntHave('reservations'),
            );
    }

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label('ID')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function name(): TextColumn
    {
        return TextColumn::make('name')
            ->label(__('resources/resource/strings.fields.name'))
            ->searchable()
            ->sortable()->searchable()->weight(FontWeight::Bold)
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function reservationsCount(): TextColumn
    {
        return TextColumn::make('reservations_count')
            ->label(__('resources/resource/strings.fields.reservations_count'))
            ->badge()
            ->color('info')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function status(): TextColumn
    {
        return TextColumn::make('status')
            ->label(__('resources/resource/strings.fields.status'))
            ->badge()
            ->formatStateUsing(fn(string $state) => ResourceStatus::tryFrom($state)?->getLabel() ?? $state)
            ->color(fn(string $state) => ResourceStatus::tryFrom($state)?->getColor() ?? 'gray')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function statusFilter(): SelectFilter
    {
        return SelectFilter::make('status')
            ->label(__('resources/resource/strings.filters.status'))
            ->options(ResourceStatus::class)
            ->validationMessages([
                'in' => __('resources/resource/strings.validation.status.in')
            ]);
    }

    public static function type(): TextColumn
    {
        return TextColumn::make('type')
            ->label(__('resources/resource/strings.fields.type'))
            ->badge()
            ->formatStateUsing(fn(string $state) => ResourceType::tryFrom($state)?->getLabel() ?? $state)
            ->color(fn(string $state) => ResourceType::tryFrom($state)?->getColor() ?? 'gray')
            ->icon(fn(string $state) => ResourceType::tryFrom($state)?->getIcon() ?? null)
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function typeFilter(): SelectFilter
    {
        return SelectFilter::make('type')
            ->label(__('resources/resource/strings.filters.type'))
            ->options(ResourceType::class)
            ->validationMessages([
                'in' => __('resources/resource/strings.validation.type.in')
            ]);
    }

    public static function typeGroup(): Group
    {
        return Group::make('type')
            ->label(__('resources/resource/strings.fields.type'))
            ->getTitleFromRecordUsing(fn(Model $record) => ResourceType::tryFrom($record->type)?->getLabel() ?? $record->type)
            ->collapsible();
    }
}
