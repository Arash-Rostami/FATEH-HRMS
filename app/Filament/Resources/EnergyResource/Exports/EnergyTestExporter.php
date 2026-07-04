<?php

namespace App\Filament\Resources\EnergyResource\Exports;

use App\Models\EnergyTest;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class EnergyTestExporter extends Exporter
{
    protected static ?string $model = EnergyTest::class;

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['user']);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('user.name')->label(__('resources/energy/strings.fields.user')),
            ExportColumn::make('overall_score')->label(__('resources/energy/strings.fields.overall_score')),
            ExportColumn::make('physique_score')->label(__('resources/energy/strings.fields.physique_score')),
            ExportColumn::make('emotion_score')->label(__('resources/energy/strings.fields.emotion_score')),
            ExportColumn::make('mind_score')->label(__('resources/energy/strings.fields.mind_score')),
            ExportColumn::make('soul_score')->label(__('resources/energy/strings.fields.soul_score')),
            ExportColumn::make('month_index')->label(__('resources/energy/strings.fields.month_index')),
            ExportColumn::make('completed_at')
                ->label(__('resources/energy/strings.fields.completed_at'))
                ->state(fn($record) => $record->completed_at?->format('Y-m-d H:i')),
            ExportColumn::make('created_at')->label(__('resources/energy/strings.fields.created_at')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);
        return __('resources/energy/strings.export.completed', ['count' => $count]);
    }
}
