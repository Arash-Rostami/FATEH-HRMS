<?php

namespace App\Filament\Resources\GalleryResource\Schemas;

use App\Models\Department;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

class GalleryTablePresenter
{
    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label('ID')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function preview(): ImageColumn
    {
        return ImageColumn::make('preview')
            ->label(__('resources/gallery/strings.fields.preview'))
            ->disk('public')
            ->square()
            ->size(56)
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

    public static function department(): TextColumn
    {
        return TextColumn::make('department.name')
            ->label(__('resources/gallery/strings.fields.department'))
            ->placeholder(__('resources/gallery/strings.fields.public_gallery'))
            ->badge()
            ->color(fn($state) => $state ? 'warning' : 'success')
            ->icon(fn($record) => $record->department_id ? 'heroicon-o-lock-closed' : 'heroicon-o-globe-alt')
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

    public static function eventDate(): TextColumn
    {
        return TextColumn::make('event_date')
            ->label(__('resources/gallery/strings.fields.event_date'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->sortable()
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/gallery/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    // ─── Groups ───────────────────────────────────────────────────────────────

    public static function departmentGroup(): Group
    {
        return Group::make('department.name')
            ->label(__('resources/gallery/strings.fields.department'))
            ->getTitleFromRecordUsing(fn($r) => $r->department?->name ?? __('resources/gallery/strings.fields.public_gallery'))
            ->collapsible();
    }

    // ─── Filters ──────────────────────────────────────────────────────────────

    public static function departmentFilter(): SelectFilter
    {
        return SelectFilter::make('department_id')
            ->label(__('resources/gallery/strings.fields.department'))
            ->options(fn() => Department::pluck('name', 'code'))
            ->searchable()
            ->preload();
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

    public static function eventDateRangeFilter(): Filter
    {
        return Filter::make('event_date_range')
            ->label(__('resources/gallery/strings.filters.event_date_range'))
            ->form([
                DatePicker::make('from')->label(__('resources/gallery/strings.filters.date_from')),
                DatePicker::make('until')->label(__('resources/gallery/strings.filters.date_until')),
            ])
            ->query(fn(Builder $q, array $data) => $q
                ->when($data['from'], fn($q, $v) => $q->whereDate('event_date', '>=', $v))
                ->when($data['until'], fn($q, $v) => $q->whereDate('event_date', '<=', $v))
            );
    }
}
