<?php

namespace App\Filament\Resources\AdResource\Exports;

use App\Filament\Resources\AdResource\Enums\AdGender;
use App\Filament\Resources\AdResource\Enums\AdStatus;
use App\Models\Ad;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class AdExporter extends Exporter
{

    protected static ?string $model = Ad::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('position')->label(__('resources/ad/strings.fields.position')),
            ExportColumn::make('gender')
                ->label(__('resources/ad/strings.fields.gender'))
                ->formatStateUsing(fn(string $state) => AdGender::from($state)->getLabel()),
            ExportColumn::make('active')
                ->label(__('resources/ad/strings.fields.active'))
                ->formatStateUsing(fn(bool $state) => AdStatus::fromBool($state)->getLabel()),
            ExportColumn::make('link')->label(__('resources/ad/strings.fields.link')),
            ExportColumn::make('certificate')->label(__('resources/ad/strings.fields.certificate')),
            ExportColumn::make('skill')->label(__('resources/ad/strings.fields.skill')),
            ExportColumn::make('experience')->label(__('resources/ad/strings.fields.experience')),
            ExportColumn::make('extra')
                ->label(__('resources/ad/strings.fields.extra'))
                ->formatStateUsing(function ($state) {
                    if (!is_array($state)) return '-';
                    $formatted = [];
                    foreach ($state as $item) {
                        if (isset($item['key']) && isset($item['value'])) {
                            $formatted[] = $item['key'] . ': ' . $item['value'];
                        }
                    }
                    return implode(', ', $formatted) ?: '-';
                }),
            ExportColumn::make('created_at')->label(__('resources/ad/strings.fields.created_at')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);

        return __('resources/ad/strings.export.completed', ['count' => $count]);
    }
}
