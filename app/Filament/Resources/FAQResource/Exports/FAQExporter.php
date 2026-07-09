<?php

namespace App\Filament\Resources\FAQResource\Exports;

use App\Models\FAQ;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class FAQExporter extends Exporter
{

    protected static ?string $model = FAQ::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('category')->label(__('resources/faq/strings.fields.category')),
            ExportColumn::make('question')->label(__('resources/faq/strings.fields.question')),
            ExportColumn::make('answer')->label(__('resources/faq/strings.fields.answer')),
            ExportColumn::make('user.name')->label(__('resources/faq/strings.fields.user')),
            ExportColumn::make('department.name')->label(__('resources/faq/strings.fields.department'))
                ->state(fn($record) => $record->department?->displayLabel() ?? '-'),
            ExportColumn::make('created_at')->label(__('resources/faq/strings.fields.created_at'))
                ->formatStateUsing(fn($state) => toJalaliSmart($state)),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);

        return __('resources/faq/strings.export.completed', ['count' => $count]);
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['user', 'department']);
    }
}
