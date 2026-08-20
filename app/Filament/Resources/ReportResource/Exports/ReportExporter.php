<?php

namespace App\Filament\Resources\ReportResource\Exports;

use App\Models\Report;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class ReportExporter extends Exporter
{
    protected static ?string $model = Report::class;

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['user', 'department']);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('resources/report/strings.export.id')),

            ExportColumn::make('title')
                ->label(__('resources/report/strings.export.title')),

            ExportColumn::make('description')
                ->label(__('resources/report/strings.export.description')),

            ExportColumn::make('department.name')
                ->label(__('resources/report/strings.export.department'))
                ->state(fn($record) => $record->department?->displayLabel() ?? '-'),

            ExportColumn::make('audience')
                ->label(__('resources/report/strings.export.audience'))
                ->state(fn($record) => $record->audience_departments->isEmpty()
                    ? __('resources/report/strings.filters.visibility_public')
                    : $record->audience_departments->map(fn($d) => $d->displayLabel())->join('، ')),

            ExportColumn::make('user.name')
                ->label(__('resources/report/strings.export.user')),

            ExportColumn::make('file_type')
                ->label(__('resources/report/strings.export.file_type')),

            ExportColumn::make('active')
                ->label(__('resources/report/strings.export.active'))
                ->formatStateUsing(fn(bool $state): string => $state
                    ? __('resources/report/strings.filters.active_active')
                    : __('resources/report/strings.filters.active_inactive')),

            ExportColumn::make('pinned')
                ->label(__('resources/report/strings.export.pinned'))
                ->formatStateUsing(fn($state): string => $state
                    ? __('resources/report/strings.filters.pinned_only')
                    : __('resources/report/strings.filters.pinned_not')),

            ExportColumn::make('report_date')
                ->label(__('resources/report/strings.export.report_date'))
                ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-'),

            ExportColumn::make('expires_at')
                ->label(__('resources/report/strings.export.expires_at'))
                ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-'),

            ExportColumn::make('created_at')
                ->label(__('resources/report/strings.export.created_at'))
                ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);

        return __('resources/general/strings.notifications.export_completed', ['count' => $count]);
    }
}
