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
            ->validationMessages([
                'required' => __('resources/user/strings.validation.generic.required'),
                'unique' => __('resources/user/strings.validation.generic.unique'),
                'max' => __('resources/user/strings.validation.generic.max'),
                'min' => __('resources/user/strings.validation.generic.min'),
                'email' => __('resources/user/strings.validation.generic.email'),
                'numeric' => __('resources/user/strings.validation.generic.numeric'),
                'mimes' => __('resources/user/strings.validation.generic.mimes'),
                'url' => __('resources/user/strings.validation.generic.url'),
                'in' => __('resources/user/strings.validation.generic.in'),
                'exists' => __('resources/user/strings.validation.generic.exists')
            ])
            ->label(__('resources/user/strings.form.booking'))
            ->schema([
                Toggle::make('value')
            ->validationMessages([
                'required' => __('resources/user/strings.validation.generic.required'),
                'unique' => __('resources/user/strings.validation.generic.unique'),
                'max' => __('resources/user/strings.validation.generic.max'),
                'min' => __('resources/user/strings.validation.generic.min'),
                'email' => __('resources/user/strings.validation.generic.email'),
                'numeric' => __('resources/user/strings.validation.generic.numeric'),
                'mimes' => __('resources/user/strings.validation.generic.mimes'),
                'url' => __('resources/user/strings.validation.generic.url'),
                'in' => __('resources/user/strings.validation.generic.in'),
                'exists' => __('resources/user/strings.validation.generic.exists')
            ])
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
            ->validationMessages([
                'required' => __('resources/user/strings.validation.generic.required'),
                'unique' => __('resources/user/strings.validation.generic.unique'),
                'max' => __('resources/user/strings.validation.generic.max'),
                'min' => __('resources/user/strings.validation.generic.min'),
                'email' => __('resources/user/strings.validation.generic.email'),
                'numeric' => __('resources/user/strings.validation.generic.numeric'),
                'mimes' => __('resources/user/strings.validation.generic.mimes'),
                'url' => __('resources/user/strings.validation.generic.url'),
                'in' => __('resources/user/strings.validation.generic.in'),
                'exists' => __('resources/user/strings.validation.generic.exists')
            ])
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
                if (blank($state)) return;

                $items = array_is_list($state) || isset(array_values($state)[0]['key'])
                    ? collect($component->getState())
                    : collect($state)->map(fn($v, $k) => ['key' => $k, 'value' => (bool)$v]);

                $component->state(
                    $items->sortBy(fn($i) => $i['key'] === 'all' ? 0 : 1)->values()->all()
                );
            })
            ->helperText(__('resources/user/strings.hints.booking'));
    }

    public static function email(): TextInput
    {
        return TextInput::make('email')
            ->validationMessages([
                'required' => __('resources/user/strings.validation.generic.required'),
                'unique' => __('resources/user/strings.validation.generic.unique'),
                'max' => __('resources/user/strings.validation.generic.max'),
                'min' => __('resources/user/strings.validation.generic.min'),
                'email' => __('resources/user/strings.validation.generic.email'),
                'numeric' => __('resources/user/strings.validation.generic.numeric'),
                'mimes' => __('resources/user/strings.validation.generic.mimes'),
                'url' => __('resources/user/strings.validation.generic.url'),
                'in' => __('resources/user/strings.validation.generic.in'),
                'exists' => __('resources/user/strings.validation.generic.exists')
            ])
            ->label(__('resources/user/strings.form.email'))
            ->email()
            ->required()
            ->unique(ignoreRecord: true)
            ->maxLength(255)
            ->helperText(__('resources/user/strings.hints.email'))
            ->validationMessages([
                'required' => __('resources/user/strings.form.email_required'),
                'unique' => __('resources/user/strings.form.email_unique'),
                'email' => __('resources/user/strings.form.email_invalid'),
            ]);
    }

    public static function extra(): KeyValue
    {
        return KeyValue::make('extra')
            ->validationMessages([
                'required' => __('resources/user/strings.validation.generic.required'),
                'unique' => __('resources/user/strings.validation.generic.unique'),
                'max' => __('resources/user/strings.validation.generic.max'),
                'min' => __('resources/user/strings.validation.generic.min'),
                'email' => __('resources/user/strings.validation.generic.email'),
                'numeric' => __('resources/user/strings.validation.generic.numeric'),
                'mimes' => __('resources/user/strings.validation.generic.mimes'),
                'url' => __('resources/user/strings.validation.generic.url'),
                'in' => __('resources/user/strings.validation.generic.in'),
                'exists' => __('resources/user/strings.validation.generic.exists')
            ])
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
            ->validationMessages([
                'required' => __('resources/user/strings.validation.generic.required'),
                'unique' => __('resources/user/strings.validation.generic.unique'),
                'max' => __('resources/user/strings.validation.generic.max'),
                'min' => __('resources/user/strings.validation.generic.min'),
                'email' => __('resources/user/strings.validation.generic.email'),
                'numeric' => __('resources/user/strings.validation.generic.numeric'),
                'mimes' => __('resources/user/strings.validation.generic.mimes'),
                'url' => __('resources/user/strings.validation.generic.url'),
                'in' => __('resources/user/strings.validation.generic.in'),
                'exists' => __('resources/user/strings.validation.generic.exists')
            ])
            ->label(__('resources/user/strings.form.maximum'))
            ->numeric()
            ->integer()
            ->default(12)
            ->minValue(1)
            ->required()
            ->hintColor('warning')
            ->helperText(__('resources/user/strings.hints.maximum'))
            ->validationMessages([
                'required' => __('resources/user/strings.form.maximum_required'),
                'min' => __('resources/user/strings.form.maximum_min'),
            ]);
    }

    public static function name(): TextInput
    {
        return TextInput::make('name')
            ->validationMessages([
                'required' => __('resources/user/strings.validation.generic.required'),
                'unique' => __('resources/user/strings.validation.generic.unique'),
                'max' => __('resources/user/strings.validation.generic.max'),
                'min' => __('resources/user/strings.validation.generic.min'),
                'email' => __('resources/user/strings.validation.generic.email'),
                'numeric' => __('resources/user/strings.validation.generic.numeric'),
                'mimes' => __('resources/user/strings.validation.generic.mimes'),
                'url' => __('resources/user/strings.validation.generic.url'),
                'in' => __('resources/user/strings.validation.generic.in'),
                'exists' => __('resources/user/strings.validation.generic.exists')
            ])
            ->label(__('resources/user/strings.form.name'))
            ->required()
            ->maxLength(255)
            ->helperText(__('resources/user/strings.hints.name'))
            ->validationMessages([
                'required' => __('resources/user/strings.form.name_required'),
                'max' => __('resources/user/strings.form.name_max'),
            ]);
    }

    public static function password(): TextInput
    {
        return TextInput::make('password')
            ->validationMessages([
                'required' => __('resources/user/strings.validation.generic.required'),
                'unique' => __('resources/user/strings.validation.generic.unique'),
                'max' => __('resources/user/strings.validation.generic.max'),
                'min' => __('resources/user/strings.validation.generic.min'),
                'email' => __('resources/user/strings.validation.generic.email'),
                'numeric' => __('resources/user/strings.validation.generic.numeric'),
                'mimes' => __('resources/user/strings.validation.generic.mimes'),
                'url' => __('resources/user/strings.validation.generic.url'),
                'in' => __('resources/user/strings.validation.generic.in'),
                'exists' => __('resources/user/strings.validation.generic.exists')
            ])
            ->label(__('resources/user/strings.form.password'))
            ->password()
            ->revealable()
            ->required()
            ->minLength(8)
            ->confirmed()
            ->visible(fn(string $operation): bool => $operation === 'create')
            ->dehydrated(fn(string $operation): bool => $operation === 'create')
            ->helperText(__('resources/user/strings.hints.password'))
            ->validationMessages([
                'required' => __('resources/user/strings.form.password_required'),
                'min' => __('resources/user/strings.form.password_min'),
                'confirmed' => __('resources/user/strings.form.password_confirmed'),
            ]);
    }

    public static function passwordConfirmation(): TextInput
    {
        return TextInput::make('password_confirmation')
            ->validationMessages([
                'required' => __('resources/user/strings.validation.generic.required'),
                'unique' => __('resources/user/strings.validation.generic.unique'),
                'max' => __('resources/user/strings.validation.generic.max'),
                'min' => __('resources/user/strings.validation.generic.min'),
                'email' => __('resources/user/strings.validation.generic.email'),
                'numeric' => __('resources/user/strings.validation.generic.numeric'),
                'mimes' => __('resources/user/strings.validation.generic.mimes'),
                'url' => __('resources/user/strings.validation.generic.url'),
                'in' => __('resources/user/strings.validation.generic.in'),
                'exists' => __('resources/user/strings.validation.generic.exists')
            ])
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
            ->validationMessages([
                'required' => __('resources/user/strings.validation.generic.required'),
                'unique' => __('resources/user/strings.validation.generic.unique'),
                'max' => __('resources/user/strings.validation.generic.max'),
                'min' => __('resources/user/strings.validation.generic.min'),
                'email' => __('resources/user/strings.validation.generic.email'),
                'numeric' => __('resources/user/strings.validation.generic.numeric'),
                'mimes' => __('resources/user/strings.validation.generic.mimes'),
                'url' => __('resources/user/strings.validation.generic.url'),
                'in' => __('resources/user/strings.validation.generic.in'),
                'exists' => __('resources/user/strings.validation.generic.exists')
            ])
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
            ->helperText(__('resources/user/strings.hints.presence'))
            ->validationMessages([
                'required' => __('resources/user/strings.form.presence_required'),
            ]);
    }

    public static function role(): Select
    {
        return Select::make('role')
            ->validationMessages([
                'required' => __('resources/user/strings.validation.generic.required'),
                'unique' => __('resources/user/strings.validation.generic.unique'),
                'max' => __('resources/user/strings.validation.generic.max'),
                'min' => __('resources/user/strings.validation.generic.min'),
                'email' => __('resources/user/strings.validation.generic.email'),
                'numeric' => __('resources/user/strings.validation.generic.numeric'),
                'mimes' => __('resources/user/strings.validation.generic.mimes'),
                'url' => __('resources/user/strings.validation.generic.url'),
                'in' => __('resources/user/strings.validation.generic.in'),
                'exists' => __('resources/user/strings.validation.generic.exists')
            ])
            ->label(__('resources/user/strings.form.role'))
            ->options(UserRole::class)
            ->default(UserRole::User->value)
            ->required()
            ->native(false)
            ->helperText(__('resources/user/strings.hints.role'))
            ->validationMessages([
                'required' => __('resources/user/strings.form.role_required'),
            ]);
    }

    public static function status(): Select
    {
        return Select::make('status')
            ->validationMessages([
                'required' => __('resources/user/strings.validation.generic.required'),
                'unique' => __('resources/user/strings.validation.generic.unique'),
                'max' => __('resources/user/strings.validation.generic.max'),
                'min' => __('resources/user/strings.validation.generic.min'),
                'email' => __('resources/user/strings.validation.generic.email'),
                'numeric' => __('resources/user/strings.validation.generic.numeric'),
                'mimes' => __('resources/user/strings.validation.generic.mimes'),
                'url' => __('resources/user/strings.validation.generic.url'),
                'in' => __('resources/user/strings.validation.generic.in'),
                'exists' => __('resources/user/strings.validation.generic.exists')
            ])
            ->label(__('resources/user/strings.form.status'))
            ->options(UserStatus::class)
            ->default(UserStatus::Active->value)
            ->required()
            ->native(false)
            ->helperText(__('resources/user/strings.hints.status'))
            ->validationMessages([
                'required' => __('resources/user/strings.form.status_required'),
            ]);
    }

    public static function type(): Select
    {
        return Select::make('type')
            ->validationMessages([
                'required' => __('resources/user/strings.validation.generic.required'),
                'unique' => __('resources/user/strings.validation.generic.unique'),
                'max' => __('resources/user/strings.validation.generic.max'),
                'min' => __('resources/user/strings.validation.generic.min'),
                'email' => __('resources/user/strings.validation.generic.email'),
                'numeric' => __('resources/user/strings.validation.generic.numeric'),
                'mimes' => __('resources/user/strings.validation.generic.mimes'),
                'url' => __('resources/user/strings.validation.generic.url'),
                'in' => __('resources/user/strings.validation.generic.in'),
                'exists' => __('resources/user/strings.validation.generic.exists')
            ])
            ->label(__('resources/user/strings.form.type'))
            ->options(UserType::class)
            ->default(UserType::Employee->value)
            ->required()
            ->native(false)
            ->helperText(__('resources/user/strings.hints.type'))
            ->validationMessages([
                'required' => __('resources/user/strings.form.type_required'),
            ]);
    }
}
