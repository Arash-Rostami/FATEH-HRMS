<?php

namespace App\Filament\Resources\ThsResource\Exports;

use App\Filament\Resources\ThsResource\Enums\{TicketPriority, TicketStatus};
use App\Filament\Resources\ThsResource\Schemas\TicketTablePresenter;
use App\Models\Department;
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
        return $query->with(['requester', 'assignee', 'replies']);
    }

    private static function departmentLabel(?string $code): string
    {
        static $departments;
        $departments ??= Department::getCachedModels();

        return $code ? ($departments->get($code)?->displayLabel() ?? $code) : '';
    }

    private static function fileNames(?array $files): string
    {
        return collect($files)->map(fn($item) => basename(is_array($item) ? ($item['name'] ?? $item['file'] ?? '') : (string)$item))->filter()->implode("\n");
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
                ->state(fn($record) => Ticket::getCustomRequestTypeLabel($record->request_type, $record->extra['target_department'] ?? null)),

            ExportColumn::make('request_area')
                ->label(__('resources/ths/strings.fields.request_area'))
                ->state(fn($record) => $record->request_area
                    ? Ticket::getCustomRequestAreaLabel($record->request_type, $record->request_area, $record->extra['target_department'] ?? null)
                    : ''),

            ExportColumn::make('requester.name')->label(__('resources/ths/strings.fields.requester')),

            ExportColumn::make('department')
                ->label(__('resources/ths/strings.fields.department'))
                ->state(fn($record) => self::departmentLabel($record->extra['department'] ?? null)),

            ExportColumn::make('target_department')
                ->label(__('resources/ths/strings.fields.target_department'))
                ->state(fn($record) => self::departmentLabel($record->extra['target_department'] ?? null) ?: __('resources/ths/strings.fields.target_department_default')),

            ExportColumn::make('request_subject')->label(__('resources/ths/strings.fields.subject')),

            ExportColumn::make('description')->label(__('resources/ths/strings.fields.description')),

            ExportColumn::make('additional_notes')->label(__('resources/ths/strings.fields.additional_notes')),

            ExportColumn::make('assignee.name')->label(__('resources/ths/strings.fields.assignee')),

            ExportColumn::make('completion_deadline')
                ->label(__('resources/ths/strings.fields.deadline'))
                ->state(fn($record) => $record->completion_deadline ? toJalaliSmart($record->completion_deadline) : '-'),

            ExportColumn::make('completion_date')
                ->label(__('resources/ths/strings.fields.completion_date'))
                ->state(fn($record) => $record->completion_date ? toJalaliSmart($record->completion_date) : '-'),

            ExportColumn::make('effectiveness')->label(__('resources/ths/strings.fields.effectiveness')),

            ExportColumn::make('satisfaction_score')->label(__('resources/ths/strings.fields.satisfaction')),

            ExportColumn::make('satisfaction_comment')
                ->label(__('resources/ths/strings.fields.satisfaction_comment'))
                ->state(fn($record) => $record->extra['satisfaction_comment'] ?? ''),

            ExportColumn::make('action_result')->label(__('resources/ths/strings.fields.action_result')),

            ExportColumn::make('replies')
                ->label(__('resources/ths/strings.fields.replies'))
                ->state(fn($record) => $record->replies
                    ->map(fn($reply) => sprintf('%s (%s): %s', $reply->user?->name ?? '—', toJalaliSmart($reply->created_at), $reply->body))
                    ->implode("\n")),

            ExportColumn::make('requester_files')
                ->label(__('resources/ths/strings.fields.requester_files'))
                ->state(fn($record) => self::fileNames($record->requester_files)),

            ExportColumn::make('assignee_files')
                ->label(__('resources/ths/strings.fields.assignee_files'))
                ->state(fn($record) => self::fileNames($record->assignee_files)),

            ExportColumn::make('created_at')->label(__('resources/ths/strings.fields.created_at'))
                ->formatStateUsing(fn($state) => $state ? toJalaliSmart($state) : '—'),

            ExportColumn::make('updated_at')->label(__('resources/ths/strings.fields.updated_at'))
                ->formatStateUsing(fn($state) => $state ? toJalaliSmart($state) : '—'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);

        return __('resources/ths/strings.export.completed', ['count' => $count]);
    }
}
