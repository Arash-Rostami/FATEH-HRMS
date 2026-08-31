<?php

namespace App\Filament\Resources\TaskResource\Schemas;

use App\Filament\Resources\TaskResource\Enums\TaskState;
use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\TextSize;
use Illuminate\Database\Eloquent\Model;

class TaskInfolistPresenter
{
    public static function archivedAt(): TextEntry
    {
        return TextEntry::make('archived_at')
            ->label(__('resources/task/strings.fields.archived_at'))
            ->formatStateUsing(fn($state, $record) => $record->adminDateLabel('archived_at', null))
            ->placeholder('—')
            ->color('gray')
            ->icon('heroicon-o-archive-box')
            ->hidden(fn($record) => !$record->archived_at);
    }

    public static function actionSource(): TextEntry
    {
        return TextEntry::make('detail.action_source')
            ->label(__('resources/task/strings.fields.action_source'))
            ->placeholder('—')
            ->extraAttributes(['style' => 'white-space: pre-wrap;'])
            ->columnSpanFull();
    }

    public static function actionSourceDomain(): TextEntry
    {
        return TextEntry::make('detail.action_source_domain')
            ->label(__('resources/task/strings.fields.action_source_domain'))
            ->placeholder('—')
            ->extraAttributes(['style' => 'white-space: pre-wrap;'])
            ->columnSpanFull();
    }

    public static function approval(): TextEntry
    {
        return TextEntry::make('approval_status')
            ->label(__('resources/task/strings.fields.approval_status'))
            ->getStateUsing(fn($record) => $record->isPendingApproval()
                ? 'منتظر تأیید مدیر پروژه'
                : ($record->approved_at
                    ? toJalali($record->approved_at, 'Y/m/d H:i') . ' — ' . ($record->approvedBy?->name ?? '—')
                    : 'بدون نیاز به تأیید'))
            ->color(fn($record) => $record->isPendingApproval()
                ? 'warning'
                : ($record->approved_at ? 'success' : 'gray'))
            ->badge()
            ->icon('heroicon-o-check-badge')
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->visible(fn($record) => $record->status === TaskStatus::Done->value);
    }

    public static function assignee(): TextEntry
    {
        return TextEntry::make('assignee.name')
            ->label(__('resources/task/strings.fields.assignee'))
            ->placeholder(fn (?Model $record): ?string => $record?->creator?->name)
            ->icon('heroicon-o-user-plus');
    }

    public static function attachments(): RepeatableEntry
    {
        return RepeatableEntry::make('detail.attachments')
            ->label(__('resources/task/strings.fields.attachments'))
            ->schema([
                TextEntry::make('path')
                    ->hiddenLabel()
                    ->formatStateUsing(fn($state) => __('resources/task/strings.fields.view_file'))
                    ->url(fn($state) => $state ? asset('storage/' . $state) : null)
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->placeholder('—'),
            ])
            ->columnSpanFull();
    }

    public static function collaborators(): TextEntry
    {
        return TextEntry::make('detail.collaborators')
            ->label(__('resources/task/strings.fields.collaborators'))
            ->getStateUsing(fn($record) => $record->detail?->collaboratorNames() ?? [])
            ->badge()
            ->placeholder('—');
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/task/strings.fields.created_at'))
            ->formatStateUsing(fn($state, $record) => $record->createdLabel())
            ->color('gray')
            ->icon('heroicon-o-clock');
    }

    public static function creator(): TextEntry
    {
        return TextEntry::make('creator.name')
            ->label(__('resources/task/strings.fields.creator'))
            ->placeholder('—')
            ->icon('heroicon-o-user');
    }

    public static function deadline(): TextEntry
    {
        return TextEntry::make('deadline')
            ->label(__('resources/task/strings.fields.deadline'))
            ->formatStateUsing(fn($state, $record) => $record->deadline ? toJalali($record->deadline, 'Y/m/d') : null)
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->placeholder('—')
            ->color(fn($record) => match (true) {
                !$record->deadline => 'gray',
                $record->status === TaskStatus::Done->value => 'success',
                $record->status === TaskStatus::Pending->value => 'danger',
                $record->deadline->isPast() => 'danger',
                $record->deadline->diffInDays(now()) <= 2 => 'warning',
                default => 'primary',
            })
            ->icon('heroicon-o-calendar');
    }

    public static function delegatedIcon(): IconEntry
    {
        return IconEntry::make('delegated')
            ->label(__('resources/task/strings.fields.delegated'))
            ->getStateUsing(fn($record) => filled($record->assigned_to) && $record->assigned_to !== $record->user_id)
            ->boolean()
            ->trueIcon('heroicon-o-arrow-right-on-rectangle')
            ->falseIcon('heroicon-o-minus')
            ->trueColor('info')
            ->falseColor('gray');
    }

    public static function deletedAt(): TextEntry
    {
        return TextEntry::make('deleted_at')
            ->label(__('resources/task/strings.fields.deleted_at'))
            ->formatStateUsing(fn($state, $record) => $record->adminDateLabel('deleted_at', null))
            ->placeholder('—')
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->hidden(fn($record) => !$record->deleted_at);
    }

    public static function meta(): TextEntry
    {
        return TextEntry::make('detail.meta')
            ->label(__('resources/task/strings.fields.meta'))
            ->getStateUsing(function ($record) {
                $schema = $record->project?->setting('custom_schema') ?? [];

                return collect($record->detail?->meta ?? [])
                    ->filter(fn($value) => is_scalar($value) && $value !== '')
                    ->map(fn($value, $key) => ($schema[$key]['label'] ?? $key) . ': ' . $value)
                    ->values();
            })
            ->badge()
            ->placeholder('—')
            ->columnSpanFull();
    }

    public static function prunableWarning(): TextEntry
    {
        return TextEntry::make('prune_info')
            ->label(__('resources/task/strings.fields.prune_status'))
            ->getStateUsing(fn($record) => $record->pruneStatusText())
            ->color(fn($record) => $record->pruneStatusColor())
            ->badge()
            ->hidden(fn($record) => !$record->deleted_at);
    }

    public static function department(): TextEntry
    {
        return TextEntry::make('detail.department.name')
            ->label(__('resources/task/strings.fields.department'))
            ->placeholder('—')
            ->formatStateUsing(fn(?Model $record): string => $record?->detail?->department?->displayLabel() ?? '-')
            ->tooltip(fn(?Model $record): string => $record?->detail?->department?->tooltipLabel() ?? '-')
            ->icon('heroicon-o-building-office-2');
    }

    public static function description(): TextEntry
    {
        return TextEntry::make('description')
            ->label(__('resources/task/strings.fields.description'))
            ->placeholder('—')
            ->extraAttributes(['dir' => 'auto', 'style' => 'white-space: pre-wrap; unicode-bidi: isolate;'])
            ->columnSpanFull();
    }

    public static function project(): TextEntry
    {
        return TextEntry::make('detail.project')
            ->label(__('resources/task/strings.fields.project'))
            ->placeholder('—');
    }

    public static function linkedProject(): TextEntry
    {
        return TextEntry::make('project.name')
            ->label(__('resources/task/strings.fields.project_id'))
            ->placeholder('—')
            ->icon('heroicon-o-rectangle-stack');
    }

    public static function lastTouchedBy(): TextEntry
    {
        return TextEntry::make('last_touched')
            ->label(__('resources/task/strings.fields.last_touched_by'))
            ->getStateUsing(function ($record) {
                $touch = $record->replies()
                    ->whereNotNull('user_id')
                    ->latest('id')
                    ->first(['user_id', 'created_at']);

                return $touch
                    ? ($touch->user?->name ?? '—') . ' · ' . toJalaliRelative($touch->created_at)
                    : null;
            })
            ->placeholder('—')
            ->color('gray')
            ->icon('heroicon-o-finger-print');
    }

    public static function labels(): TextEntry
    {
        return TextEntry::make('labels')
            ->label(__('resources/task/strings.fields.labels'))
            ->badge()
            ->placeholder('—');
    }

    public static function priority(): TextEntry
    {
        return TextEntry::make('priority')
            ->label(__('resources/task/strings.fields.priority'))
            ->getStateUsing(fn($record) => $record->priority)
            ->badge()
            ->placeholder('—');
    }

    public static function checklistCompletion(): TextEntry
    {
        return TextEntry::make('detail.checklist')
            ->label(__('resources/task/strings.fields.checklist_completion'))
            ->formatStateUsing(function ($state, $record) {
                $items = $state ?? [];
                $total = count($items);
                $done = count(array_filter($items, fn($item) => $item['done'] ?? false));
                $percent = $record->progress_percent ?? 0;

                return $total > 0 ? "{$done} از {$total} ({$percent}٪)" : '—';
            });
    }

    public static function activityStream(): RepeatableEntry
    {
        return RepeatableEntry::make('replies')
            ->hiddenLabel()
            ->schema([
                TextEntry::make('user.name')
                    ->hiddenLabel()
                    ->placeholder('سیستم')
                    ->weight('bold'),
                TextEntry::make('body')
                    ->hiddenLabel()
                    ->formatStateUsing(fn($state, $record) => $state ?: app(\App\Services\ProjectTask\ActivityLogger::class)->render($record)['body'])
                    ->extraAttributes(['style' => 'white-space: pre-wrap;'])
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->hiddenLabel()
                    ->dateTime(),
            ])
            ->columns(3)
            ->columnSpanFull();
    }

    public static function responsibleUser(): TextEntry
    {
        return TextEntry::make('detail.responsibleUser.name')
            ->label(__('resources/task/strings.fields.responsible_user'))
            ->placeholder('—')
            ->icon('heroicon-o-user');
    }

    public static function scheme(): TextEntry
    {
        return TextEntry::make('detail.scheme')
            ->label(__('resources/task/strings.fields.scheme'))
            ->placeholder('—');
    }

    public static function section(): TextEntry
    {
        return TextEntry::make('detail.section')
            ->label(__('resources/task/strings.fields.section'))
            ->placeholder('—');
    }

    public static function state(): TextEntry
    {
        return TextEntry::make('detail.state')
            ->label(__('resources/task/strings.fields.state'))
            ->getStateUsing(fn($record) => TaskState::tryFrom($record->detail?->state ?? ''))
            ->badge()
            ->placeholder('—');
    }

    public static function status(): TextEntry
    {
        return TextEntry::make('status')
            ->label(__('resources/task/strings.fields.status'))
            ->getStateUsing(fn($record) => TaskStatus::tryFrom($record->status))
            ->badge();
    }

    public static function title(): TextEntry
    {
        return TextEntry::make('title')
            ->label(__('resources/task/strings.fields.title'))
            ->size(TextSize::Large)
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->columnSpanFull();
    }

    public static function unit(): TextEntry
    {
        return TextEntry::make('detail.unit')
            ->label(__('resources/task/strings.fields.unit'))
            ->placeholder('—');
    }

    public static function updatedAt(): TextEntry
    {
        return TextEntry::make('updated_at')
            ->label(__('resources/task/strings.fields.updated_at'))
            ->formatStateUsing(fn($state, $record) => $record->updatedLabel())
            ->color('gray')
            ->icon('heroicon-o-arrow-path');
    }
}
