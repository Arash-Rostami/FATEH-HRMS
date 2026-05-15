<?php

namespace App\Filament\Resources\SuggestionResource\Exports;

use App\Filament\Resources\SuggestionResource\Enums\SuggestionStage;
use App\Models\Suggestion;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class SuggestionExporter extends Exporter
{
    protected static ?string $model = Suggestion::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('serial')
                ->label(__('resources/suggestion/strings.fields.serial'))
                ->state(fn($record): string => sprintf(
                    'SN-%s-%06d',
                    $record->created_at?->format('Ymd') ?? '00000000',
                    $record->id,
                )),
            ExportColumn::make('title')->label(__('resources/suggestion/strings.fields.title')),
            ExportColumn::make('user.name')->label(__('resources/suggestion/strings.fields.submitter')),
            ExportColumn::make('stage')
                ->label(__('resources/suggestion/strings.fields.stage'))
                ->state(fn($record) => SuggestionStage::tryFrom($record->stage)?->getLabel() ?? $record->stage),
            ExportColumn::make('departments')
                ->label(__('resources/suggestion/strings.fields.departments'))
                ->state(fn($record): string => implode('، ', (array) ($record->departments ?? []))),
            ExportColumn::make('purpose')
                ->label(__('resources/suggestion/strings.fields.purpose'))
                ->state(fn($record): string => collect((array) ($record->purpose ?? []))
                    ->map(fn(string $k): string => Suggestion::PURPOSES[$k] ?? $k)
                    ->implode('، ')),
            ExportColumn::make('agree_count')->label(__('resources/suggestion/strings.fields.agree_count')),
            ExportColumn::make('neutral_count')->label(__('resources/suggestion/strings.fields.neutral_count')),
            ExportColumn::make('disagree_count')->label(__('resources/suggestion/strings.fields.disagree_count')),
            ExportColumn::make('self_fill')->label(__('resources/suggestion/strings.fields.self_fill')),
            ExportColumn::make('created_at')->label(__('resources/suggestion/strings.fields.created_at')),
            ExportColumn::make('updated_at')->label(__('resources/suggestion/strings.fields.updated_at')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);

        return __('resources/suggestion/strings.export.completed', ['count' => $count]);
    }
}
