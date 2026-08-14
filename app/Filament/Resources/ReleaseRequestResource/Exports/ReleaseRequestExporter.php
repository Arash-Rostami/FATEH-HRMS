<?php

namespace App\Filament\Resources\ReleaseRequestResource\Exports;

use App\Enums\ReleaseRequestStatus;
use App\Enums\ReleaseRequestType;
use App\Models\ReleaseRequest;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class ReleaseRequestExporter extends Exporter
{
    protected static ?string $model = ReleaseRequest::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('type')->label(__('resources/release_request/strings.fields.type'))
                ->formatStateUsing(fn($state) => $state instanceof ReleaseRequestType
                    ? $state->getLabel()
                    : (ReleaseRequestType::tryFrom($state)?->getLabel() ?? $state)),
            ExportColumn::make('title')->label(__('resources/release_request/strings.fields.title')),
            ExportColumn::make('body')->label(__('resources/release_request/strings.fields.body')),
            ExportColumn::make('status')->label(__('resources/release_request/strings.fields.status'))
                ->formatStateUsing(fn($state) => $state instanceof ReleaseRequestStatus
                    ? $state->getLabel()
                    : (ReleaseRequestStatus::tryFrom($state)?->getLabel() ?? $state)),
            ExportColumn::make('response')->label(__('resources/release_request/strings.fields.response')),
            ExportColumn::make('user.name')->label(__('resources/release_request/strings.fields.user'))
                ->state(fn($record) => $record->user?->name ?? __('resources/release_request/strings.deleted_user')),
            ExportColumn::make('created_at')->label(__('resources/release_request/strings.fields.created_at'))
                ->formatStateUsing(fn($state) => toJalaliSmart($state)),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);

        return __('resources/release_request/strings.export.completed', ['count' => $count]);
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['user']);
    }
}