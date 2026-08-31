<?php

namespace App\Filament\Resources\TaskResource\Schemas;

use Closure;
use App\Filament\Resources\TaskResource\Enums\TaskPriority;
use App\Filament\Resources\TaskResource\Enums\TaskState;
use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Models\Department;
use App\Models\User;
use App\Rules\ProjectDeadlineCap;
use App\Rules\TaskLabelLength;
use App\Services\PersianDateFieldService;
use App\Traits\FilamentFormDivider;
use App\Traits\StoresAttachedFiles;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class TaskFormPresenter
{
    use FilamentFormDivider, StoresAttachedFiles;

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
                        $meta = static::storeAttachment($file, 'task/attachments', fn($f) => self::fileName($f));

                        $set('name', $meta['name']);
                        $set('mime', $meta['mime']);
                        $set('size', $meta['size']);
                        return $meta['path'];
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

    public static function checklist(): Repeater
    {
        return Repeater::make('checklist')
            ->label(__('resources/task/strings.fields.checklist'))
            ->schema([
                TextInput::make('text')
                    ->label(__('resources/task/strings.fields.checklist_item'))
                    ->maxLength(255)
                    ->nullable(),
                Toggle::make('done')
                    ->label(__('resources/task/strings.fields.checklist_done')),
                TextInput::make('weight')
                    ->label(__('resources/task/strings.fields.checklist_weight'))
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->default(0)
                    ->nullable(),
            ])
            ->default([])
            ->nullable()
            ->helperText(__('resources/task/strings.hints.checklist') . ' ' . __('resources/task/strings.hints.checklist_weight'))
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
            carrierRule: fn(Get $get, $record) => new ProjectDeadlineCap($get('project_id') ?: $record?->project_id),
        )->columnSpanFull()
            ->helperText(__('resources/task/strings.hints.deadline_date'));
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

    public static function projectId(): Select
    {
        return Select::make('project_id')
            ->label(__('resources/task/strings.fields.project_id'))
            ->relationship('project', 'name')
            ->searchable()
            ->preload()
            ->nullable()
            ->helperText(__('resources/task/strings.hints.project_id'));
    }

    public static function labels(): TagsInput
    {
        return TagsInput::make('labels')
            ->label(__('resources/task/strings.fields.labels'))
            ->rules(['array', 'max:10', new TaskLabelLength()])
            ->nullable()
            ->helperText(__('resources/task/strings.hints.labels'));
    }

    public static function meta(): KeyValue
    {
        return KeyValue::make('meta')
            ->label(__('resources/task/strings.fields.meta'))
            ->keyLabel(__('resources/task/strings.fields.meta_key'))
            ->valueLabel(__('resources/task/strings.fields.meta_value'))
            ->rule(self::metaKeyPattern())
            ->helperText(__('resources/task/strings.hints.meta'))
            ->columnSpanFull();
    }

    public static function metaKeyPattern(): Closure
    {
        return fn() => function (string $attribute, mixed $value, Closure $fail) {
            foreach (array_keys($value ?? []) as $key) {
                if (!preg_match('/^[a-z0-9_]+$/', (string) $key)) {
                    $fail(__('resources/task/strings.validation.meta_key'));
                }
            }
        };
    }

    public static function priority(): Select
    {
        return Select::make('priority')
            ->label(__('resources/task/strings.fields.priority'))
            ->options(TaskPriority::class)
            ->nullable()
            ->helperText(__('resources/task/strings.hints.priority'));
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
            ->options(fn(Get $get) => Department::getCachedModels()->get($get('department_id'))?->sectionsOptions() ?? [])
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
            ->rule(self::doneRequiresState())
            ->helperText(__('resources/task/strings.hints.status'));
    }

    public static function doneRequiresState(): Closure
    {
        return fn(Get $get, $record) => function (string $attribute, mixed $value, Closure $fail) use ($get, $record) {
            if ($value !== TaskStatus::Done->value) {
                return;
            }

            if (!empty($get('detail.state'))) {
                return;
            }

            if ($record && $record->status === TaskStatus::Done->value) {
                return;
            }

            $fail(__('resources/task/strings.validation.done_requires_state'));
        };
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
            ->options(fn(Get $get) => Department::getCachedModels()->get($get('department_id'))?->unitsOptions() ?? [])
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
