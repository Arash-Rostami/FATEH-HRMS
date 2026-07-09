<?php

namespace App\Filament\Resources\CredentialResource\Exports;

use App\Models\Credential;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class CredentialExporter extends Exporter
{
    protected static ?string $model = Credential::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('resources/credential/strings.export.id')),

            ExportColumn::make('user_id')
                ->label(__('resources/credential/strings.export.user_id')),

            ExportColumn::make('app_name')
                ->label(__('resources/credential/strings.export.app_name')),

            ExportColumn::make('username')
                ->label(__('resources/credential/strings.export.username')),

            ExportColumn::make('password')
                ->label(__('resources/credential/strings.export.password')),

            ExportColumn::make('link')
                ->label(__('resources/credential/strings.export.link')),

            ExportColumn::make('note')
                ->label(__('resources/credential/strings.export.note')),

            ExportColumn::make('created_at')
                ->label(__('resources/credential/strings.export.created_at'))
                ->formatStateUsing(fn ($state) => $state ? toJalaliSmart($state) : '-'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);

        return "خروجی {$count} مورد از اطلاعات ورود با موفقیت آماده شد.";
    }
}
