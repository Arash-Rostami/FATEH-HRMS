<?php

namespace App\Filament\Resources\TaskResource\Schemas;

use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Services\PersianDateFieldService;
use App\Traits\FilamentFormDivider;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\FusedGroup;
use Illuminate\Support\Carbon;

class TaskFormPresenter
{
    use FilamentFormDivider;
    public static function assignedTo(): Select
    {
        return Select::make('assigned_to')
            ->label(__('resources/task/strings.fields.assignee'))
            ->helperText(__('resources/task/strings.fields.assignee_hint'))
            ->relationship('assignee', 'name')
            ->searchable()
            ->preload()
            ->nullable();
    }

    public static function deadlineDate(): FusedGroup
    {
        return PersianDateFieldService::make(
            prefix: 'deadline_date',
            label: __('resources/task/strings.fields.deadline_date'),
            required: false,
            yearFrom: 1400,
            fullWidth: false,
        )->columnSpanFull()
            ->helperText(__('resources/task/strings.hints.deadline_date'));
    }

    public static function deadlineTime(): TextInput
    {
        return TextInput::make('deadline_time')
            ->label(__('resources/task/strings.fields.deadline_time'))
            ->type('time')
            ->default('17:00')
            ->afterStateHydrated(function (TextInput $component, $record) {
                if (!$record?->deadline) return;
                $component->state(Carbon::parse($record->deadline)->format('H:i'));
            })
            ->nullable()
            ->helperText(__('resources/task/strings.hints.deadline_time'))
            ->validationMessages(['date_format' => __('resources/task/strings.validation.deadline_time.invalid')])
            ->columnSpanFull();
    }

    public static function description(): Textarea
    {
        return Textarea::make('description')
            ->label(__('resources/task/strings.fields.description'))
            ->rows(3)
            ->maxLength(5000)
            ->nullable()
            ->helperText(__('resources/task/strings.hints.description'))
            ->validationMessages(['max' => __('resources/task/strings.validation.description.max_length')])
            ->columnSpanFull();
    }

    public static function status(): Select
    {
        return Select::make('status')
            ->label(__('resources/task/strings.fields.status'))
            ->options(TaskStatus::class)
            ->required()
            ->default(TaskStatus::Todo->value)
            ->helperText(__('resources/task/strings.hints.status'))
            ->validationMessages([
                'required' => __('resources/task/strings.validation.status.required'),
            ]);
    }

    public static function title(): TextInput
    {
        return TextInput::make('title')
            ->label(__('resources/task/strings.fields.title'))
            ->required()
            ->maxLength(255)
            ->helperText(__('resources/task/strings.hints.title'))
            ->validationMessages([
                'required' => __('resources/task/strings.validation.title.required'),
                'max' => __('resources/task/strings.validation.title.max_length'),
            ]);
    }

    public static function userId(): Select
    {
        return Select::make('user_id')
            ->label(__('resources/task/strings.fields.creator'))
            ->relationship('creator', 'name')
            ->searchable()
            ->preload()
            ->helperText(__('resources/task/strings.hints.user_id'))
            ->required()
            ->validationMessages([
                'required' => __('resources/task/strings.validation.user_id.required'),
            ]);
    }
}
