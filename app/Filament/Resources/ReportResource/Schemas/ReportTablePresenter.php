<?php

namespace App\Filament\Resources\ReportResource\Schemas;

use App\Models\Department;
use App\Traits\FilamentFilters;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ReportTablePresenter
{
    use FilamentFilters;

    public static function active(): IconColumn
    {
        return IconColumn::make('active')
            ->label(__('resources/report/strings.table.active'))
            ->boolean()
            ->trueIcon('heroicon-o-check-circle')
            ->falseIcon('heroicon-o-x-circle')
            ->trueColor('success')
            ->falseColor('danger')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function activeFilter(): TernaryFilter
    {
        return TernaryFilter::make('active')
            ->label(__('resources/report/strings.filters.active'))
            ->trueLabel(__('resources/report/strings.filters.active_active'))
            ->falseLabel(__('resources/report/strings.filters.active_inactive'))
            ->native(false);
    }

    public static function activeGroup(): Group
    {
        return Group::make('active')
            ->label(__('resources/report/strings.groups.active'))
            ->getTitleFromRecordUsing(fn($record): string => $record->active
                ? __('resources/report/strings.filters.active_active')
                : __('resources/report/strings.filters.active_inactive'))
            ->collapsible();
    }

    public static function audience(): TextColumn
    {
        return TextColumn::make('audience')
            ->label(__('resources/report/strings.table.audience'))
            ->placeholder(__('resources/report/strings.filters.visibility_public'))
            ->badge()
            ->color(fn($record) => $record->is_public ? 'success' : ($record->audience_departments->count() > 1 ? 'info' : 'warning'))
            ->icon(fn($record) => $record->is_public
                ? 'heroicon-o-globe-alt'
                : ($record->audience_departments->count() > 1 ? 'heroicon-o-users' : 'heroicon-o-lock-closed'))
            ->getStateUsing(function ($record) {
                $models = $record->audience_departments;
                if ($models->isEmpty()) {
                    return null;
                }
                return $models->map(fn($d) => $d->displayLabel())->join('، ');
            })
            ->tooltip(function ($record) {
                $models = $record->audience_departments;
                if ($models->isEmpty()) {
                    return null;
                }
                return $models->map(fn($d) => $d->tooltipLabel())->join('، ');
            })
            ->sortable(false)
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/report/strings.table.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function department(): TextColumn
    {
        return TextColumn::make('department.name')
            ->label(__('resources/report/strings.table.department'))
            ->badge()
            ->formatStateUsing(fn(?Model $record): string => $record?->department?->displayLabel() ?? '-')
            ->tooltip(fn(?Model $record): string => $record?->department?->tooltipLabel() ?? '-')
            ->color('info')
            ->sortable()
            ->searchable()
            ->placeholder('-')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function departmentFilter(): SelectFilter
    {
        return SelectFilter::make('department_id')
            ->label(__('resources/report/strings.filters.department'))
            ->options(fn() => Department::getCachedOptions()->toArray())
            ->searchable();
    }

    public static function departmentGroup(): Group
    {
        return Group::make('department.description')
            ->label(__('resources/report/strings.groups.department'))
            ->getTitleFromRecordUsing(fn(?Model $record): string => $record?->department?->displayLabel() ?? '-')
            ->collapsible();
    }

    public static function fileType(): TextColumn
    {
        return TextColumn::make('file_type')
            ->label(__('resources/report/strings.table.file_type'))
            ->badge()
            ->color(fn(string $state): string => match ($state) {
                'pdf' => 'danger',
                'docx', 'doc' => 'info',
                default => 'gray',
            })
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label(__('resources/report/strings.table.id'))
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function pinned(): IconColumn
    {
        return IconColumn::make('pinned')
            ->label(__('resources/report/strings.table.pinned'))
            ->boolean()
            ->trueIcon('heroicon-o-bookmark')
            ->falseIcon('heroicon-o-bookmark')
            ->trueColor('warning')
            ->falseColor('gray')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function pinnedFilter(): TernaryFilter
    {
        return TernaryFilter::make('pinned')
            ->label(__('resources/report/strings.filters.pinned'))
            ->trueLabel(__('resources/report/strings.filters.pinned_only'))
            ->falseLabel(__('resources/report/strings.filters.pinned_not'))
            ->native(false);
    }

    public static function pinnedGroup(): Group
    {
        return Group::make('pinned')
            ->label(__('resources/report/strings.groups.pinned'))
            ->getTitleFromRecordUsing(fn($record): string => $record->pinned
                ? __('resources/report/strings.filters.pinned_only')
                : __('resources/report/strings.filters.pinned_not'))
            ->collapsible();
    }

    public static function reportDate(): TextColumn
    {
        return TextColumn::make('report_date')
            ->label(__('resources/report/strings.table.report_date'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->sortable()
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function reportDateRangeFilter(): Filter
    {
        return self::jalaliDateRangeFilter(
            'report_date_range',
            'report_date',
            __('resources/report/strings.filters.report_date_range'),
            __('resources/report/strings.filters.date_from'),
            __('resources/report/strings.filters.date_until'),
        );
    }

    public static function shareWithDepartmentsBulkAction(): BulkAction
    {
        return BulkAction::make('share_with_departments')
            ->label(__('resources/report/strings.bulk.share_with_departments'))
            ->icon('heroicon-o-share')
            ->color('warning')
            ->schema([
                Select::make('departments')
                    ->label(__('resources/report/strings.fields.departments'))
                    ->options(fn() => Department::getCachedOptions()->toArray())
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->helperText(__('resources/report/strings.bulk.share_description')),
            ])
            ->action(function (Collection $records, array $data): void {
                $depts = $data['departments'] ?? [];
                $records->each(fn($record) => $record->update(['departments' => $depts]));
                Notification::make()
                    ->title(__('resources/report/strings.bulk.share_done'))
                    ->success()
                    ->send();
            });
    }

    public static function title(): TextColumn
    {
        return TextColumn::make('title')
            ->label(__('resources/report/strings.table.title'))
            ->sortable()
            ->searchable()
            ->limit(45)
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function user(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label(__('resources/report/strings.table.user'))
            ->sortable()
            ->searchable()
            ->placeholder('-')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function visibilityFilter(): TernaryFilter
    {
        return TernaryFilter::make('visibility')
            ->label(__('resources/report/strings.filters.visibility'))
            ->trueLabel(__('resources/report/strings.filters.visibility_restricted'))
            ->falseLabel(__('resources/report/strings.filters.visibility_public'))
            ->native(false)
            ->queries(
                true: fn(Builder $q) => $q->whereNotNull('departments')->whereRaw('JSON_LENGTH(departments) > 0'),
                false: fn(Builder $q) => $q->where(fn($x) => $x->whereNull('departments')->orWhereRaw('JSON_LENGTH(departments) = 0')),
            );
    }
}