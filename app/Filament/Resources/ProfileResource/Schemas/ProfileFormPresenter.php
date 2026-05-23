<?php

namespace App\Filament\Resources\ProfileResource\Schemas;

use App\Traits\FilamentFormDivider;
use App\Filament\Resources\ProfileResource\Enums\Degree;
use App\Filament\Resources\ProfileResource\Enums\EmploymentStatus;
use App\Filament\Resources\ProfileResource\Enums\EmploymentType;
use App\Filament\Resources\ProfileResource\Enums\Gender;
use App\Filament\Resources\ProfileResource\Enums\MaritalStatus;
use App\Filament\Resources\ProfileResource\Enums\Position;
use App\Filament\Resources\ProfileResource\Enums\WorkExperience;
use App\Models\Department;
use App\Services\PersianDateFieldService;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\FusedGroup;

class ProfileFormPresenter
{
    use FilamentFormDivider;

    public static function aboutMe(): Repeater
    {
        $core = ['bio', 'movies', 'music', 'hobbies', 'food', 'sports'];

        return Repeater::make('about_me')
            ->label(__('resources/profile/strings.form.about_me'))
            ->schema([
                TextInput::make('key')
                    ->label('کلید')
                    ->disabled(fn($get): bool => in_array($get('key'), $core))
                    ->dehydrated()
                    ->required()
                    ->validationMessages([
                        'required' => __('resources/profile/strings.validation.about_me_key.required'),
                    ]),
                TextInput::make('value')
                    ->label('مقدار'),
            ])
            ->afterStateHydrated(fn($component, $state) => $component->state(
                collect($state ?? [])
                    ->map(fn($v, $k) => ['key' => $k, 'value' => $v])
                    ->values()
                    ->all()
            )
            )
            ->saveRelationshipsUsing(function ($component, $record) {
                $record->update([
                    'about_me' => collect($component->getState() ?? [])
                        ->mapWithKeys(fn($item) => [$item['key'] => $item['value']])
                        ->all()
                ]);
            })
            ->grid(2)
            ->dehydrated(false)
            ->addable()
            ->deletable()
            ->reorderable(false)
            ->columnSpanFull()
            ->helperText(__('resources/profile/strings.hints.about_me'));
    }

    public static function accessibility(): Textarea
    {
        return Textarea::make('accessibility')
            ->label(__('resources/profile/strings.form.accessibility'))
            ->rows(2)
            ->columnSpanFull()
            ->helperText(__('resources/profile/strings.hints.accessibility'));
    }

    public static function address(): Textarea
    {
        return Textarea::make('address')
            ->label(__('resources/profile/strings.form.address'))
            ->rows(3)
            ->columnSpanFull()
            ->helperText(__('resources/profile/strings.hints.address'));
    }

    public static function attachments(): Repeater
    {
        return Repeater::make('attachments')
            ->label(__('resources/profile/strings.form.attachments'))
            ->defaultItems(1)
            ->schema([
                TextInput::make('key')
                    ->label('نام پیوست')
                    ->required()
                    ->maxLength(255)
                    ->validationMessages([
                        'required' => __('resources/profile/strings.validation.attachment_key.required'),
                        'max' => __('resources/profile/strings.validation.attachment_key.max'),
                    ]),

                Select::make('category')
                    ->label('نوع پیوست')
                    ->options([
                        'pdf' => 'PDF',
                        'image' => 'تصویر',
                        'doc' => 'سند',
                        'standard' => 'استاندارد',
                        'custom' => 'سفارشی'
                    ])
                    ->required()
                    ->native(false)
                    ->validationMessages([
                        'required' => __('resources/profile/strings.validation.attachment_category.required'),
                    ]),

                FileUpload::make('path')
                    ->label('فایل پیوست')
                    ->disk('public')
                    ->downloadable()
                    ->openable()
                    ->previewable()
                    ->acceptedFileTypes([
                        'application/pdf',
                        'image/*',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ])
                    ->directory('profiles/docs')
                    ->maxSize(5120)
                    ->required()
                    ->columnSpanFull()
                    ->validationMessages([
                        'required' => __('resources/profile/strings.validation.attachment_path.required'),
                        'max' => __('resources/profile/strings.validation.attachment_path.max'),
                    ]),
            ])
            ->columns(2)
            ->addable()
            ->deletable()
            ->reorderable(false)
            ->columnSpanFull()
            ->helperText(__('resources/profile/strings.hints.attachments'))
            ->dehydrateStateUsing(fn(?array $state): array => array_values(array_filter(
                $state ?? [],
                fn(array $item): bool => filled($item['name'] ?? null) ||
                    filled($item['type'] ?? null) ||
                    filled($item['path'] ?? null)
            )));
    }

    public static function birthdate(): FusedGroup
    {
        return PersianDateFieldService::make(
            'birthdate',
            __('resources/profile/strings.form.birthdate'),
            fullWidth: true,
        )->helperText(__('resources/profile/strings.hints.birthdate'));
    }

    public static function cellphone(): TextInput
    {
        return TextInput::make('cellphone')
            ->label(__('resources/profile/strings.form.cellphone'))
            ->tel()
            ->maxLength(20)
            ->helperText(__('resources/profile/strings.hints.cellphone'));
    }

    public static function degree(): Select
    {
        return Select::make('degree')
            ->label(__('resources/profile/strings.form.degree'))
            ->options(Degree::class)
            ->required()
            ->native(false)
            ->helperText(__('resources/profile/strings.hints.degree'))
            ->validationMessages([
                'required' => __('resources/profile/strings.validation.degree.required'),
            ]);
    }

    public static function departmentId(): Select
    {
        return Select::make('department_id')
            ->label(__('resources/profile/strings.form.department_id'))
            ->options(fn() => Department::getCachedOptions()->toArray())
            ->searchable()
            ->native(false)
            ->helperText(__('resources/profile/strings.hints.department_id'));
    }

    public static function emergencyPhone(): TextInput
    {
        return TextInput::make('emergency_phone')
            ->label(__('resources/profile/strings.form.emergency_phone'))
            ->tel()
            ->maxLength(20)
            ->helperText(__('resources/profile/strings.hints.emergency_phone'));
    }

    public static function emergencyRelationship(): TextInput
    {
        return TextInput::make('emergency_relationship')
            ->label(__('resources/profile/strings.form.emergency_relationship'))
            ->maxLength(100)
            ->helperText(__('resources/profile/strings.hints.emergency_relationship'));
    }

    public static function employmentStatus(): Select
    {
        return Select::make('employment_status')
            ->label(__('resources/profile/strings.form.employment_status'))
            ->options(EmploymentStatus::class)
            ->required()
            ->native(false)
            ->helperText(__('resources/profile/strings.hints.employment_status'))
            ->validationMessages([
                'required' => __('resources/profile/strings.validation.employment_status.required'),
            ]);
    }

    public static function employmentType(): Select
    {
        return Select::make('employment_type')
            ->label(__('resources/profile/strings.form.employment_type'))
            ->options(EmploymentType::class)
            ->required()
            ->native(false)
            ->helperText(__('resources/profile/strings.hints.employment_type'))
            ->validationMessages([
                'required' => __('resources/profile/strings.validation.employment_type.required'),
            ]);
    }

    public static function endDate(): FusedGroup
    {
        return PersianDateFieldService::make(
            'end_date',
            __('resources/profile/strings.form.end_date'),
            required: false,
            yearFrom: 1370,
        )->helperText(__('resources/profile/strings.hints.end_date'));
    }

    public static function favoriteColors(): Repeater
    {
        return Repeater::make('favorite_colors')
            ->label(__('resources/profile/strings.form.favorite_colors'))
            ->schema([
                ColorPicker::make('color')
                    ->label(__('resources/profile/strings.form.favorite_color_item'))
                    ->validationMessages([
                        'required' => __('resources/profile/strings.validation.favorite_color.required'),
                    ])
            ])
            ->grid(4)
            ->columns(1)
            ->addable()
            ->deletable()
            ->reorderable(false)
            ->columnSpanFull()
            ->helperText(__('resources/profile/strings.hints.favorite_colors'))
            ->afterStateHydrated(function (Repeater $component, mixed $state): void {
                $normalized = collect($state ?? [])
                    ->map(function ($item) {
                        if (is_string($item)) return ['color' => $item];
                        if (is_array($item)) return ['color' => $item['color'] ?? null];

                        return ['color' => null];
                    })
                    ->filter(fn($item) => filled($item['color']))
                    ->values()
                    ->all();

                $component->state($normalized);
            })
            ->dehydrateStateUsing(function ($state): array {
                return collect($state ?? [])
                    ->map(fn($item) => is_array($item) ? ($item['color'] ?? null) : $item)
                    ->filter()
                    ->values()
                    ->all();
            });
    }

    public static function field(): TextInput
    {
        return TextInput::make('field')
            ->label(__('resources/profile/strings.form.field'))
            ->maxLength(255)
            ->columnSpanFull()
            ->helperText(__('resources/profile/strings.hints.field'));
    }

    public static function gender(): Select
    {
        return Select::make('gender')
            ->label(__('resources/profile/strings.form.gender'))
            ->options(Gender::class)
            ->required()
            ->native(false)
            ->helperText(__('resources/profile/strings.hints.gender'))
            ->validationMessages([
                'required' => __('resources/profile/strings.validation.gender.required'),
            ]);
    }

    public static function idBookletNumber(): TextInput
    {
        return TextInput::make('id_booklet_number')
            ->label(__('resources/profile/strings.form.id_booklet_number'))
            ->unique(ignoreRecord: true)
            ->maxLength(20)
            ->helperText(__('resources/profile/strings.hints.id_booklet_number'))
            ->validationMessages([
                'unique' => __('resources/profile/strings.validation.id_booklet_number.unique'),
            ]);
    }

    public static function idCardNumber(): TextInput
    {
        return TextInput::make('id_card_number')
            ->label(__('resources/profile/strings.form.id_card_number'))
            ->unique(ignoreRecord: true)
            ->maxLength(20)
            ->helperText(__('resources/profile/strings.hints.id_card_number'))
            ->validationMessages([
                'unique' => __('resources/profile/strings.validation.id_card_number.unique'),
            ]);
    }

    public static function image(): FileUpload
    {
        return FileUpload::make('image')
            ->label(__('resources/profile/strings.form.image'))
            ->image()
            ->downloadable()
            ->openable()
            ->previewable()
            ->disk('public')
            ->directory('profiles/images')
            ->imagePreviewHeight('120')
            ->maxSize(2048)
            ->columnSpanFull()
            ->helperText(__('resources/profile/strings.hints.image'));
    }

    public static function insurance(): TextInput
    {
        return TextInput::make('insurance')
            ->label(__('resources/profile/strings.form.insurance'))
            ->maxLength(255)
            ->helperText(__('resources/profile/strings.hints.insurance'));
    }

    public static function interests(): Textarea
    {
        return Textarea::make('interests')
            ->label(__('resources/profile/strings.form.interests'))
            ->rows(3)
            ->columnSpanFull()
            ->helperText(__('resources/profile/strings.hints.interests'));
    }

    public static function landline(): TextInput
    {
        return TextInput::make('landline')
            ->label(__('resources/profile/strings.form.landline'))
            ->tel()
            ->maxLength(20)
            ->helperText(__('resources/profile/strings.hints.landline'));
    }

    public static function licensePlate(): TextInput
    {
        return TextInput::make('license_plate')
            ->label(__('resources/profile/strings.form.license_plate'))
            ->maxLength(20)
            ->helperText(__('resources/profile/strings.hints.license_plate'));
    }

    public static function maritalStatus(): Select
    {
        return Select::make('marital_status')
            ->label(__('resources/profile/strings.form.marital_status'))
            ->options(MaritalStatus::class)
            ->required()
            ->native(false)
            ->helperText(__('resources/profile/strings.hints.marital_status'))
            ->validationMessages([
                'required' => __('resources/profile/strings.validation.marital_status.required'),
            ]);
    }

    public static function numberOfChildren(): Select
    {
        return Select::make('number_of_children')
            ->label(__('resources/profile/strings.form.number_of_children'))
            ->options(collect(range(0, 10))->mapWithKeys(fn($n) => [$n => (string)$n]))
            ->default(0)
            ->native(false)
            ->helperText(__('resources/profile/strings.hints.number_of_children'));
    }

    public static function personnelId(): TextInput
    {
        return TextInput::make('personnel_id')
            ->label(__('resources/profile/strings.form.personnel_id'))
            ->unique(ignoreRecord: true)
            ->maxLength(50)
            ->helperText(__('resources/profile/strings.hints.personnel_id'))
            ->validationMessages([
                'unique' => __('resources/profile/strings.validation.personnel_id.unique'),
            ]);
    }

    public static function position(): Select
    {
        return Select::make('position')
            ->label(__('resources/profile/strings.form.position'))
            ->required()
            ->options(Position::class)
            ->default(Position::Employee->value)
            ->preload()
            ->helperText(__('resources/profile/strings.hints.position'))
            ->validationMessages([
                'required' => __('resources/profile/strings.validation.position.required'),
            ]);
    }

    public static function startDate(): FusedGroup
    {
        return PersianDateFieldService::make(
            'start_date',
            __('resources/profile/strings.form.start_date'),
            required: false,
            yearFrom: 1370,
        )->helperText(__('resources/profile/strings.hints.start_date'));
    }

    public static function userId(): Select
    {
        return Select::make('user_id')
            ->label(__('resources/profile/strings.form.user_id'))
            ->relationship('user', 'name')
            ->searchable()
            ->preload()
            ->required()
            ->columnSpanFull()
            ->native(false)
            ->helperText(__('resources/profile/strings.hints.user_id'))
            ->validationMessages([
                'required' => __('resources/profile/strings.validation.user_id.required'),
            ]);
    }

    public static function workExperience(): Select
    {
        return Select::make('work_experience')
            ->label('سابقه کاری')
            ->options(WorkExperience::class)
            ->native(false)
            ->searchable(false)
            ->helperText(__('resources/profile/strings.hints.work_experience'));
    }

    public static function zipCode(): TextInput
    {
        return TextInput::make('zip_code')
            ->label(__('resources/profile/strings.form.zip_code'))
            ->maxLength(20)
            ->helperText(__('resources/profile/strings.hints.zip_code'));
    }
}
