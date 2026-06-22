<?php

namespace App\Filament\Resources\TaskResource\Schemas;

use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;

class TaskTablePresenter
{
    public static function assignee(): TextColumn
    {
        return TextColumn::make('assignee.name')
            ->label(__('resources/task/strings.fields.assignee'))
            ->searchable()
            ->placeholder(__('resources/task/strings.fields.self_assigned'))
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function assigneeFilter(): SelectFilter
    {
        return SelectFilter::make('assigned_to')
            ->label(__('resources/task/strings.fields.assignee'))
            ->relationship('assignee', 'name')
            ->searchable()
            ->preload();
    }

    public static function assigneeGroup(): Group
    {
        return Group::make('assignee.name')
            ->label(__('resources/task/strings.fields.assignee'))
            ->getTitleFromRecordUsing(
                fn($record): string => $record?->assignee?->name ?: '—'
            )
            ->getKeyFromRecordUsing(
                fn($record): string => (string)($record?->assignee_id ?? 'unassigned')
            )
            ->titlePrefixedWithLabel(false)
            ->collapsible();
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/task/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function creator(): TextColumn
    {
        return TextColumn::make('creator.name')
            ->label(__('resources/task/strings.fields.creator'))
            ->searchable()
            ->sortable()
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function creatorFilter(): SelectFilter
    {
        return SelectFilter::make('user_id')
            ->label(__('resources/task/strings.fields.creator'))
            ->relationship('creator', 'name')
            ->searchable()
            ->preload();
    }

    public static function creatorGroup(): Group
    {
        return Group::make('creator.name')
            ->label(__('resources/task/strings.fields.creator'))
            ->getTitleFromRecordUsing(
                fn($record): string => $record?->creator?->name ?: '—'
            )
            ->getKeyFromRecordUsing(
                fn($record): string => (string)($record?->creator_id ?? 'unknown')
            )
            ->titlePrefixedWithLabel(false)
            ->collapsible();
    }

    public static function deadline(): TextColumn
    {
        return TextColumn::make('deadline')
            ->label(__('resources/task/strings.fields.deadline'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignCenter()
            ->color(fn($record) => match (true) {
                !$record->deadline => 'gray',
                $record->status === TaskStatus::Done->value => 'success',
                $record->status === TaskStatus::Pending->value => 'danger',
                $record->deadline->isPast() => 'danger',
                $record->deadline->diffInDays(now()) <= 2 => 'warning',
                default => 'primary',
            })
            ->sortable()
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function delegatedFilter(): TernaryFilter
    {
        return TernaryFilter::make('delegated')
            ->label(__('resources/task/strings.fields.delegated'))
            ->trueLabel(__('resources/task/strings.filters.delegated'))
            ->falseLabel(__('resources/task/strings.filters.not_delegated'))
            ->queries(
                true: fn(Builder $query) => $query->whereNotNull('assigned_to')
                    ->whereColumn('assigned_to', '!=', 'user_id'),
                false: fn(Builder $query) => $query->where(fn($q) => $q
                    ->whereNull('assigned_to')
                    ->orWhereColumn('assigned_to', 'user_id')
                ),
            );
    }

    public static function deletedAt(): TextColumn
    {
        return TextColumn::make('deleted_at')
            ->label(__('resources/task/strings.fields.deleted_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->placeholder('—')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function description(): TextColumn
    {
        return TextColumn::make('description')
            ->label(__('resources/task/strings.fields.description'))
            ->limit(60)
            ->wrap()
            ->tooltip(fn($state) => strlen($state ?? '') > 60 ? $state : null)
            ->searchable()
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label(__('resources/task/strings.fields.id'))
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function isDelegated(): IconColumn
    {
        return IconColumn::make('is_delegated')
            ->label(__('resources/task/strings.fields.delegated'))
            ->getStateUsing(fn($record) => filled($record->assigned_to) && $record->assigned_to !== $record->user_id)
            ->boolean()
            ->trueIcon('heroicon-o-arrow-right-on-rectangle')
            ->falseIcon('heroicon-o-minus')
            ->trueColor('info')
            ->falseColor('gray')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function overdueFilter(): Filter
    {
        return Filter::make('overdue')
            ->label(__('resources/task/strings.filters.overdue'))
            ->query(fn(Builder $query) => $query
                ->whereNotNull('deadline')
                ->where('deadline', '<', now())
                ->where('status', '!=', TaskStatus::Done->value)
            )
            ->toggle();
    }

    public static function status(): TextColumn
    {
        return TextColumn::make('status')
            ->label(__('resources/task/strings.fields.status'))
            ->getStateUsing(fn($record) => TaskStatus::tryFrom($record->status))
            ->badge()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function statusFilter(): SelectFilter
    {
        return SelectFilter::make('status')
            ->label(__('resources/task/strings.fields.status'))
            ->options(TaskStatus::class);
    }

    public static function statusGroup(): Group
    {
        return Group::make('status')
            ->label(__('resources/task/strings.fields.status'))
            ->getTitleFromRecordUsing(
                fn($record): string => TaskStatus::tryFrom($record?->status)?->getLabel()
                    ?? (string)$record?->status
            )
            ->getKeyFromRecordUsing(
                fn($record): string => (string)$record?->status
            )
            ->titlePrefixedWithLabel(false)
            ->collapsible();
    }

    public static function title(): TextColumn
    {
        return TextColumn::make('title')
            ->label(__('resources/task/strings.fields.title'))
            ->searchable()
            ->sortable()
            ->limit(60)
            ->tooltip(fn($state) => strlen($state ?? '') > 60 ? $state : null)
            ->toggleable(isToggledHiddenByDefault: false);
    }
}
