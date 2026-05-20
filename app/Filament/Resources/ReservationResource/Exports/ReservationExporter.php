<?php

namespace App\Filament\Resources\ReservationResource\Exports;

use App\Enums\CancelReason;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ReservationExporter extends Exporter
{
    protected static ?string $model = Reservation::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label(__('resources/reservation/strings.export.id')),
            ExportColumn::make('user.name')->label(__('resources/reservation/strings.export.user')),
            ExportColumn::make('resource.name')->label(__('resources/reservation/strings.export.resource')),
            ExportColumn::make('resource.type')->label(__('resources/reservation/strings.export.type')),
            ExportColumn::make('start_time')->label(__('resources/reservation/strings.export.start_time'))->formatStateUsing(fn($state) => $state?->format('Y/m/d H:i') ?? '-'),
            ExportColumn::make('end_time')->label(__('resources/reservation/strings.export.end_time'))->formatStateUsing(fn($state) => $state?->format('Y/m/d H:i') ?? '-'),
            ExportColumn::make('is_full_day')->label(__('resources/reservation/strings.export.is_full_day'))->formatStateUsing(fn(bool $state) => $state ? 'بله' : 'خیر'),
            ExportColumn::make('status')->label(__('resources/reservation/strings.export.status'))->formatStateUsing(fn(string $state) => ReservationStatus::tryFrom($state)?->getLabel() ?? $state),
            ExportColumn::make('cancel_reason')->label(__('resources/reservation/strings.export.cancel_reason'))->formatStateUsing(fn(?string $state) => $state ? CancelReason::tryFrom($state)?->getLabel() ?? $state : '-'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'خروجی ' . number_format($export->successful_rows) . ' رزرو با موفقیت آماده شد.';
    }
}
