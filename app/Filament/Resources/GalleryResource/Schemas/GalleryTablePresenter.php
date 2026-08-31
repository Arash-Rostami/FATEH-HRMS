<?php

namespace App\Filament\Resources\GalleryResource\Schemas;

use App\Models\Department;
use App\Traits\FilamentFilters;
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
    use FilamentFilters;

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
        return TextColumn::make('all_departments_string')
            ->label(__('resources/gallery/strings.fields.department'))
            ->placeholder(__('resources/gallery/strings.fields.public_gallery'))
            ->badge()
            ->color(fn($record) => count($record->all_departments) > 1 ? 'info' : (count($record->all_departments) === 1 ? 'warning' : 'success'))
            ->icon(fn($record) => count($record->all_departments) > 1 ? 'heroicon-o-users' : (count($record->all_departments) === 1 ? 'heroicon-o-lock-closed' : 'heroicon-o-globe-alt'))
            ->getStateUsing(function ($record) {
                $models = $record->all_department_models;
                if ($models->isEmpty()) return null;
                return $models->map(fn($d) => $d->displayLabel())->join(', ');
            })
            ->tooltip(function ($record) {
                $models = $record->all_department_models;
                if ($models->isEmpty()) return null;
                return $models->map(fn($d) => $d->tooltipLabel())->join(', ');
            })
            ->sortable(false)
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function departmentFilter(): SelectFilter
    {
        return SelectFilter::make('department_id')
            ->label(__('resources/gallery/strings.fields.department'))
            ->options(fn() => Department::getCachedOptions()->toArray())
            ->searchable()
            ->preload()
            ->query(function (Builder $query, array $data) {
                if (!empty($data['value'])) {
                    $query->where(function ($q) use ($data) {
                        $q->where('department_id', $data['value'])
                            ->orWhereJsonContains('departments', $data['value']);
                    });
                }
            });
    }

    public static function departmentGroup(): Group
    {
        return Group::make('department.description')
            ->label(__('resources/gallery/strings.fields.department'))
            ->getTitleFromRecordUsing(function (Model $record): string {
                $models = $record->all_department_models;
                return $models->isEmpty() ? __('resources/gallery/strings.fields.public_gallery') : $models->map(fn($d) => $d->displayLabel())->join(', ');
            })
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
        return self::jalaliDateRangeFilter(
            'event_date_range',
            'event_date',
            __('resources/gallery/strings.filters.event_date_range'),
            __('resources/gallery/strings.filters.date_from'),
            __('resources/gallery/strings.filters.date_until'),
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
            ->getStateUsing(function ($record) {
                $first = collect($record->path ?? [])->first();
                if ($first && in_array(strtolower(pathinfo($first, PATHINFO_EXTENSION)), ['mp4', 'webm', 'mov'], true)) {
                    return null;
                }
                return $first;
            })
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
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function visibilityFilter(): TernaryFilter
    {
        return TernaryFilter::make('visibility')
            ->label(__('resources/gallery/strings.filters.visibility'))
            ->trueLabel(__('resources/gallery/strings.filters.private'))
            ->falseLabel(__('resources/gallery/strings.filters.public'))
            ->queries(
                true: fn(Builder $q) => $q->where(fn($sq) => $sq->whereNotNull('department_id')->orWhereNotNull('departments')),
                false: fn(Builder $q) => $q->whereNull('department_id')->whereNull('departments'),
            );
    }

    public static function sharedFilter(): TernaryFilter
    {
        return TernaryFilter::make('shared_type')
            ->label(__('resources/gallery/strings.filters.shared_type') ?? 'Sharing')
            ->trueLabel(__('resources/gallery/strings.filters.multiple_departments') ?? 'Multiple Departments')
            ->falseLabel(__('resources/gallery/strings.filters.single_department') ?? 'Single Department')
            ->queries(
                true: fn(Builder $query) => $query->whereNotNull('departments')->whereRaw('JSON_LENGTH(departments) > 0'),
                false: fn(Builder $query) => $query->whereNotNull('department_id')->where(fn($q) => $q->whereNull('departments')->orWhereRaw('JSON_LENGTH(departments) = 0')),
            );
    }
}
