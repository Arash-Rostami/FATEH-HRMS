<?php

namespace App\Filament\Resources\TaskResource\Exports;

use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Models\Task;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class TaskExporter extends Exporter
{
    protected static ?string $model = Task::class;

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['creator', 'assignee']);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('title')->label(__('resources/task/strings.fields.title')),
            ExportColumn::make('description')->label(__('resources/task/strings.fields.description')),
            ExportColumn::make('status')
                ->label(__('resources/task/strings.fields.status'))
                ->state(fn($record) => TaskStatus::tryFrom($record->status)?->getLabel() ?? $record->status),
            ExportColumn::make('creator.name')->label(__('resources/task/strings.fields.creator')),
            ExportColumn::make('assignee.name')->label(__('resources/task/strings.fields.assignee')),
            ExportColumn::make('deadline')
                ->label(__('resources/task/strings.fields.deadline'))
                ->state(fn($record) => $record->deadline ? toJalaliSmart($record->deadline) : '-'),
            ExportColumn::make('created_at')->label(__('resources/task/strings.fields.created_at'))
                ->formatStateUsing(fn($state) => $state ? toJalaliSmart($state) : '—'),
            ExportColumn::make('deleted_at')->label(__('resources/task/strings.fields.deleted_at'))
                ->formatStateUsing(fn($state) => $state ? toJalaliSmart($state) : '—'),
            ExportColumn::make('archived_at')->label(__('resources/task/strings.fields.archived_at'))
                ->formatStateUsing(fn($state) => $state ? toJalaliSmart($state) : '—'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);

        return __('resources/task/strings.export.completed', ['count' => $count]);
    }
}
