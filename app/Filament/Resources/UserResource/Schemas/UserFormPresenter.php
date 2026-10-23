<?php

namespace App\Filament\Resources\UserResource\Schemas;

use App\Enums\PresenceStatus;
use App\Enums\ResourceType;
use App\Filament\Resources\UserResource\Enums\UserRole;
use App\Filament\Resources\UserResource\Enums\UserStatus;
use App\Filament\Resources\UserResource\Enums\UserType;
use App\Traits\FilamentFormDivider;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Blade;

class UserFormPresenter
{
    use FilamentFormDivider;

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
            ->columnSpanFull()
            ->helperText(__('resources/user/strings.hints.extra'));
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
}
