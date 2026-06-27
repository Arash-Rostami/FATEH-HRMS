<?php

namespace App\Filament\Resources\UserResource\Schemas;

use App\Enums\PresenceStatus;
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
use Illuminate\Support\Facades\Blade;
use Livewire\Component;

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
                    ->afterStateUpdated(function (bool $state, Get $get, Component $livewire): void {
                        $booking = &$livewire->data['booking'];

                        if ($get('key') === 'all') {
                            $state && array_walk($booking, fn(&$item) => $item['value'] = true);
                            return;
                        }

                        if (!$state) {
                            foreach ($booking as &$item) {
                                if ($item['key'] === 'all') {
                                    $item['value'] = false;
                                    break;
                                }
                            }
                        }
                    }),
                TextInput::make('key')
                    ->label(__('resources/user/strings.form.booking_key'))
                    ->dehydrated(true)
                    ->required()
                    ->columnSpanFull()
                    ->placeholder('کلید صرفا به انگلیسی')
                    ->extraAttributes(['dir' => 'ltr']),
            ])
            ->columns(2)
            ->grid(5)
            ->addable(true)
            ->deletable(true)
            ->reorderable(false)
            ->default([
                ['key' => 'all', 'value' => false],
                ['key' => 'car', 'value' => false],
                ['key' => 'seat', 'value' => true],
                ['key' => 'spot', 'value' => true],
                ['key' => 'meeting', 'value' => true],
            ])
            ->afterStateHydrated(function (Repeater $component, $state): void {
                $set = static::normalizeBookingState($state);

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

        return collect($state)->map(function ($v, $k) {
            if (is_array($v)) {
                $key = $v['key'] ?? $k;
                $value = $v['value'] ?? false;
            } else {
                $key = $k;
                $value = $v;
            }

            return ['key' => $key, 'value' => (bool) $value];
        })->sortBy(fn($i) => $i['key'] === 'all' ? 0 : 1)->values()->all();
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

    public static function maximum(): TextInput
    {
        return TextInput::make('maximum')
            ->label(__('resources/user/strings.form.maximum'))
            ->numeric()
            ->integer()
            ->default(12)
            ->minValue(1)
            ->required()
            ->hintColor('warning')
            ->helperText(__('resources/user/strings.hints.maximum'));
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
            ->default(PresenceStatus::Onsite->value)
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
