<?php

namespace App\Filament\Resources\UserResource\Schemas;

use App\Enums\PresenceStatus;
use App\Enums\ResourceType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\User;
use App\Support\FileNamer;
use App\Traits\Filament\FilamentFormDivider;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class UserFormPresenter
{
    use FilamentFormDivider;

    public const SIGNATURE_DIRECTORY = 'users/signatures';

    public static function booking(): Repeater
    {
        return Repeater::make('booking')
            ->label(__('resources/user/strings.form.booking'))
            ->schema([
                Toggle::make('value')
                    ->label(__('resources/user/strings.form.booking_value'))
                    ->columnSpanFull()
                    ->live()
                    ->afterStateUpdated(function (bool $state, Get $get, Set $set): void {
                        $items = $get('../') ?? [];

                        if ($get('key') === 'all') {
                            $state && $set('../', array_map(fn($item) => [...$item, 'value' => true], $items));
                            return;
                        }

                        if (!$state) {
                            $set('../', array_map(fn($item) => ($item['key'] ?? null) === 'all' ? [...$item, 'value' => false] : $item, $items));
                        }
                    }),
                Select::make('key')
                    ->label(__('resources/user/strings.form.booking_key'))
                    ->options(fn($state) => ['all' => 'همه'] + ResourceType::pluck() + (filled($state) ? [$state => $state] : []))
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->dehydrated(true)
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('max')
                    ->label(fn(Get $get) => __($get('key') === 'all' ? 'resources/user/strings.form.booking_max_all' : 'resources/user/strings.form.booking_max'))
                    ->placeholder(fn(Get $get) => $get('key') === 'all' ? null : collect($get('../') ?? [])->firstWhere('key', 'all')['max'] ?? null)
                    ->integer()
                    ->minValue(1)
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->grid(5)
            ->addable(true)
            ->deletable(true)
            ->reorderable(false)
            ->default(fn() => array_merge(
                [['key' => 'all', 'value' => false]],
                collect(ResourceType::cases())->map(fn(ResourceType $type) => ['key' => $type->value, 'value' => true])->all()
            ))
            ->afterStateHydrated(function (Repeater $component, $state): void {
                $set = static::normalizeBookingState($component->getRecord()?->bookingRows() ?: $state ?: $component->getDefaultState());

                if ($set !== []) {
                    $component->state($set);
                }
            })
            ->helperText(__('resources/user/strings.hints.booking'));
    }

    public static function normalizeBookingState(mixed $state): array
    {
        if (blank($state)) {
            return [];
        }

        $rows = collect($state)->map(function ($v, $k) {
            if (is_array($v)) {
                $key = $v['key'] ?? $k;
                $value = $v['value'] ?? false;
                $max = $v['max'] ?? null;
            } else {
                $key = $k;
                $value = $v;
                $max = null;
            }

            return ['key' => $key, 'value' => (bool) $value, 'max' => filled($max) ? max(1, (int) $max) : null];
        })->keyBy('key');

        foreach (ResourceType::cases() as $type) {
            $rows->has($type->value) || $rows->put($type->value, ['key' => $type->value, 'value' => false, 'max' => null]);
        }

        return $rows->sortBy(fn($i) => $i['key'] === 'all' ? 0 : 1)->values()->all();
    }

    public static function email(): TextInput
    {
        return TextInput::make('email')
            ->label(__('resources/user/strings.form.email'))
            ->email()
            ->required()
            ->unique(ignoreRecord: true)
            ->maxLength(255)
            ->helperText(__('resources/user/strings.hints.email'));
    }

    public static function extra(): KeyValue
    {
        return KeyValue::make('extra')
            ->label(__('resources/user/strings.form.extra'))
            ->keyLabel(__('resources/user/strings.form.extra_key'))
            ->valueLabel(__('resources/user/strings.form.extra_value'))
            ->columns(2)
            ->addActionLabel('افزودن ردیف')
            ->afterStateHydrated(function (KeyValue $component, ?array $state, ?User $record): void {
                $component->state($record?->editableExtra() ?? User::editableExtraOf($state));
            })
            ->dehydrateStateUsing(function (?array $state, ?User $record): array {
                $stored = collect($record?->adminExtra() ?? [])->only(User::PROTECTED_ADMIN_KEYS);

                return self::flatRows($state)->merge($stored)->all();
            })
            ->columnSpanFull()
            ->helperText(__('resources/user/strings.hints.extra'));
    }

    public static function registryPermission(): Toggle
    {
        return Toggle::make('registry_permission')
            ->label(__('resources/correspondence/strings.admin.fields.registry_permission'))
            ->helperText(__('resources/correspondence/strings.admin.fields.registry_permission_hint'))
            ->default(false);
    }

    public static function signature(): FileUpload
    {
        return FileUpload::make('signature_image')
            ->label(__('resources/correspondence/strings.admin.fields.signature'))
            ->image()
            ->disk('local')
            ->visibility('private')
            ->downloadable()
            ->openable()
            ->previewable()
            ->directory(self::SIGNATURE_DIRECTORY)
            ->maxSize(config('ai.correspondence.upload.image_kb'))
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->rule(Rule::dimensions()->maxWidth(config('ai.correspondence.upload.image_max_width')))
            ->getUploadedFileNameForStorageUsing(
                fn (TemporaryUploadedFile $file): string => FileNamer::stored('signature', $file)
            )
            ->nullable()
            ->helperText(__('resources/correspondence/strings.admin.fields.signature_hint'));
    }

    public static function signCode(): Group
    {
        $minLength = (int) config('ai.correspondence.sign_code_min_length');

        return Group::make([
            TextInput::make('sign_code')
                ->label(__('resources/correspondence/strings.admin.fields.sign_code'))
                ->password()
                ->revealable()
                ->autocomplete('new-password')
                ->minLength($minLength)
                ->maxLength(72)
                ->hidden(fn (Get $get): bool => (bool) $get('clear_sign_code'))
                ->dehydrateStateUsing(fn ($state): ?string => filled($state) ? Hash::make($state) : null)
                ->dehydrated(fn ($state): bool => filled($state))
                ->helperText(__('resources/correspondence/strings.admin.fields.sign_code_hint', ['min' => convertToPersian($minLength)])),
            Toggle::make('clear_sign_code')
                ->label(__('resources/correspondence/strings.admin.fields.clear_sign_code'))
                ->helperText(__('resources/correspondence/strings.admin.fields.clear_sign_code_hint'))
                ->live()
                ->default(false)
                ->visible(fn (?User $record): bool => filled($record?->getExtraValue('admin.sign_code_hash'))),
        ])->columnSpan(1);
    }

    public static function signCodePassword(): TextInput
    {
        return TextInput::make('sign_code_password')
            ->label(__('resources/correspondence/strings.admin.fields.sign_code_password'))
            ->password()
            ->revealable()
            ->autocomplete('current-password')
            ->rule('current_password:web')
            ->required(fn (Get $get): bool => filled($get('sign_code')) || (bool) $get('clear_sign_code'))
            ->dehydrated(false)
            ->helperText(__('resources/correspondence/strings.admin.fields.sign_code_password_hint'));
    }

    public static function name(): TextInput
    {
        return TextInput::make('name')
            ->label(__('resources/user/strings.form.name'))
            ->required()
            ->maxLength(255)
            ->helperText(__('resources/user/strings.hints.name'));
    }

    public static function password(): TextInput
    {
        return TextInput::make('password')
            ->label(__('resources/user/strings.form.password'))
            ->password()
            ->revealable()
            ->required()
            ->minLength(8)
            ->confirmed()
            ->visible(fn(string $operation): bool => $operation === 'create')
            ->dehydrated(fn(string $operation): bool => $operation === 'create')
            ->helperText(__('resources/user/strings.hints.password'));
    }

    public static function passwordConfirmation(): TextInput
    {
        return TextInput::make('password_confirmation')
            ->label(__('resources/user/strings.form.password_confirmation'))
            ->password()
            ->revealable()
            ->required()
            ->visible(fn(string $operation): bool => $operation === 'create')
            ->dehydrated(false)
            ->helperText(__('resources/user/strings.hints.password_confirmation'));
    }

    public static function presence(): Select
    {
        return Select::make('presence')
            ->label(__('resources/user/strings.form.presence'))
            ->options(
                collect(PresenceStatus::cases())->mapWithKeys(function ($status) {
                    $icon = Blade::render(
                        "<x-filament::icon icon=\"{$status->heroicon()}\" class=\"h-5 w-5\" />"
                    );

                    return [$status->value => "<div class='flex items-center gap-2'>{$icon}<span>{$status->label()}</span></div>"];
                })->toArray()
            )
            ->allowHtml()
            ->default(PresenceStatus::default()->value)
            ->required()
            ->native(false)
            ->helperText(__('resources/user/strings.hints.presence'));
    }

    public static function role(): Select
    {
        return Select::make('role')
            ->label(__('resources/user/strings.form.role'))
            ->options(UserRole::class)
            ->default(UserRole::User->value)
            ->required()
            ->native(false)
            ->disableOptionWhen(fn(string $value): bool => $value === UserRole::Developer->value)
            ->helperText(__('resources/user/strings.hints.role'));
    }

    public static function status(): Select
    {
        return Select::make('status')
            ->label(__('resources/user/strings.form.status'))
            ->options(UserStatus::class)
            ->default(UserStatus::Active->value)
            ->required()
            ->native(false)
            ->rule(fn($record) => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                if ($value === UserStatus::Active->value && $record?->status !== UserStatus::Active->value && $record?->profile?->isTerminated()) {
                    $fail(__('resources/user/strings.validation.terminated_locked'));
                }
            })
            ->helperText(__('resources/user/strings.hints.status'));
    }

    public static function type(): Select
    {
        return Select::make('type')
            ->label(__('resources/user/strings.form.type'))
            ->options(UserType::class)
            ->default(UserType::Employee->value)
            ->required()
            ->native(false)
            ->helperText(__('resources/user/strings.hints.type'));
    }

    private static function flatRows(?array $state): Collection
    {
        return collect($state)
            ->except([...User::PROTECTED_ADMIN_KEYS, 'admin', 'preferences'])
            ->filter(fn ($value): bool => is_scalar($value) || $value === null);
    }
}
