<?php

namespace App\Filament\Resources\PermissionResource\Exports;

use App\Models\Permission;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PermissionExporter extends Exporter
{
    protected static ?string $model = Permission::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('resources/permission/strings.export.id')),

            ExportColumn::make('user.name')
                ->label(__('resources/permission/strings.export.user')),

            ExportColumn::make('is_super_admin')
                ->label(__('resources/permission/strings.export.is_super_admin'))
                ->formatStateUsing(fn($state): string => $state ? '✓' : '-'),

            ExportColumn::make('modules')
                ->label(__('resources/permission/strings.export.modules'))
                ->state(fn(Permission $record): string => implode(', ', $record->allowedModules())),

            ExportColumn::make('created_at')
                ->label(__('resources/permission/strings.export.created_at'))
                ->formatStateUsing(fn($state): string => $state?->format('Y/m/d H:i') ?? '-'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);
        return "خروجی {$count} دسترسی با موفقیت آماده شد.";
    }
}
