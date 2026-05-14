<?php

namespace App\Filament\Resources\GalleryResource\Schemas;

use App\Models\Department;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GalleryTablePresenter
{
    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/gallery/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function department(): TextColumn
    {
        return TextColumn::make('department.description')
            ->label(__('resources/gallery/strings.fields.department'))
            ->placeholder(__('resources/gallery/strings.fields.public_gallery'))
            ->badge()
            ->color(fn($state) => $state ? 'warning' : 'success')
            ->icon(fn($record) => $record->department_id ? 'heroicon-o-lock-closed' : 'heroicon-o-globe-alt')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function departmentFilter(): SelectFilter
    {
        return SelectFilter::make('department_id')
            ->label(__('resources/gallery/strings.fields.department'))
            ->options(fn() => Department::getCachedOptions()->toArray())
            ->searchable()
            ->preload();
    }

    public static function departmentGroup(): Group
    {
        return Group::make('department.description')
            ->label(__('resources/gallery/strings.fields.department'))
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->department?->description ?? __('resources/gallery/strings.fields.public_gallery'))
            ->collapsible();
    }

    public static function eventDate(): TextColumn
    {
        return TextColumn::make('event_date')
            ->label(__('resources/gallery/strings.fields.event_date'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->sortable()
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function eventDateRangeFilter(): Filter
    {
        return Filter::make('event_date_range')
            ->label(__('resources/gallery/strings.filters.event_date_range'))
            ->schema([
                Grid::make(2)->schema([
                    DatePicker::make('from')->native(false)->label(__('resources/gallery/strings.filters.date_from')),
                    DatePicker::make('until')->native(false)->label(__('resources/gallery/strings.filters.date_until'))
                ])
            ])
            ->query(fn(Builder $query, array $data) => $query
                ->when($data['from'], fn($query, $v) => $query->whereDate('event_date', '>=', $v))
                ->when($data['until'], fn($query, $v) => $query->whereDate('event_date', '<=', $v))
            );
    }

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label('ID')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function photosCount(): TextColumn
    {
        return TextColumn::make('photos_count')
            ->label(__('resources/gallery/strings.fields.count'))
            ->getStateUsing(fn($record) => count($record->path ?? []))
            ->badge()
            ->color('primary')
            ->icon('heroicon-o-photo')
            ->sortable(false)
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function preview(): ImageColumn
    {
        return ImageColumn::make('preview')
            ->label(__('resources/gallery/strings.fields.preview'))
            ->disk('public')
            ->square()
            ->imageSize(56)
            ->getStateUsing(fn($record) => collect($record->path ?? [])->first())
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function title(): TextColumn
    {
        return TextColumn::make('title')
            ->label(__('resources/gallery/strings.fields.title'))
            ->searchable()
            ->sortable()
            ->limit(50)
            ->tooltip(fn($state) => strlen($state ?? '') > 50 ? $state : null)
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function visibilityFilter(): TernaryFilter
    {
        return TernaryFilter::make('visibility')
            ->label(__('resources/gallery/strings.filters.visibility'))
            ->trueLabel(__('resources/gallery/strings.filters.private'))
            ->falseLabel(__('resources/gallery/strings.filters.public'))
            ->queries(
                true: fn(Builder $q) => $q->whereNotNull('department_id'),
                false: fn(Builder $q) => $q->whereNull('department_id'),
            );
    }
}
