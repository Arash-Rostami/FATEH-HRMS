<?php

namespace App\Filament\Resources\DepartmentResource\Exports;

use App\Models\Department;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class DepartmentExporter extends Exporter
{
    protected static ?string $model = Department::class;

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->withCount('users');
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('resources/department/strings.export.id')),

            ExportColumn::make('code')
                ->label(__('resources/department/strings.export.code')),

            ExportColumn::make('name')
                ->label(__('resources/department/strings.export.name')),

            ExportColumn::make('description')
                ->label(__('resources/department/strings.export.description'))
                ->formatStateUsing(fn (?string $state): string => $state ?? '-'),

            ExportColumn::make('users_count')
                ->label(__('resources/department/strings.export.users_count'))
                ->state(fn (Department $record): int => $record->users_count ?? 0),

            ExportColumn::make('created_at')
                ->label(__('resources/department/strings.export.created_at'))
                ->formatStateUsing(fn ($state): string => $state?->format('Y/m/d H:i') ?? '-'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);
        return "خروجی {$count} واحدها با موفقیت آماده شد.";
    }
}
