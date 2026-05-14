<?php

namespace App\Filament\Resources\ReportResource\Schemas;

use App\Models\Department;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ReportTablePresenter
{
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

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/report/strings.table.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function createdAtFilter(): Filter
    {
        return Filter::make('created_at')
            ->label(__('resources/report/strings.filters.created_from'))
            ->form([
                DatePicker::make('created_from')
                    ->native(false)
                    ->label(__('resources/report/strings.filters.created_from')),
                DatePicker::make('created_until')
                    ->native(false)
                    ->label(__('resources/report/strings.filters.created_until')),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when($data['created_from'], fn($q, $d) => $q->whereDate('created_at', '>=', $d))
                    ->when($data['created_until'], fn($q, $d) => $q->whereDate('created_at', '<=', $d));
            });
    }

    public static function department(): TextColumn
    {
        return TextColumn::make('department.name')
            ->label(__('resources/report/strings.table.department'))
            ->badge()
            ->formatStateUsing(fn(?Model $record): string => $record?->department->description ?? $record?->department->name ?? '-')
            ->tooltip(fn(?Model $record): string => $record?->department->name ?? $record?->department->description ?? '-')
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
            ->label('ID')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function title(): TextColumn
    {
        return TextColumn::make('title')
            ->label(__('resources/report/strings.table.title'))
            ->sortable()
            ->searchable()
            ->limit(45)
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
}
