<?php

namespace App\Filament\Resources\ResourceResource\Schemas;

use App\Enums\ResourceType;
use App\Enums\ResourceTypeIcon;
use App\Filament\Resources\ResourceResource\Enums\ResourceStatus;
use App\Filament\Resources\ResourceResource\Schemas\Helper\TypeProvisioner;
use App\Traits\FilamentFormDivider;
use App\Traits\FilamentIconOptions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;

class ResourceFormPresenter
{
    use FilamentFormDivider, FilamentIconOptions;

    private const TYPE_COLORS = [
        'primary' => 'آبی',
        'success' => 'سبز',
        'warning' => 'زرد',
        'danger' => 'قرمز',
        'info' => 'آبی روشن',
        'gray' => 'خاکستری',
    ];

    public static function availableDays(): CheckboxList
    {
        return CheckboxList::make('metadata.available_days')
            ->label(__('resources/resource/strings.fields.available_days'))
            ->helperText(__('resources/resource/strings.hints.available_days'))
            ->options(__('resources/policy/strings.days'))
            ->columns(4)
            ->columnSpanFull();
    }

    public static function capacity(): TextInput
    {
        return TextInput::make('metadata.capacity')
            ->label(__('resources/resource/strings.fields.capacity'))
            ->numeric()
            ->nullable()
            ->helperText(__('resources/resource/strings.hints.capacity'))
            ->visible(fn($livewire) => in_array($livewire->data['type'] ?? null, ['meeting', 'car']))
            ->dehydrated(fn($livewire) => in_array($livewire->data['type'] ?? null, ['meeting', 'car']));
    }

    public static function cardNumber(): TextInput
    {
        return TextInput::make('metadata.card')
            ->label(__('resources/resource/strings.fields.card_number'))
            ->maxLength(255)
            ->nullable()
            ->helperText(__('resources/resource/strings.hints.card_number'))
            ->visible(fn($livewire) => ($livewire->data['type'] ?? null) === 'spot')
            ->dehydrated(fn($livewire) => ($livewire->data['type'] ?? null) === 'spot');
    }

    public static function customMetadata(): KeyValue
    {
        return KeyValue::make('metadata.custom')
            ->label(__('resources/resource/strings.fields.custom'))
            ->keyLabel(__('resources/resource/strings.fields.custom_key'))
            ->valueLabel(__('resources/resource/strings.fields.custom_value'))
            ->columns(2)
            ->addActionLabel('افزودن ردیف')
            ->columnSpanFull()
            ->helperText(__('resources/resource/strings.hints.custom'));
    }

    public static function extension(): TextInput
    {
        return TextInput::make('metadata.extension')
            ->label(__('resources/resource/strings.fields.extension'))
            ->maxLength(255)
            ->nullable()
            ->helperText(__('resources/resource/strings.hints.extension'))
            ->visible(fn($livewire) => in_array($livewire->data['type'] ?? null, ['seat', 'meeting']))
            ->dehydrated(fn($livewire) => in_array($livewire->data['type'] ?? null, ['seat', 'meeting']));
    }

    public static function floor(): TextInput
    {
        return TextInput::make('metadata.floor')
            ->label(__('resources/resource/strings.fields.floor'))
            ->maxLength(255)
            ->nullable()
            ->helperText(__('resources/resource/strings.hints.floor'))
            ->visible(fn($livewire) => in_array($livewire->data['type'] ?? null, ['seat', 'spot']))
            ->dehydrated(fn($livewire) => in_array($livewire->data['type'] ?? null, ['seat', 'spot']));
    }

    public static function image(): FileUpload
    {
        return FileUpload::make('image')
            ->label(__('resources/resource/strings.fields.image'))
            ->image()
            ->downloadable()
            ->openable()
            ->previewable()
            ->disk('public')
            ->directory('resources')
            ->nullable()
            ->helperText(__('resources/resource/strings.hints.image'))
            ->columnSpanFull();
    }

    public static function normalizeMetadata(array $data): array
    {
        if (blank($data['metadata']['time_slots']['start'] ?? null) || blank($data['metadata']['time_slots']['end'] ?? null)) {
            unset($data['metadata']['time_slots']);
        }

        if (empty($data['metadata']['available_days'])) {
            unset($data['metadata']['available_days']);
        }

        return $data;
    }

    public static function name(): TextInput
    {
        return TextInput::make('name')
            ->label(__('resources/resource/strings.fields.name'))
            ->required()
            ->maxLength(255)
            ->helperText(fn($livewire) => ($livewire->data['type'] ?? null) === 'meeting'
                ? __('resources/resource/strings.hints.name_meeting')
                : __('resources/resource/strings.hints.name'));
    }

    public static function notes(): Textarea
    {
        return Textarea::make('metadata.notes')
            ->label(__('resources/resource/strings.fields.notes'))
            ->maxLength(1000)
            ->nullable()
            ->rows(2)
            ->helperText(__('resources/resource/strings.hints.notes'))
            ->columnSpanFull();
    }

    public static function status(): Select
    {
        return Select::make('status')
            ->label(__('resources/resource/strings.fields.status'))
            ->options(ResourceStatus::class)
            ->default('active')
            ->required()
            ->helperText(__('resources/resource/strings.hints.status'));
    }

    public static function timeSlotEnd(): TimePicker
    {
        return TimePicker::make('metadata.time_slots.end')
            ->label(__('resources/resource/strings.fields.time_slot_end'))
            ->helperText(__('resources/resource/strings.hints.time_slot_end'))
            ->seconds(false)
            ->displayFormat('H:i')
            ->format('H:i')
            ->after('metadata.time_slots.start')
            ->visible(fn($livewire) => ResourceType::tryFrom($livewire->data['type'] ?? null)?->isFullDay() === false)
            ->dehydrated(fn($livewire) => ResourceType::tryFrom($livewire->data['type'] ?? null)?->isFullDay() === false);
    }

    public static function timeSlotStart(): TimePicker
    {
        return TimePicker::make('metadata.time_slots.start')
            ->label(__('resources/resource/strings.fields.time_slot_start'))
            ->helperText(__('resources/resource/strings.hints.time_slot_start'))
            ->seconds(false)
            ->displayFormat('H:i')
            ->format('H:i')
            ->before('metadata.time_slots.end')
            ->visible(fn($livewire) => ResourceType::tryFrom($livewire->data['type'] ?? null)?->isFullDay() === false)
            ->dehydrated(fn($livewire) => ResourceType::tryFrom($livewire->data['type'] ?? null)?->isFullDay() === false);
    }

    public static function unit(): TextInput
    {
        return TextInput::make('metadata.unit')
            ->label(__('resources/resource/strings.fields.unit'))
            ->maxLength(255)
            ->nullable()
            ->helperText(__('resources/resource/strings.hints.unit'))
            ->visible(fn($livewire) => ($livewire->data['type'] ?? null) === 'seat')
            ->dehydrated(fn($livewire) => ($livewire->data['type'] ?? null) === 'seat');
    }

    public static function type(): Select
    {
        return Select::make('type')
            ->label(__('resources/resource/strings.fields.type'))
            ->options(fn() => ResourceType::pluck())
            ->required()
            ->live()
            ->searchable()
            ->createOptionForm([
                TextInput::make('display_label')
                    ->label(__('resources/resource/strings.fields.type_label'))
                    ->required()
                    ->maxLength(255),
                Select::make('display_icon')
                    ->label(__('resources/resource/strings.fields.type_icon'))
                    ->options(fn() => static::curatedIconOptions())
                    ->native(false)
                    ->allowHtml()
                    ->getOptionLabelUsing(fn(string $value) => static::curatedIconOptionLabel($value))
                    ->default(ResourceTypeIcon::default()->value)
                    ->required(),
                Select::make('display_color')
                    ->label(__('resources/resource/strings.fields.type_color'))
                    ->options(self::TYPE_COLORS)
                    ->required(),
                Toggle::make('is_full_day')
                    ->label(__('resources/resource/strings.fields.type_full_day'))
                    ->helperText(__('resources/resource/strings.hints.type_full_day'))
                    ->default(true),
            ])
            ->createOptionUsing(fn(array $data) => TypeProvisioner::provision($data))
            ->helperText(__('resources/resource/strings.hints.type'));
    }
}
