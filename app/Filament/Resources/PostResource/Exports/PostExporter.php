<?php

namespace App\Filament\Resources\PostResource\Exports;

use App\Models\Post;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PostExporter extends Exporter
{
    protected static ?string $model = Post::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('title')->label(__('resources/post/strings.fields.title')),
            ExportColumn::make('body')
                ->label(__('resources/post/strings.fields.body'))
                ->state(fn($record) => strip_tags($record->body ?? '')),
            ExportColumn::make('user.name')->label(__('resources/post/strings.fields.user')),
            ExportColumn::make('pinned')
                ->label(__('resources/post/strings.fields.pinned'))
                ->formatStateUsing(fn($state) => $state ? 'بله' : 'خیر'),
            ExportColumn::make('created_at')->label(__('resources/post/strings.fields.created_at')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return __('resources/post/strings.export.completed', [
            'count' => number_format($export->successful_rows),
        ]);
    }
}
