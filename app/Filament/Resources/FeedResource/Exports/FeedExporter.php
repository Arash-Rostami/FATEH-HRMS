<?php

namespace App\Filament\Resources\FeedResource\Exports;

use App\Filament\Resources\FeedResource\Enums\FeedCategory;
use App\Models\Feed;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class FeedExporter extends Exporter
{
    protected static ?string $model = Feed::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('user.name')->label(__('resources/feed/strings.fields.user')),
            ExportColumn::make('category')
                ->label(__('resources/feed/strings.fields.category'))
                ->state(fn($record) => FeedCategory::tryFrom($record->category)?->getLabel() ?? $record->category),
            ExportColumn::make('content')
                ->label(__('resources/feed/strings.fields.content'))
                ->state(fn($record) => strip_tags($record->content ?? '')),
            ExportColumn::make('comments_count')
                ->label(__('resources/feed/strings.fields.comments_count'))
                ->state(fn($record) => $record->comments()->count()),
            ExportColumn::make('reactions_count')
                ->label(__('resources/feed/strings.fields.reactions_count'))
                ->state(fn($record) => $record->reactions()->count()),
            ExportColumn::make('created_at')->label(__('resources/feed/strings.fields.created_at')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);

        return __('resources/feed/strings.export.completed', ['count' => $count]);
    }
}
