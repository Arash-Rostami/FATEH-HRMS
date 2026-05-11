<?php

namespace App\Filament\Resources\LinkResource\Exports;

use App\Filament\Resources\LinkResource\Enums\LinkType;
use App\Models\Link;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class LinkExporter extends Exporter
{
    protected static ?string $model = Link::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('sequence')->label(__('resources/link/strings.fields.sequence')),
            ExportColumn::make('url_title')->label(__('resources/link/strings.fields.url_title')),
            ExportColumn::make('url')->label(__('resources/link/strings.fields.url')),
            ExportColumn::make('internal_url')->label(__('resources/link/strings.fields.internal_url')),
            ExportColumn::make('link')
                ->label(__('resources/link/strings.fields.link_type'))
                ->state(fn($record) => LinkType::tryFrom($record->link)?->getLabel() ?? $record->link),
            ExportColumn::make('smart_routing')
                ->label(__('resources/link/strings.fields.smart_routing'))
                ->state(fn($record) => filled($record->internal_url) ? 'بله' : 'خیر'),
            ExportColumn::make('url_description')->label(__('resources/link/strings.fields.url_description')),
            ExportColumn::make('created_at')->label(__('resources/link/strings.fields.created_at')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);

        return __('resources/link/strings.export.completed', ['count' => $count]);
    }
}
