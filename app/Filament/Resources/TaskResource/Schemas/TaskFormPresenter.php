<?php

namespace App\Filament\Resources\TaskResource\Schemas;

use App\Filament\Resources\TaskResource\Enums\TaskState;
use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Models\Department;
use App\Models\User;
use App\Services\PersianDateFieldService;
use App\Traits\FilamentFormDivider;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class TaskFormPresenter
{
    use FilamentFormDivider;

    public static function actionSource(): Textarea
    {
        return Textarea::make('action_source')
            ->label(__('resources/task/strings.fields.action_source'))
            ->rows(2)
            ->maxLength(2000)
            ->nullable()
            ->helperText(__('resources/task/strings.hints.action_source'));
    }

    public static function actionSourceDomain(): Textarea
    {
        return Textarea::make('action_source_domain')
            ->label(__('resources/task/strings.fields.action_source_domain'))
            ->rows(2)
            ->maxLength(2000)
            ->nullable()
            ->helperText(__('resources/task/strings.hints.action_source_domain'));
    }

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

    public static function attachments(): Repeater
    {
        return Repeater::make('attachments')
            ->label(__('resources/task/strings.fields.attachments'))
            ->schema([
                FileUpload::make('path')
                    ->label(__('resources/task/strings.fields.file'))
                    ->disk('public')
                    ->directory('task/attachments')
                    ->maxSize(4096)
                    ->acceptedFileTypes(self::acceptedMimeTypes())
                    ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, callable $set) {
                        $path = $file->storeAs('task/attachments', self::fileName($file), 'public');
                        $set('name', $file->getClientOriginalName());
                        $set('mime', $file->getMimeType());
                        $set('size', $file->getSize());
                        return $path;
                    })
                    ->openable()
                    ->downloadable(),
                Hidden::make('name'),
                Hidden::make('mime'),
                Hidden::make('size'),
            ])
            ->maxItems(5)
            ->helperText(__('resources/task/strings.hints.attachments'))
            ->columnSpanFull();
    }

    public static function collaborators(): Select
    {
        return Select::make('collaborators')
            ->label(__('resources/task/strings.fields.collaborators'))
            ->multiple()
            ->options(fn() => User::getCachedActiveOptions())
            ->searchable()
            ->preload()
            ->helperText(__('resources/task/strings.hints.collaborators'));
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
            ->columnSpanFull();
    }

    public static function departmentId(): Select
    {
        return Select::make('department_id')
            ->label(__('resources/task/strings.fields.department'))
            ->options(fn() => Department::getCachedOptions()->toArray())
            ->searchable()
            ->live()
            ->afterStateUpdated(function (callable $set) {
                $set('unit', null);
                $set('section', null);
            })
            ->helperText(__('resources/task/strings.hints.department'));
    }

    public static function description(): Textarea
    {
        return Textarea::make('description')
            ->label(__('resources/task/strings.fields.description'))
            ->rows(3)
            ->maxLength(5000)
            ->nullable()
            ->helperText(__('resources/task/strings.hints.description'))
            ->columnSpanFull();
    }

    public static function project(): TextInput
    {
        return TextInput::make('project')
            ->label(__('resources/task/strings.fields.project'))
            ->maxLength(255)
            ->nullable()
            ->helperText(__('resources/task/strings.hints.project'));
    }

    public static function responsibleUserId(): Select
    {
        return Select::make('responsible_user_id')
            ->label(__('resources/task/strings.fields.responsible_user'))
            ->relationship('responsibleUser', 'name')
            ->searchable()
            ->preload()
            ->nullable()
            ->helperText(__('resources/task/strings.hints.responsible_user'));
    }

    public static function scheme(): TextInput
    {
        return TextInput::make('scheme')
            ->label(__('resources/task/strings.fields.scheme'))
            ->maxLength(255)
            ->nullable()
            ->helperText(__('resources/task/strings.hints.scheme'));
    }

    public static function section(): Select
    {
        return Select::make('section')
            ->label(__('resources/task/strings.fields.section'))
            ->options(fn(Get $get) => Department::find($get('department_id'))?->sectionsOptions() ?? [])
            ->searchable()
            ->nullable()
            ->helperText(__('resources/task/strings.hints.section'));
    }

    public static function state(): Select
    {
        return Select::make('state')
            ->label(__('resources/task/strings.fields.state'))
            ->options(TaskState::class)
            ->nullable()
            ->helperText(__('resources/task/strings.hints.state'));
    }

    public static function status(): Select
    {
        return Select::make('status')
            ->label(__('resources/task/strings.fields.status'))
            ->options(TaskStatus::class)
            ->required()
            ->default(TaskStatus::Todo->value)
            ->helperText(__('resources/task/strings.hints.status'));
    }

    public static function title(): TextInput
    {
        return TextInput::make('title')
            ->label(__('resources/task/strings.fields.title'))
            ->required()
            ->maxLength(255)
            ->helperText(__('resources/task/strings.hints.title'));
    }

    public static function unit(): Select
    {
        return Select::make('unit')
            ->label(__('resources/task/strings.fields.unit'))
            ->options(fn(Get $get) => Department::find($get('department_id'))?->unitsOptions() ?? [])
            ->searchable()
            ->nullable()
            ->helperText(__('resources/task/strings.hints.unit'));
    }

    public static function userId(): Select
    {
        return Select::make('user_id')
            ->label(__('resources/task/strings.fields.creator'))
            ->relationship('creator', 'name')
            ->searchable()
            ->preload()
            ->helperText(__('resources/task/strings.hints.user_id'))
            ->required();
    }

    private static function acceptedMimeTypes(): array
    {
        return [
            'image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/webp', 'image/svg+xml',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
    }

    private static function fileName(TemporaryUploadedFile $file): string
    {
        return sprintf(
            'TASK-%s-%s.%s',
            now()->format('Ymd'),
            Str::random(10),
            $file->getClientOriginalExtension()
        );
    }
}
