<?php

namespace App\Filament\Resources\ThsResource\Schemas;

use App\Filament\Resources\ThsResource\Enums\{TicketPriority, TicketStatus};
use App\Models\Department;
use App\Models\Ticket;
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
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class TicketFormPresenter
{
    use FilamentFormDivider;

    public static function actionResult(): Textarea
    {
        return Textarea::make('action_result')
            ->label(__('resources/ths/strings.fields.action_result'))
            ->rows(3)
            ->maxLength(5000)
            ->nullable()
            ->helperText(__('resources/ths/strings.hints.action_result'))
            ->validationMessages(['max' => __('resources/ths/strings.validation.action_result.max_length')])
            ->columnSpanFull();
    }

    public static function additionalNotes(): Textarea
    {
        return Textarea::make('additional_notes')
            ->label(__('resources/ths/strings.fields.additional_notes'))
            ->rows(2)
            ->maxLength(2000)
            ->nullable()
            ->helperText(__('resources/ths/strings.hints.additional_notes'))
            ->validationMessages(['max' => __('resources/ths/strings.validation.additional_notes.max_length')])
            ->columnSpanFull();
    }

    public static function assignedTo(): Select
    {
        return Select::make('assigned_to')
            ->label(__('resources/ths/strings.fields.assignee'))
            ->relationship('assignee', 'name')
            ->searchable()
            ->preload()
            ->live()
            ->afterStateUpdated(function (?int $state, callable $set, callable $get) {
                $currentValue = $get('status')?->value;

                if (blank($state) && $currentValue === TicketStatus::InProgress->value) {
                    $set('status', TicketStatus::Open->value);
                }

                if (filled($state) && in_array($currentValue, [TicketStatus::Open->value, null])) {
                    $set('status', TicketStatus::InProgress->value);
                }
            })
            ->nullable()
            ->helperText(__('resources/ths/strings.hints.assigned_to'))
            ->validationMessages([
                'exists' => __('resources/ths/strings.validation.assigned_to.exists'),
                'in' => __('resources/ths/strings.validation.assigned_to.in')
            ]);
    }

    public static function assigneeFiles(): Repeater
    {
        return Repeater::make('assignee_files')
            ->label(__('resources/ths/strings.fields.assignee_files'))
            ->schema([
                FileUpload::make('file')
                    ->label(__('resources/ths/strings.fields.file'))
                    ->disk('public')
                    ->directory('ticket/assignee')
                    ->maxSize(4096)
                    ->acceptedFileTypes(self::acceptedMimeTypes())
                    ->getUploadedFileNameForStorageUsing(fn(TemporaryUploadedFile $file) => self::fileName($file))
                    ->validationMessages([
                        'max' => __('resources/ths/strings.validation.assignee_files.max_size'),
                        'mimetypes' => __('resources/ths/strings.validation.assignee_files.mime_types'),
                'mimes' => __('resources/ths/strings.validation.assignee_files.mimes')
            ])
                    ->openable()
                    ->downloadable(),
            ])
            ->maxItems(3)
            ->helperText(__('resources/ths/strings.hints.assignee_files'))
            ->validationMessages(['max' => __('resources/ths/strings.validation.assignee_files.max_items')])
            ->columnSpanFull();
    }

    public static function completionDate(): Hidden
    {
        return Hidden::make('completion_date')
            ->label(__('resources/ths/strings.fields.completion_date'));
    }

    public static function completionDeadlineDate(): FusedGroup
    {
        return PersianDateFieldService::make(
            prefix: 'completion_deadline_date',
            label: __('resources/ths/strings.fields.deadline_date'),
            required: false,
            yearFrom: 1400,
            fullWidth: false,
        )->columnSpan(2)
            ->helperText(__('resources/ths/strings.hints.completion_deadline_date'));
    }

    public static function completionDeadlineTime(): TextInput
    {
        return TextInput::make('completion_deadline_time')
            ->label(__('resources/ths/strings.fields.deadline_time'))
            ->type('time')
            ->default('09:00')
            ->nullable()
            ->helperText(__('resources/ths/strings.hints.completion_deadline_time'))
            ->validationMessages(['date_format' => __('resources/ths/strings.validation.completion_deadline_time.invalid')]);
    }

    public static function departmentDisplay(): TextInput
    {
        return TextInput::make('extra.department')
            ->label(__('resources/ths/strings.fields.department'))
            ->readOnly()
            ->dehydrated(true);
    }

    public static function description(): Textarea
    {
        return Textarea::make('description')
            ->label(__('resources/ths/strings.fields.description'))
            ->required()
            ->rows(4)
            ->helperText(__('resources/ths/strings.hints.description'))
            ->maxLength(5000)
            ->validationMessages([
                'required' => __('resources/ths/strings.validation.description.required'),
                'max' => __('resources/ths/strings.validation.description.max_length'),
            ])
            ->columnSpanFull();
    }

    public static function effectiveness(): Select
    {
        return Select::make('effectiveness')
            ->label(__('resources/ths/strings.fields.effectiveness'))
            ->options([
                '5' => '★★★★★ — بسیار مؤثر',
                '4' => '★★★★☆ — مؤثر',
                '3' => '★★★☆☆ — خنثی',
                '2' => '★★☆☆☆ — کم‌اثر',
                '1' => '★☆☆☆☆ — بی‌اثر',
            ])
            ->nullable()
            ->helperText(__('resources/ths/strings.hints.effectiveness'))
            ->validationMessages([
                'in' => __('resources/ths/strings.validation.effectiveness.in')
            ]);
    }

    public static function priority(): Select
    {
        return Select::make('priority')
            ->label(__('resources/ths/strings.fields.priority'))
            ->options(TicketPriority::class)
            ->required()
            ->default(TicketPriority::Low->value)
            ->disabledOn('edit')
            ->helperText(__('resources/ths/strings.hints.priority'))
            ->validationMessages(['required' => __('resources/ths/strings.validation.priority.required'),
                'in' => __('resources/ths/strings.validation.priority.in')
            ]);
    }

    public static function requestArea(): Select
    {
        return Select::make('request_area')
            ->label(__('resources/ths/strings.fields.request_area'))
            ->options(function (Get $get) {
                $type = $get('request_type');
                $dept = $get('extra.target_department');
                $key = $type instanceof \BackedEnum ? $type->value : (string)$type;

                return collect(Ticket::getCustomRequestAreaOptions($dept, $key))
                    ->filter(fn($label, $areaKey) => $areaKey !== '')
                    ->map(fn($label, $areaKey) => Ticket::getCustomAdminAreaLabelHtml($key, $areaKey, $dept))
                    ->toArray();
            })
            ->preload()
            ->native(false)
            ->allowHtml()
            ->required()
            ->live()
            ->helperText(__('resources/ths/strings.hints.request_area'))
            ->validationMessages([
                'required' => __('resources/ths/strings.validation.request_area.required'),
                'in' => __('resources/ths/strings.validation.request_area.in')
            ]);
    }

    public static function requestSubject(): TextInput
    {
        return TextInput::make('request_subject')
            ->label(__('resources/ths/strings.fields.subject'))
            ->required()
            ->maxLength(255)
            ->helperText(__('resources/ths/strings.hints.request_subject'))
            ->validationMessages([
                'required' => __('resources/ths/strings.validation.request_subject.required'),
                'max' => __('resources/ths/strings.validation.request_subject.max_length'),
            ])
            ->columnSpanFull();
    }

    public static function requestType(): Select
    {
        return Select::make('request_type')
            ->label(__('resources/ths/strings.fields.request_type'))
            ->options(fn(Get $get) => Ticket::getCustomRequestTypeOptions($get('extra.target_department')))
            ->required()
            ->live()
            ->native(false)
            ->afterStateUpdated(fn(callable $set) => $set('request_area', null))
            ->helperText(__('resources/ths/strings.hints.request_type'))
            ->validationMessages(['required' => __('resources/ths/strings.validation.request_type.required'),
                'in' => __('resources/ths/strings.validation.request_type.in')
            ]);
    }

    public static function requesterFiles(): Repeater
    {
        return Repeater::make('requester_files')
            ->label(__('resources/ths/strings.fields.requester_files'))
            ->schema([
                FileUpload::make('file')
                    ->label(__('resources/ths/strings.fields.file'))
                    ->disk('public')
                    ->directory('ticket/requester')
                    ->maxSize(4096)
                    ->acceptedFileTypes(self::acceptedMimeTypes())
                    ->getUploadedFileNameForStorageUsing(fn(TemporaryUploadedFile $file) => self::fileName($file))
                    ->validationMessages([
                        'max' => __('resources/ths/strings.validation.requester_files.max_size'),
                        'mimetypes' => __('resources/ths/strings.validation.requester_files.mime_types'),
                'mimes' => __('resources/ths/strings.validation.requester_files.mimes')
            ])
                    ->openable()
                    ->downloadable(),
            ])
            ->maxItems(3)
            ->helperText(__('resources/ths/strings.hints.requester_files'))
            ->validationMessages(['max' => __('resources/ths/strings.validation.requester_files.max_items')])
            ->disabledOn('edit')
            ->columnSpanFull();
    }

    public static function requesterId(): Select
    {
        return Select::make('requester_id')
            ->label(__('resources/ths/strings.fields.requester'))
            ->relationship('requester', 'name')
            ->searchable()
            ->preload()
            ->required()
            ->live()
            ->disabledOn('edit')
            ->helperText(__('resources/ths/strings.hints.requester_id'))
            ->afterStateUpdated(function (?int $state, callable $set) {
                $deptId = $state ? User::with('profile')->find($state)?->profile?->department_id : null;
                $set('extra.department', $deptId ?? '');
            })
            ->validationMessages(['required' => __('resources/ths/strings.validation.requester_id.required'),
                'exists' => __('resources/ths/strings.validation.requester_id.invalid'),
                'in' => __('resources/ths/strings.validation.requester_id.invalid')
            ]);
    }

    public static function satisfactionScore(): Select
    {
        return Select::make('satisfaction_score')
            ->label(__('resources/ths/strings.fields.satisfaction'))
            ->disabledOn('edit')
            ->options(['5' => '★★★★★', '4' => '★★★★', '3' => '★★★', '2' => '★★', '1' => '★'])
            ->nullable()
            ->helperText(__('resources/ths/strings.hints.satisfaction_score'))
            ->validationMessages([
                'in' => __('resources/ths/strings.validation.satisfaction_score.in')
            ]);
    }

    public static function status(): Select
    {
        return Select::make('status')
            ->label(__('resources/ths/strings.fields.status'))
            ->options(TicketStatus::class)
            ->required()
            ->default(TicketStatus::Open->value)
            ->helperText(function ($record) {
                $completionDate = data_get($record, 'completion_date');
                return filled($completionDate) ? (toJalali($completionDate, 'H:i Y/m/d ')) . '✅' : '';
            })
            ->live()
            ->afterStateUpdated(function (mixed $state, callable $set) {
                $set('completion_date', $state?->value === 'closed' ? now()->format('Y-m-d H:i:s') : null);
            })
            ->validationMessages(['required' => __('resources/ths/strings.validation.status.required'),
                'in' => __('resources/ths/strings.validation.status.in')
            ]);
    }

    public static function targetDepartment(): Select
    {
        return Select::make('extra.target_department')
            ->label(__('resources/ths/strings.fields.target_department'))
            ->options(fn() => Department::getCachedOptions()->toArray())
            ->searchable()
            ->live()
            ->afterStateUpdated(fn(callable $set) => $set('request_area', null));
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
            'application/vnd.oasis.opendocument.text',
            'application/vnd.oasis.opendocument.spreadsheet',
        ];
    }

    private static function fileName(TemporaryUploadedFile $file): string
    {
        return sprintf(
            'THS-%s-%s.%s',
            now()->format('Ymd'),
            Str::random(10),
            $file->getClientOriginalExtension()
        );
    }
}
