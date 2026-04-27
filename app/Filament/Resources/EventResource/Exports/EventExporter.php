<?php

namespace App\Filament\Resources\EventResource\Exports;

use App\Models\Event;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class EventExporter extends Exporter
{
    protected static ?string $model = Event::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('title')->label(__('resources/event/strings.fields.title')),
            ExportColumn::make('description')->label(__('resources/event/strings.fields.description')),
            ExportColumn::make('user.name')->label(__('resources/event/strings.fields.user')),
            ExportColumn::make('date')
                ->label(__('resources/event/strings.fields.date'))
                ->state(fn($record) => $record->date?->format('Y-m-d H:i')),
            ExportColumn::make('private')
                ->label(__('resources/event/strings.fields.private'))
                ->formatStateUsing(fn($state) => $state ? 'خصوصی' : 'عمومی'),
            ExportColumn::make('created_at')->label(__('resources/event/strings.fields.created_at')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return __('resources/event/strings.export.completed', [
            'count' => number_format($export->successful_rows),
        ]);
    }
}
