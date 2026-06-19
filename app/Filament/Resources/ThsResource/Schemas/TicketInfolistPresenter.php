<?php

namespace App\Filament\Resources\ThsResource\Schemas;

use App\Filament\Resources\ThsResource\Enums\{TicketPriority, TicketStatus};
use App\Models\Ticket;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\TextSize;

class TicketInfolistPresenter
{
    public static function actionResult(): TextEntry
    {
        return TextEntry::make('action_result')
            ->label(__('resources/ths/strings.fields.action_result'))
            ->placeholder('—')
            ->columnSpanFull();
    }

    public static function additionalNotes(): TextEntry
    {
        return TextEntry::make('additional_notes')
            ->label(__('resources/ths/strings.fields.additional_notes'))
            ->placeholder('—')
            ->columnSpanFull();
    }

    public static function assignee(): TextEntry
    {
        return TextEntry::make('assignee.name')
            ->label(__('resources/ths/strings.fields.assignee'))
            ->placeholder(__('resources/ths/strings.fields.unassigned'))
            ->icon('heroicon-o-user-circle');
    }

    public static function assigneeFiles(): RepeatableEntry
    {
        return RepeatableEntry::make('assignee_files')
            ->label(__('resources/ths/strings.fields.assignee_files'))
            ->schema([
                TextEntry::make('file')
                    ->label(__('resources/ths/strings.fields.file'))
                    ->url(fn($state) => asset('storage/' . $state), true)
                    ->icon('heroicon-o-paper-clip'),
            ])
            ->columnSpanFull()
            ->hidden(fn($record) => empty($record->assignee_files));
    }

    public static function completionDate(): TextEntry
    {
        return TextEntry::make('completion_date')
            ->label(__('resources/ths/strings.fields.completion_date'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : null)
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->placeholder('—')
            ->color('success')
            ->icon('heroicon-o-check-circle');
    }

    public static function completionDeadline(): TextEntry
    {
        return TextEntry::make('completion_deadline')
            ->label(__('resources/ths/strings.fields.deadline'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : null)
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->placeholder('—')
            ->color(fn($record) => TicketTablePresenter::deadlineColor($record))
            ->icon('heroicon-o-calendar');
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/ths/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->color('gray')
            ->icon('heroicon-o-clock');
    }

    public static function department(): TextEntry
    {
        return TextEntry::make('department.description')
            ->label(__('resources/ths/strings.fields.department'))
            ->placeholder('—')
            ->icon('heroicon-o-user');
    }

    public static function description(): TextEntry
    {
        return TextEntry::make('description')
            ->label(__('resources/ths/strings.fields.description'))
            ->columnSpanFull();
    }

    public static function effectiveness(): TextEntry
    {
        return TextEntry::make('effectiveness')
            ->label(__('resources/ths/strings.fields.effectiveness'))
            ->getStateUsing(fn($record) => $record->effectiveness !== null
                ? str_repeat('✮', (int)$record->effectiveness) . ' (' . $record->effectiveness . '/5)'
                : null
            )
            ->placeholder('—')
            ->color(fn($record) => match (true) {
                $record->effectiveness === null => 'gray',
                (int)$record->effectiveness >= 4 => 'success',
                (int)$record->effectiveness >= 2 => 'warning',
                default => 'danger',
            });
    }

    public static function priority(): TextEntry
    {
        return TextEntry::make('priority')
            ->label(__('resources/ths/strings.fields.priority'))
            ->getStateUsing(fn($record) => TicketPriority::tryFrom($record->priority))
            ->badge();
    }

    public static function requestArea(): TextEntry
    {
        return TextEntry::make('request_area')
            ->label(__('resources/ths/strings.fields.request_area'))
            ->getStateUsing(fn($record) => Ticket::getCustomRequestAreaLabel($record->request_type, $record->request_area, $record->extra['target_department'] ?? null))
            ->placeholder('—');
    }

    public static function requestType(): TextEntry
    {
        return TextEntry::make('request_type')
            ->label(__('resources/ths/strings.fields.request_type'))
            ->getStateUsing(fn($record) => $record->request_type)
            ->badge();
    }

    public static function requester(): TextEntry
    {
        return TextEntry::make('requester.name')
            ->label(__('resources/ths/strings.fields.requester'))
            ->placeholder('—')
            ->icon('heroicon-o-user');
    }

    public static function requesterFiles(): RepeatableEntry
    {
        return RepeatableEntry::make('requester_files')
            ->label(__('resources/ths/strings.fields.requester_files'))
            ->schema([
                TextEntry::make('file')
                    ->label(__('resources/ths/strings.fields.file'))
                    ->url(fn($state) => asset('storage/' . $state), true)
                    ->icon('heroicon-o-paper-clip'),
            ])
            ->columnSpanFull()
            ->hidden(fn($record) => empty($record->requester_files));
    }

    public static function satisfaction(): TextEntry
    {
        return TextEntry::make('satisfaction_score')
            ->label(__('resources/ths/strings.fields.satisfaction'))
            ->getStateUsing(fn($record) => $record->satisfaction_score !== null
                ? str_repeat('✮', (int)round($record->satisfaction_score)) . ' (' . number_format($record->satisfaction_score, 1) . '/5)'
                : null
            )
            ->placeholder('—')
            ->helperText(fn($record) => $record->extra['satisfaction_comment'] ?? null)
            ->color(fn($record) => match (true) {
                $record->satisfaction_score === null => 'gray',
                $record->satisfaction_score >= 4 => 'success',
                $record->satisfaction_score >= 2 => 'warning',
                default => 'danger',
            });
    }

    public static function status(): TextEntry
    {
        return TextEntry::make('status')
            ->label(__('resources/ths/strings.fields.status'))
            ->getStateUsing(fn($record) => TicketStatus::tryFrom($record->status))
            ->badge();
    }

    public static function subject(): TextEntry
    {
        return TextEntry::make('request_subject')
            ->label(__('resources/ths/strings.fields.subject'))
            ->size(TextSize::Medium)
            ->weight(FontWeight::SemiBold)
            ->columnSpanFull();
    }

    public static function targetDepartment(): TextEntry
    {
        return TextEntry::make('targetDepartment.description')
            ->label(__('resources/ths/strings.fields.target_department'))
            ->placeholder(__('resources/ths/strings.fields.target_department_default'))
            ->icon('heroicon-o-building-office-2');
    }

    public static function ticketId(): TextEntry
    {
        return TextEntry::make('ticket_id')
            ->label(__('resources/ths/strings.fields.ticket_id'))
            ->getStateUsing(fn($record) => TicketTablePresenter::formatTicketId($record))
            ->weight(FontWeight::Bold)
            ->copyable();
    }

    public static function updatedAt(): TextEntry
    {
        return TextEntry::make('updated_at')
            ->label(__('resources/ths/strings.fields.updated_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->color('gray')
            ->icon('heroicon-o-arrow-path');
    }
}
