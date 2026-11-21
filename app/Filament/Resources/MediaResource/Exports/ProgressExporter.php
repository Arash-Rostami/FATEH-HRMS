<?php

namespace App\Filament\Resources\MediaResource\Exports;

use App\Models\KnowledgeProgress;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class ProgressExporter extends Exporter
{
    protected static ?string $model = KnowledgeProgress::class;

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['user', 'media.parent', 'report']);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('resources/media/strings.progress.export.id')),

            ExportColumn::make('user.name')
                ->label(__('resources/media/strings.progress.export.user')),

            ExportColumn::make('item')
                ->label(__('resources/media/strings.progress.export.item'))
                ->state(fn($record) => $record->media?->title ?? $record->report?->title ?? '-'),

            ExportColumn::make('series')
                ->label(__('resources/media/strings.progress.export.series'))
                ->state(fn($record) => $record->media?->parent?->title ?? '-'),

            ExportColumn::make('position')
                ->label(__('resources/media/strings.progress.export.position')),

            ExportColumn::make('completed')
                ->label(__('resources/media/strings.progress.export.completed'))
                ->state(fn($record) => $record->completed_at
                    ? __('resources/media/strings.progress.export.completed_yes')
                    : __('resources/media/strings.progress.export.completed_no')),

            ExportColumn::make('last_seen_at')
                ->label(__('resources/media/strings.progress.export.last_seen_at'))
                ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '-'),

            ExportColumn::make('created_at')
                ->label(__('resources/media/strings.progress.export.created_at'))
                ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);

        return __('resources/general/strings.notifications.export_completed', ['count' => $count]);
    }
}
