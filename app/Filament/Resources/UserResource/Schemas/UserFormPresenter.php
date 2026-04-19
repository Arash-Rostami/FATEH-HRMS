<?php

namespace App\Filament\Resources\UserResource\Schemas;

use App\Enums\PresenceStatus;
use App\Filament\Resources\UserResource\Enums\UserRole;
use App\Filament\Resources\UserResource\Enums\UserStatus;
use App\Filament\Resources\UserResource\Enums\UserType;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class UserFormPresenter
{
    public static function booking(): Repeater
    {
        return Repeater::make('booking')
            ->label(__('resources/user/strings.form.booking'))
            ->schema([
                Toggle::make('value')
                    ->label(__('resources/user/strings.form.booking_value'))
                    ->columnSpanFull(),
                TextInput::make('key')
                    ->label(__('resources/user/strings.form.booking_key'))
                    ->dehydrated(true)
                    ->required()
                    ->columnSpanFull()
                    ->placeholder('کلید صرفا به انگلیسی')
                    ->extraAttributes(['dir' => 'ltr'])
            ])
            ->columns(2)
            ->grid(5)
            ->addable(true)
            ->deletable(true)
            ->reorderable(false)
            ->default([
                ['key' => 'car', 'value' => false],
                ['key' => 'seat', 'value' => true],
                ['key' => 'spot', 'value' => true],
                ['key' => 'meeting', 'value' => true],
                ['key' => 'all', 'value' => false],
            ])
            // DB  → Repeater
            ->afterStateHydrated(function (Repeater $component, $state): void {
                if (blank($state) || array_is_list($state)) return;
                if (isset(array_values($state)[0]['key'])) return;

                $component->state(collect($state)
                    ->map(fn($value, $key) => ['key' => $key, 'value' => (bool)$value])
                    ->values()
                    ->all()
                );
            });
    }

    public static function email(): TextInput
    {
        return TextInput::make('email')
            ->label(__('resources/user/strings.form.email'))
            ->email()
            ->required()
            ->unique(ignoreRecord: true)
            ->maxLength(255)
            ->validationMessages([
                'required' => __('resources/user/strings.form.email_required'),
                'unique' => __('resources/user/strings.form.email_unique'),
                'email' => __('resources/user/strings.form.email_invalid'),
            ]);
    }

    public static function extra(): KeyValue
    {
        return KeyValue::make('extra')
            ->label(__('resources/user/strings.form.extra'))
            ->keyLabel(__('resources/user/strings.form.extra_key'))
            ->valueLabel(__('resources/user/strings.form.extra_value'))
            ->columns(2)
            ->addActionLabel('افزودن ردیف')
            ->columnSpanFull();
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
            ->validationMessages([
                'required' => __('resources/user/strings.form.maximum_required'),
                'min' => __('resources/user/strings.form.maximum_min'),
            ]);
    }

    public static function name(): TextInput
    {
        return TextInput::make('name')
            ->label(__('resources/user/strings.form.name'))
            ->required()
            ->maxLength(255)
            ->validationMessages([
                'required' => __('resources/user/strings.form.name_required'),
                'max' => __('resources/user/strings.form.name_max'),
            ]);
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
            ->validationMessages([
                'required' => __('resources/user/strings.form.password_required'),
                'min' => __('resources/user/strings.form.password_min'),
                'confirmed' => __('resources/user/strings.form.password_confirmed'),
            ]);
    }

    public static function passwordConfirmation(): TextInput
    {
        return TextInput::make('password_confirmation')
            ->label(__('resources/user/strings.form.password_confirmation'))
            ->password()
            ->revealable()
            ->required()
            ->visible(fn(string $operation): bool => $operation === 'create')
            ->dehydrated(false);
    }

    public static function presence(): Select
    {
        return Select::make('presence')
            ->label(__('resources/user/strings.form.presence'))
            ->options(
                collect(PresenceStatus::cases())
                    ->mapWithKeys(fn(PresenceStatus $c) => [$c->value => $c->label()])
            )
            ->default(PresenceStatus::Onsite->value)
            ->required()
            ->native(false)
            ->validationMessages([
                'required' => __('resources/user/strings.form.presence_required'),
            ]);
    }

    public static function role(): Select
    {
        return Select::make('role')
            ->label(__('resources/user/strings.form.role'))
            ->options(UserRole::class)
            ->default(UserRole::User->value)
            ->required()
            ->native(false)
            ->validationMessages([
                'required' => __('resources/user/strings.form.role_required'),
            ]);
    }

    public static function status(): Select
    {
        return Select::make('status')
            ->label(__('resources/user/strings.form.status'))
            ->options(UserStatus::class)
            ->default(UserStatus::Active->value)
            ->required()
            ->native(false)
            ->validationMessages([
                'required' => __('resources/user/strings.form.status_required'),
            ]);
    }

    public static function type(): Select
    {
        return Select::make('type')
            ->label(__('resources/user/strings.form.type'))
            ->options(UserType::class)
            ->default(UserType::Employee->value)
            ->required()
            ->native(false)
            ->validationMessages([
                'required' => __('resources/user/strings.form.type_required'),
            ]);
    }
}
