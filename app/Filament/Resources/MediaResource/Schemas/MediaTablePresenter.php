<?php

namespace App\Filament\Resources\MediaResource\Schemas;

use App\Enums\MediaIcon;
use App\Filament\Resources\ReportResource\Schemas\ReportTablePresenter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MediaTablePresenter extends ReportTablePresenter
{
    public static function reportDate(): TextColumn
    {
        return parent::reportDate()->label(__('resources/media/strings.fields.report_date'));
    }

    public static function reportDateRangeFilter(): Filter
    {
        return parent::reportDateRangeFilter()->label(__('resources/media/strings.filters.report_date_range'));
    }

    public static function kind(): TextColumn
    {
        return TextColumn::make('kind')
            ->label(__('resources/media/strings.table.kind'))
            ->badge()
            ->formatStateUsing(fn(string $state): string => static::kindLabel($state))
            ->color(fn(string $state): string => match ($state) {
                'series' => 'info',
                'video' => 'warning',
                default => 'success',
            })
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function parent(): TextColumn
    {
        return TextColumn::make('parent.title')
            ->label(__('resources/media/strings.table.parent'))
            ->formatStateUsing(fn(?Model $record): string => $record?->parent?->title
                ?? ($record?->parent_id ? '—' : __('resources/media/strings.table.parent_root')))
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function duration(): TextColumn
    {
        return TextColumn::make('duration_seconds')
            ->label(__('resources/media/strings.table.duration'))
            ->formatStateUsing(fn($state): string => static::durationLabel($state))
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function durationLabel(mixed $state): string
    {
        return $state
            ? convertToPersian((string) max(1, (int) round(((int) $state) / 60))) . ' ' . __('resources/media/strings.table.minutes')
            : '—';
    }

    public static function icon(): TextColumn
    {
        return TextColumn::make('icon')
            ->label(__('resources/media/strings.table.icon'))
            ->formatStateUsing(fn(?string $state): string => MediaIcon::tryFrom((string) $state)?->getLabel() ?? '—')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function kindGroup(): Group
    {
        return Group::make('kind')
            ->label(__('resources/media/strings.table.kind'))
            ->getTitleFromRecordUsing(fn(Model $record): string => static::kindLabel($record->kind))
            ->collapsible();
    }

    public static function kindFilter(): SelectFilter
    {
        return SelectFilter::make('kind')
            ->label(__('resources/media/strings.table.kind'))
            ->options([
                'series' => __('resources/media/strings.fields.kind_series'),
                'audio' => __('resources/media/strings.fields.kind_audio'),
                'video' => __('resources/media/strings.fields.kind_video'),
            ]);
    }

    public static function rootFilter(): TernaryFilter
    {
        return TernaryFilter::make('parent_id')
            ->label(__('resources/media/strings.filters.root'))
            ->trueLabel(__('resources/media/strings.filters.root_episodes'))
            ->falseLabel(__('resources/media/strings.filters.root_items'))
            ->queries(
                true: fn(Builder $q) => $q->whereNotNull('parent_id'),
                false: fn(Builder $q) => $q->whereNull('parent_id'),
            );
    }

    public static function kindLabel(string $kind): string
    {
        return match ($kind) {
            'series' => __('resources/media/strings.fields.kind_series'),
            'video' => __('resources/media/strings.fields.kind_video'),
            default => __('resources/media/strings.fields.kind_audio'),
        };
    }
}
