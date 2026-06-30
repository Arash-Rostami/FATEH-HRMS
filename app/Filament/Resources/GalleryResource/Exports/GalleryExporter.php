<?php

namespace App\Filament\Resources\GalleryResource\Exports;

use App\Models\Photo;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class GalleryExporter extends Exporter
{
    protected static ?string $model = Photo::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('title')->label(__('resources/gallery/strings.fields.title')),
            ExportColumn::make('description')->label(__('resources/gallery/strings.fields.description')),
            ExportColumn::make('department_string')
                ->label(__('resources/gallery/strings.fields.department'))
                ->state(function ($record) {
                    $models = $record->all_department_models;
                    if ($models->isEmpty()) return __('resources/gallery/strings.fields.public_gallery');
                    return $models->map(fn($d) => $d->displayLabel())->join(' | ');
                }),
            ExportColumn::make('event_date')
                ->label(__('resources/gallery/strings.fields.event_date'))
                ->state(fn($record) => $record->event_date?->format('Y-m-d')),
            ExportColumn::make('photos_count')
                ->label(__('resources/gallery/strings.fields.count'))
                ->state(fn($record) => count($record->path ?? [])),
            ExportColumn::make('created_at')->label(__('resources/gallery/strings.fields.created_at')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);

        return __('resources/gallery/strings.export.completed', ['count' => $count]);
    }
}
