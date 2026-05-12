<?php

namespace App\Filament\Resources\DmsResource\Exports;

use App\Filament\Resources\DmsResource\Enums\DocumentStatus;
use App\Models\DMS;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class DmsExporter extends Exporter
{
    protected static ?string $model = DMS::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),

            ExportColumn::make('title')
                ->label(__('resources/dms/strings.fields.title')),

            ExportColumn::make('code')
                ->label(__('resources/dms/strings.fields.code')),

            ExportColumn::make('version')
                ->label(__('resources/dms/strings.fields.version')),

            ExportColumn::make('status')
                ->label(__('resources/dms/strings.fields.status'))
                ->state(fn($record) => DocumentStatus::tryFrom($record->status)?->getLabel() ?? $record->status),

            ExportColumn::make('owners_display')
                ->label(__('resources/dms/strings.fields.owners'))
                ->state(fn($record) => $record->getDepartmentNames()),

            ExportColumn::make('users_count')
                ->label(__('resources/dms/strings.fields.users_count'))
                ->state(fn($record) => count($record->users ?? [])),

            ExportColumn::make('combined_read_count')
                ->label(__('resources/dms/strings.fields.read_count')),

            ExportColumn::make('revision')
                ->label(__('resources/dms/strings.fields.revision')),

            ExportColumn::make('created_at')
                ->label(__('resources/dms/strings.fields.created_at')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);

        return __('resources/dms/strings.export.completed', ['count' => $count]);
    }
}
