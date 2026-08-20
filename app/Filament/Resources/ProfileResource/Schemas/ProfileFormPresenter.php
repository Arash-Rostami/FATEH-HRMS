<?php

namespace App\Filament\Resources\ProfileResource\Schemas;

use App\Enums\FavoriteColor;
use App\Enums\ProfileDetailGroup;
use App\Enums\SkillRequestStatus;
use App\Filament\Resources\ProfileResource\Enums\Degree;
use App\Filament\Resources\ProfileResource\Enums\EmploymentStatus;
use App\Filament\Resources\ProfileResource\Enums\EmploymentType;
use App\Filament\Resources\ProfileResource\Enums\Gender;
use App\Filament\Resources\ProfileResource\Enums\MaritalStatus;
use App\Filament\Resources\ProfileResource\Enums\Position;
use App\Filament\Resources\ProfileResource\Enums\WorkExperience;
use App\Livewire\Dashboard\Profile\Presentation\SkillPresenter;
use App\Models\Department;
use App\Models\Skill;
use App\Services\PersianDateFieldService;
use App\Services\ProfileDetailCatalog;
use App\Traits\FilamentFormDivider;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;

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
                    ->required(),
                TextInput::make('value')
                    ->label('مقدار')
                    ->maxLength(2000),
            ])
            ->afterStateHydrated(function ($component, $state) {
                $formatted = [];
                foreach ($state ?? [] as $k => $v) {
                    $formatted[] = ['key' => $k, 'value' => is_array($v) ? implode(', ', array_map(fn($x) => is_array($x) ? implode(', ', $x) : $x, $v)) : $v];
                }
                $component->state($formatted);
            })
            ->saveRelationshipsUsing(function ($component, $record) {
                $data = [];
                foreach ($component->getState() ?? [] as $item) {
                    if (blank($item['key'] ?? null)) continue;
                    $data[$item['key']] = $item['value'];
                }
                $record->update(['about_me' => $data]);
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
                    ->required(fn(Get $get): bool => filled($get('path')))
                    ->maxLength(255),

                Select::make('category')
                    ->label('نوع پیوست')
                    ->options([
                        'pdf' => 'PDF',
                        'image' => 'تصویر',
                        'doc' => 'سند',
                        'standard' => 'استاندارد',
                        'custom' => 'سفارشی'
                    ])
                    ->required(fn(Get $get): bool => filled($get('path')))
                    ->native(false),

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
                    ->fetchFileInformation(false)
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->addable()
            ->deletable()
            ->reorderable(false)
            ->columnSpanFull()
            ->helperText(__('resources/profile/strings.hints.attachments'))
            ->dehydrateStateUsing(fn(?array $state): array => array_values(array_filter(
                $state ?? [],
                fn(array $item): bool => filled($item['key'] ?? null) ||
                    filled($item['category'] ?? null) ||
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
            ->helperText(__('resources/profile/strings.hints.degree'));
    }

    public static function departmentId(): Select
    {
        return Select::make('department_id')
            ->label(__('resources/profile/strings.form.department_id'))
            ->options(fn() => Department::getCachedOptions()->toArray())
            ->searchable()
            ->native(false)
            ->required()
            ->helperText(__('resources/profile/strings.hints.department_id'));
    }

    public static function details(): Repeater
    {
        $label = __('resources/profile/strings.form.detail_value');

        $rowHasValue = fn(Get $get): bool => filled($get('value_text'))
            || filled($get('value_number'))
            || filled($get('value_textarea'))
            || filled($get('value_select'))
            || filled($get('value_date_year'))
            || filled($get('value_date_month'))
            || filled($get('value_date_day'));

        return Repeater::make('details')
            ->relationship()
            ->label(__('resources/profile/strings.form.details'))
            ->schema([
                Select::make('key')
                    ->label(__('resources/profile/strings.form.detail_key'))
                    ->options(ProfileDetailCatalog::selectGroups())
                    ->searchable()
                    ->required($rowHasValue)
                    ->distinct()
                    ->live()
                    ->afterStateUpdated(fn(callable $set) => self::resetDetailValues($set))
                    ->getOptionLabelUsing(fn($value): string => ProfileDetailCatalog::definition($value)['label'] ?? $value)
                    ->createOptionForm([
                        TextInput::make('key')->label(__('resources/profile/strings.form.detail_custom_key'))->required()->maxLength(191),
                    ])
                    ->createOptionUsing(fn(array $data): string => $data['key']),

                TextInput::make('value_text')
                    ->label($label)
                    ->required(fn(Get $get): bool => filled($get('key')))
                    ->maxLength(fn(Get $get): int => ProfileDetailCatalog::definition($get('key'))['max'] ?? 2000)
                    ->visible(fn(Get $get): bool => in_array(self::getDetailType($get('key')), ['text', 'url', 'email'])),

                TextInput::make('value_number')
                    ->label($label)
                    ->required(fn(Get $get): bool => filled($get('key')))
                    ->numeric()
                    ->visible(fn(Get $get): bool => self::getDetailType($get('key')) === 'number'),

                Textarea::make('value_textarea')
                    ->label($label)
                    ->required(fn(Get $get): bool => filled($get('key')))
                    ->maxLength(2000)
                    ->visible(fn(Get $get): bool => self::getDetailType($get('key')) === 'textarea'),

                Select::make('value_select')
                    ->label($label)
                    ->required(fn(Get $get): bool => filled($get('key')))
                    ->native(false)
                    ->searchable()
                    ->options(fn(Get $get): array => match ($get('key')) {
                        'unit' => Department::getCachedModels()->get($get('../../department_id'))?->unitsOptions() ?? [],
                        'section' => Department::getCachedModels()->get($get('../../department_id'))?->sectionsOptions() ?? [],
                        default => ProfileDetailCatalog::definition($get('key'))['options'] ?? [],
                    })
                    ->visible(fn(Get $get): bool => self::getDetailType($get('key')) === 'select'),

                PersianDateFieldService::make(
                    prefix: 'value_date',
                    label: $label,
                    required: true,
                    yearFrom: 1330,
                    yearTo: 1410
                )->visible(fn(Get $get): bool => self::getDetailType($get('key')) === 'date'),
            ])
            ->mutateRelationshipDataBeforeFillUsing(fn(array $data): array => self::hydrateDetailValue($data))
            ->mutateRelationshipDataBeforeCreateUsing(fn(array $data): ?array => self::dehydrateDetailValue($data))
            ->mutateRelationshipDataBeforeSaveUsing(fn(array $data): ?array => self::dehydrateDetailValue($data))
            ->defaultItems(0)
            ->addable()
            ->deletable()
            ->reorderable(false)
            ->columns(2)
            ->columnSpanFull()
            ->helperText(__('resources/profile/strings.hints.details'));
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
            ->helperText(__('resources/profile/strings.hints.employment_status'));
    }

    public static function employmentType(): Select
    {
        return Select::make('employment_type')
            ->label(__('resources/profile/strings.form.employment_type'))
            ->options(EmploymentType::class)
            ->required()
            ->native(false)
            ->helperText(__('resources/profile/strings.hints.employment_type'));
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
                Select::make('color')
                    ->label(__('resources/profile/strings.form.favorite_color_item'))
                    ->options(FavoriteColor::class)
                    ->native(false)
                    ->required()
            ])
            ->grid(4)
            ->columns(1)
            ->addable()
            ->deletable()
            ->reorderable(false)
            ->columnSpanFull()
            ->helperText(__('resources/profile/strings.hints.favorite_colors'))
            ->afterStateHydrated(function (Repeater $component, mixed $state): void {
                $normalized = [];
                foreach ($state ?? [] as $item) {
                    $color = is_string($item) ? $item : ($item['color'] ?? null);
                    if (filled($color)) {
                        $normalized[] = ['color' => $color];
                    }
                }
                $component->state($normalized);
            })
            ->dehydrateStateUsing(function ($state): array {
                $colors = [];
                foreach ($state ?? [] as $item) {
                    $color = is_array($item) ? ($item['color'] ?? null) : $item;
                    if ($color) {
                        $colors[] = $color;
                    }
                }
                return $colors;
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
            ->helperText(__('resources/profile/strings.hints.gender'));
    }

    public static function idBookletNumber(): TextInput
    {
        return TextInput::make('id_booklet_number')
            ->label(__('resources/profile/strings.form.id_booklet_number'))
            ->unique(ignoreRecord: true)
            ->maxLength(20)
            ->helperText(__('resources/profile/strings.hints.id_booklet_number'));
    }

    public static function idCardNumber(): TextInput
    {
        return TextInput::make('id_card_number')
            ->label(__('resources/profile/strings.form.id_card_number'))
            ->unique(ignoreRecord: true)
            ->maxLength(20)
            ->helperText(__('resources/profile/strings.hints.id_card_number'));
    }

    public static function image(): FileUpload
    {
        return FileUpload::make('image')
            ->label(__('resources/profile/strings.form.image'))
            ->avatar()
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
            ->helperText(__('resources/profile/strings.hints.marital_status'));
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
            ->helperText(__('resources/profile/strings.hints.personnel_id'));
    }

    public static function position(): Select
    {
        return Select::make('position')
            ->label(__('resources/profile/strings.form.position'))
            ->required()
            ->options(Position::class)
            ->default(Position::Employee->value)
            ->preload()
            ->helperText(__('resources/profile/strings.hints.position'));
    }

    public static function skills(): Repeater
    {
        return Repeater::make('skills')
            ->label(__('resources/profile/strings.form.skills'))
            ->schema([
                Hidden::make('id'),

                Select::make('skill_id')
                    ->label(__('resources/skill/strings.fields.skill'))
                    ->options(fn(): array => self::skillCatalogOptions())
                    ->searchable()
                    ->distinct()
                    ->required()
                    ->native(false),

                Select::make('status')
                    ->label(__('resources/skill/strings.fields.status'))
                    ->options(collect(SkillRequestStatus::cases())->mapWithKeys(fn(SkillRequestStatus $case): array => [$case->value => $case->label()]))
                    ->default(SkillRequestStatus::Approved->value)
                    ->required()
                    ->native(false),

                PersianDateFieldService::make(
                    'last_used_at',
                    __('resources/skill/strings.fields.last_used_at'),
                    required: false,
                    yearFrom: 1370,
                ),

                Toggle::make('is_mentoring')
                    ->label(__('resources/skill/strings.fields.is_mentoring')),

                Toggle::make('is_private')
                    ->label(__('resources/skill/strings.fields.is_private')),
            ])
            ->afterStateHydrated(function (Repeater $component): void {
                $record = $component->getRecord();
                $skillUsers = $record instanceof Model ? $record->user?->skillUsers()->with('skill')->latest('id')->get() : null;

                $component->state(($skillUsers ?? collect())->map(fn($skillUser): array => [
                    'id' => $skillUser->id,
                    'skill_id' => $skillUser->skill_id,
                    'status' => $skillUser->status->value,
                    'last_used_at' => $skillUser->last_used_at?->format('Y-m-d'),
                    'is_mentoring' => $skillUser->is_mentoring,
                    'is_private' => $skillUser->is_private,
                ])->values()->toArray());
            })
            ->saveRelationshipsUsing(function (Repeater $component, mixed $record): void {
                $user = $record instanceof Model ? $record->user : null;

                if (!$user) {
                    return;
                }

                $existingIds = $user->skillUsers()->pluck('id')->all();
                $keptIds = [];

                foreach ($component->getState() ?? [] as $item) {
                    if (blank($item['skill_id'] ?? null)) {
                        continue;
                    }

                    $status = $item['status'] ?? SkillRequestStatus::Approved->value;
                    $isApproved = $status === SkillRequestStatus::Approved->value;

                    $payload = [
                        'skill_id' => $item['skill_id'],
                        'status' => $status,
                        'last_used_at' => $item['last_used_at'] ?? null,
                        'is_mentoring' => (bool)($item['is_mentoring'] ?? false),
                        'is_private' => (bool)($item['is_private'] ?? false),
                        'approved_at' => $isApproved ? now() : null,
                        'approved_by' => $isApproved ? auth()->id() : null,
                    ];

                    $id = (int)($item['id'] ?? 0);

                    if ($id && in_array($id, $existingIds, true)) {
                        $user->skillUsers()->whereKey($id)->update($payload);
                        $keptIds[] = $id;
                        continue;
                    }

                    $keptIds[] = $user->skillUsers()->create($payload)->id;
                }

                $toDelete = array_diff($existingIds, $keptIds);

                if (filled($toDelete)) {
                    $user->skillUsers()->whereKey($toDelete)->delete();
                }
            })
            ->columns(2)
            ->dehydrated(false)
            ->addable()
            ->deletable()
            ->reorderable(false)
            ->columnSpanFull()
            ->helperText(__('resources/profile/strings.hints.skills'));
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
            ->required()
            ->columnSpanFull()
            ->native(false)
            ->helperText(__('resources/profile/strings.hints.user_id'));
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

    private static function dehydrateDetailValue(array $data): ?array
    {
        if (blank($data['key'] ?? null)) {
            return null;
        }

        $type = self::getDetailType($data['key']);
        $data['value'] = $data["value_{$type}"] ?? null;

        foreach (['text', 'number', 'textarea', 'select', 'date', 'date_year', 'date_month', 'date_day'] as $t) {
            unset($data["value_{$t}"]);
        }

        $data['section'] = ProfileDetailCatalog::definition($data['key'])['section'] ?? ProfileDetailGroup::Other->value;

        return $data;
    }

    private static function getDetailType(?string $key): string
    {
        return ProfileDetailCatalog::definition($key ?? '')['type'] ?? 'text';
    }

    private static function hydrateDetailValue(array $data): array
    {
        $type = self::getDetailType($data['key'] ?? '');
        $data["value_{$type}"] = $data['value'] ?? null;

        return $data;
    }

    private static function resetDetailValues(callable $set): void
    {
        foreach (['text', 'number', 'textarea', 'select', 'date'] as $type) {
            $set("value_{$type}", null);
        }
        $set('value_date_year', null);
        $set('value_date_month', null);
        $set('value_date_day', null);
    }

    private static function skillCatalogOptions(): array
    {
        $presenter = new SkillPresenter();

        return Skill::cachedActiveCatalog()
            ->groupBy(fn(Skill $skill): string => $presenter->categoryLabel($skill))
            ->map(fn($skills) => $skills->mapWithKeys(fn(Skill $skill): array => [
                $skill->id => trim($skill->name . ($skill->name_en ? " ({$skill->name_en})" : '')),
            ]))
            ->toArray();
    }
}
