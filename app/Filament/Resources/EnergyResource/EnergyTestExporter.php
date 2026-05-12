<?php

namespace App\Filament\Resources\EnergyTestResource\Exports;

use App\Models\EnergyTest;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class EnergyTestExporter extends Exporter
{
    protected static ?string $model = EnergyTest::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('user.name')->label(__('resources/energy_test/strings.fields.user')),
            ExportColumn::make('overall_score')->label(__('resources/energy_test/strings.fields.overall_score')),
            ExportColumn::make('mind_score')->label(__('resources/energy_test/strings.fields.mind_score')),
            ExportColumn::make('emotion_score')->label(__('resources/energy_test/strings.fields.emotion_score')),
            ExportColumn::make('physique_score')->label(__('resources/energy_test/strings.fields.physique_score')),
            ExportColumn::make('soul_score')->label(__('resources/energy_test/strings.fields.soul_score')),
            ExportColumn::make('month_index')->label(__('resources/energy_test/strings.fields.month_index')),
            ExportColumn::make('completed_at')
                ->label(__('resources/energy_test/strings.fields.completed_at'))
                ->state(fn($record) => $record->completed_at?->format('Y-m-d H:i')),
            ExportColumn::make('created_at')->label(__('resources/energy_test/strings.fields.created_at')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return __('resources/energy_test/strings.export.completed', [
            'count' => number_format($export->successful_rows),
        ]);
    }
}
