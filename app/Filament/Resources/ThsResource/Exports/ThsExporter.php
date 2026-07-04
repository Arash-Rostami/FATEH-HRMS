<?php

namespace App\Filament\Resources\ThsResource\Exports;

use App\Filament\Resources\ThsResource\Enums\{RequestType, TicketPriority, TicketStatus};
use App\Filament\Resources\ThsResource\Schemas\TicketTablePresenter;
use App\Models\Ticket;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class ThsExporter extends Exporter
{
    protected static ?string $model = Ticket::class;

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['requester', 'assignee']);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('ticket_id')
                ->label(__('resources/ths/strings.fields.ticket_id'))
                ->state(fn($record) => TicketTablePresenter::formatTicketId($record)),

            ExportColumn::make('status')
                ->label(__('resources/ths/strings.fields.status'))
                ->state(fn($record) => TicketStatus::tryFrom($record->status)?->getLabel() ?? $record->status),

            ExportColumn::make('priority')
                ->label(__('resources/ths/strings.fields.priority'))
                ->state(fn($record) => TicketPriority::tryFrom($record->priority)?->getLabel() ?? $record->priority),

            ExportColumn::make('request_type')
                ->label(__('resources/ths/strings.fields.request_type'))
                ->state(fn($record) => RequestType::tryFrom($record->request_type)?->getLabel() ?? $record->request_type),

            ExportColumn::make('request_area')
                ->label(__('resources/ths/strings.fields.request_area'))
                ->state(fn($record) => Ticket::$requestAreaOptions[$record->request_type][$record->request_area] ?? $record->request_area),

            ExportColumn::make('requester.name')->label(__('resources/ths/strings.fields.requester')),

            ExportColumn::make('department')
                ->label(__('resources/ths/strings.fields.department'))
                ->state(fn($record) => $record->extra['department'] ?? ''),

            ExportColumn::make('request_subject')->label(__('resources/ths/strings.fields.subject')),

            ExportColumn::make('assignee.name')->label(__('resources/ths/strings.fields.assignee')),

            ExportColumn::make('completion_deadline')
                ->label(__('resources/ths/strings.fields.deadline'))
                ->state(fn($record) => $record->completion_deadline?->format('Y-m-d H:i')),

            ExportColumn::make('completion_date')
                ->label(__('resources/ths/strings.fields.completion_date'))
                ->state(fn($record) => $record->completion_date?->format('Y-m-d H:i')),

            ExportColumn::make('effectiveness')->label(__('resources/ths/strings.fields.effectiveness')),

            ExportColumn::make('satisfaction_score')->label(__('resources/ths/strings.fields.satisfaction')),

            ExportColumn::make('action_result')->label(__('resources/ths/strings.fields.action_result')),

            ExportColumn::make('created_at')->label(__('resources/ths/strings.fields.created_at')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);

        return __('resources/ths/strings.export.completed', ['count' => $count]);
    }
}
