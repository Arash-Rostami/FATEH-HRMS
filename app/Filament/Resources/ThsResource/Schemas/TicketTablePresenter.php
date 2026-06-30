<?php

namespace App\Filament\Resources\ThsResource\Schemas;

use App\Livewire\Dashboard\Ths\Presentation\TicketPresenter;
use App\Models\Department;
use App\Filament\Resources\ThsResource\Enums\{RequestType, TicketPriority, TicketStatus};
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;

class TicketTablePresenter
{
    public static function assignee(): TextColumn
    {
        return TextColumn::make('assignee.name')
            ->label(__('resources/ths/strings.fields.assignee'))
            ->placeholder(__('resources/ths/strings.fields.unassigned'))
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function assigneeFilter(): SelectFilter
    {
        return SelectFilter::make('assigned_to')
            ->label(__('resources/ths/strings.fields.assignee'))
            ->relationship('assignee', 'name')
            ->searchable()
            ->preload();
    }

    public static function assigneeGroup(): Group
    {
        return Group::make('assignee.name')
            ->label(__('resources/ths/strings.fields.assignee'))
            ->getTitleFromRecordUsing(fn($record): string => $record?->assignee?->name ?: __('—'))
            ->getKeyFromRecordUsing(fn($record): string => (string)($record?->assignee_id ?? 'unassigned'))
            ->titlePrefixedWithLabel(false)
            ->collapsible();
    }

    public static function completionDate(): TextColumn
    {
        return TextColumn::make('completion_date')
            ->label(__('resources/ths/strings.fields.completion_date'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->color(fn($record) => $record->completion_date ? 'success' : 'gray')
            ->sortable()
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function completionDeadline(): TextColumn
    {
        return TextColumn::make('completion_deadline')
            ->label(__('resources/ths/strings.fields.deadline'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->tooltip(fn($record) => self::deadlineDelta($record))
            ->color(fn($record) => self::deadlineColor($record))
            ->alignRight()
            ->sortable()
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/ths/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->tooltip(fn($record) => __('resources/ths/strings.fields.updated_at') . ': ' . toJalali($record->updated_at, 'Y/m/d H:i'))
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function deadlineColor($record): string
    {
        if (!$record->completion_deadline) return 'gray';
        if ($record->completion_date) {
            return $record->completion_date->gt($record->completion_deadline) ? 'danger' : 'success';
        }
        return now()->gt($record->completion_deadline) ? 'danger' : 'primary';
    }

    public static function deadlineDelta($record): ?string
    {
        if (!$record->completion_deadline) return null;

        if ($record->completion_date) {
            $diff = $record->completion_date->diff($record->completion_deadline);
            return $record->completion_date->gt($record->completion_deadline)
                ? "تأخیر: {$diff->days} روز و {$diff->h} ساعت"
                : "زودتر از موعد: {$diff->days} روز و {$diff->h} ساعت";
        }

        $diff = now()->diff($record->completion_deadline);
        return now()->gt($record->completion_deadline)
            ? "سررسید گذشته: {$diff->days} روز و {$diff->h} ساعت"
            : "تا سررسید: {$diff->days} روز و {$diff->h} ساعت";
    }

    public static function department(): TextColumn
    {
        return TextColumn::make('department.description')
            ->label(__('resources/ths/strings.fields.department'))
            ->placeholder('—')
            ->formatStateUsing(fn(?Model $record): string => $record?->department?->displayLabel() ?? '-')
            ->tooltip(fn(?Model $record): string => $record?->department?->tooltipLabel() ?? '-')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function targetDepartment(): TextColumn
    {
        return TextColumn::make('targetDepartment.description')
            ->label(__('resources/ths/strings.fields.target_department'))
            ->icon('heroicon-o-building-office')
            ->formatStateUsing(fn(?Model $record): string => $record?->targetDepartment?->displayLabel() ?? __('resources/ths/strings.fields.target_department_default'))
            ->tooltip(fn(?Model $record): string => $record?->targetDepartment?->tooltipLabel() ?? '-')
            ->placeholder(__('resources/ths/strings.fields.target_department_default'))
            ->toggleable(isToggledHiddenByDefault: false);
    }
    public static function effectiveness(): TextColumn
    {
        return TextColumn::make('effectiveness')
            ->label(__('resources/ths/strings.fields.effectiveness'))
            ->formatStateUsing(fn($state) => $state !== null ? str_repeat('✮', (int)$state) : '—')
            ->color(fn($record) => match (true) {
                $record->effectiveness === null => 'gray',
                (int)$record->effectiveness >= 4 => 'success',
                (int)$record->effectiveness >= 2 => 'warning',
                default => 'danger',
            })
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function formatTicketId(mixed $record): string
    {
        return (new TicketPresenter())->formatId($record?->toArray());
    }

    public static function overdueFilter(): Filter
    {
        return Filter::make('overdue')
            ->label(__('resources/ths/strings.filters.overdue'))
            ->query(fn(Builder $q) => $q
                ->whereNotNull('completion_deadline')
                ->where('completion_deadline', '<', now())
                ->where('status', '!=', 'closed')
            )
            ->toggle();
    }

    public static function priority(): TextColumn
    {
        return TextColumn::make('priority')
            ->label(__('resources/ths/strings.fields.priority'))
            ->getStateUsing(fn($record) => TicketPriority::tryFrom($record->priority))
            ->badge()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function priorityFilter(): SelectFilter
    {
        return SelectFilter::make('priority')
            ->label(__('resources/ths/strings.fields.priority'))
            ->options(TicketPriority::class);
    }

    public static function requestArea(): TextColumn
    {
        return TextColumn::make('request_area')
            ->label(__('resources/ths/strings.fields.request_area'))
            ->formatStateUsing(fn($record) => Ticket::$requestAreaOptions[$record->request_type][$record->request_area] ?? $record->request_area)
            ->searchable()
            ->icon(fn($record) => Ticket::getCustomHeroiconForArea($record->request_area, $record->extra['target_department'] ?? null))
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function requestType(): TextColumn
    {
        return TextColumn::make('request_type')
            ->label(__('resources/ths/strings.fields.request_type'))
            ->getStateUsing(fn($record) => RequestType::tryFrom($record->request_type))
            ->badge()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function requester(): TextColumn
    {
        return TextColumn::make('requester.name')
            ->label(__('resources/ths/strings.fields.requester'))
            ->searchable()
            ->sortable()
            ->tooltip(fn($record) => data_get($record, 'extra.department') ?: null)
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function requesterFilter(): SelectFilter
    {
        return SelectFilter::make('requester_id')
            ->label(__('resources/ths/strings.fields.requester'))
            ->relationship('requester', 'name')
            ->searchable()
            ->preload();
    }

    public static function satisfaction(): TextColumn
    {
        return TextColumn::make('satisfaction_score')
            ->label(__('resources/ths/strings.fields.satisfaction'))
            ->formatStateUsing(fn($state) => $state !== null ? str_repeat('✮', (int)round($state)) . ' (' . number_format($state, 1) . ')' : '—')
            ->tooltip(fn($record) => $record->extra['satisfaction_comment'] ?? null)
            ->color(fn($record) => match (true) {
                $record->satisfaction_score === null => 'gray',
                $record->satisfaction_score >= 4 => 'success',
                $record->satisfaction_score >= 2 => 'warning',
                default => 'danger',
            })
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function status(): TextColumn
    {
        return TextColumn::make('status')
            ->label(__('resources/ths/strings.fields.status'))
            ->getStateUsing(fn($record) => TicketStatus::tryFrom($record->status))
            ->badge()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function departmentFilter(): SelectFilter
    {
        return SelectFilter::make('target_department')
            ->label(__('resources/ths/strings.fields.target_department'))
            ->options(Department::getCachedOptions()->toArray())
            ->placeholder(__('resources/ths/strings.fields.target_department_default'))
            ->attribute('extra->target_department');
    }

    public static function statusGroup(): Group
    {
        return Group::make('status')
            ->label(__('resources/ths/strings.fields.status'))
            ->getTitleFromRecordUsing(
                fn($record): string => TicketStatus::tryFrom($record?->status)?->getLabel() ?? (string)$record?->status
            )
            ->titlePrefixedWithLabel(false)
            ->collapsible();
    }

    public static function subject(): TextColumn
    {
        return TextColumn::make('request_subject')
            ->label(__('resources/ths/strings.fields.subject'))
            ->limit(50)
            ->tooltip(fn($state) => $state)
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function ticketId(): TextColumn
    {
        return TextColumn::make('id')
            ->label(__('resources/ths/strings.fields.ticket_id'))
            ->formatStateUsing(fn($record) => self::formatTicketId($record))
            ->copyable()
            ->copyableState(fn($record) => $record->id)
            ->tooltip(__('resources/ths/strings.fields.click_to_copy'))
            ->sortable()
            ->searchable(['id', 'extra->target_department'])
            ->color(fn($record) => (
                $record->completion_deadline && $record->completion_date && $record->completion_date > $record->completion_deadline)
                ? 'danger' : 'gray'
            )
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function typeFilter(): SelectFilter
    {
        return SelectFilter::make('request_type')
            ->label(__('resources/ths/strings.fields.request_type'))
            ->options(RequestType::class);
    }

    public static function typeGroup(): Group
    {
        return Group::make('request_type')
            ->label(__('resources/ths/strings.fields.request_type'))
            ->getTitleFromRecordUsing(
                fn($record): string => RequestType::tryFrom($record?->request_type)?->getLabel()
                    ?? (string)$record?->request_type
            )
            ->titlePrefixedWithLabel(false)
            ->collapsible();
    }

    public static function unassignedFilter(): TernaryFilter
    {
        return TernaryFilter::make('unassigned')
            ->label(__('resources/ths/strings.filters.assigned'))
            ->trueLabel(__('resources/ths/strings.filters.assigned_yes'))
            ->falseLabel(__('resources/ths/strings.filters.assigned_no'))
            ->queries(
                true: fn(Builder $q) => $q->whereNotNull('assigned_to'),
                false: fn(Builder $q) => $q->whereNull('assigned_to'),
            );
    }
}
