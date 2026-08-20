<?php

namespace App\Filament\Resources\ResourceResource\Exports;

use App\Models\Resource as ResourceModel;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class ResourceExporter extends Exporter
{
    protected static ?string $model = ResourceModel::class;

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->withCount('reservations');
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label(__('resources/resource/strings.export.id')),
            ExportColumn::make('name')->label(__('resources/resource/strings.export.name')),
            ExportColumn::make('type')->label(__('resources/resource/strings.export.type')),
            ExportColumn::make('status')->label(__('resources/resource/strings.export.status')),
            ExportColumn::make('metadata.floor')->label(__('resources/resource/strings.export.floor'))->formatStateUsing(fn($state) => $state ?? '-'),
            ExportColumn::make('metadata.unit')->label(__('resources/resource/strings.export.unit'))->formatStateUsing(fn($state) => $state ?? '-'),
            ExportColumn::make('metadata.card')->label(__('resources/resource/strings.export.card_number'))->formatStateUsing(fn($state) => $state ?? '-'),
            ExportColumn::make('metadata.extension')->label(__('resources/resource/strings.export.extension'))->formatStateUsing(fn($state) => $state ?? '-'),
            ExportColumn::make('metadata.capacity')->label(__('resources/resource/strings.export.capacity'))->formatStateUsing(fn($state) => $state ?? '-'),
            ExportColumn::make('metadata.available_days')->label(__('resources/resource/strings.export.available_days'))
                ->state(fn(ResourceModel $record) => is_array($record->metadata['available_days'] ?? null)
                    ? collect($record->metadata['available_days'])->map(fn($day) => __("resources/policy/strings.days.{$day}"))->implode('، ')
                    : null)
                ->formatStateUsing(fn($state) => $state ?: '-'),
            ExportColumn::make('metadata.time_slots')->label(__('resources/resource/strings.export.time_slots'))
                ->state(fn(ResourceModel $record) => isset($record->metadata['time_slots']['start'], $record->metadata['time_slots']['end'])
                    ? $record->metadata['time_slots']['start'] . ' - ' . $record->metadata['time_slots']['end']
                    : null)
                ->formatStateUsing(fn($state) => $state ?: '-'),
            ExportColumn::make('reservations_count')->label('تعداد رزروها')->state(fn(ResourceModel $record): int => $record->reservations_count ?? 0),
            ExportColumn::make('created_at')->label(__('resources/resource/strings.export.created_at'))->formatStateUsing(fn($state) => $state ? toJalaliSmart($state) : '-'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'خروجی ' . number_format($export->successful_rows) . ' منبع با موفقیت آماده شد.';
    }
}
