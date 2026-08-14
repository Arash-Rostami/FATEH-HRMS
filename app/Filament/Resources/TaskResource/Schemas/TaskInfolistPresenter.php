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
            ->columnSpanFull();
    }

    public static function actionSourceDomain(): TextEntry
    {
        return TextEntry::make('detail.action_source_domain')
            ->label(__('resources/task/strings.fields.action_source_domain'))
            ->placeholder('—')
            ->columnSpanFull();
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
            ->formatStateUsing(fn($state, $record) => $record->adminDateLabel('deadline', null))
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
            ->columnSpanFull();
    }

    public static function project(): TextEntry
    {
        return TextEntry::make('detail.project')
            ->label(__('resources/task/strings.fields.project'))
            ->placeholder('—');
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
