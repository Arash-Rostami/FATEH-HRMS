<?php

namespace App\Filament\Resources\ChannelResource\Schemas;

use App\Enums\ChannelType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Validation\Rule;

class ChannelFormPresenter
{
    public static function name(): TextInput
    {
        return TextInput::make('name')
            ->label(__('resources/channel/strings.fields.name'))
            ->required()
            ->maxLength(100)
            ->live(onBlur: true);
    }

    public static function slug(): TextInput
    {
        return TextInput::make('slug')
            ->label(__('resources/channel/strings.fields.slug'))
            ->required()
            ->maxLength(120)
            ->alphaDash()
            ->helperText(__('resources/channel/strings.hints.slug'))
            ->rule(fn($record) => Rule::unique('channels', 'slug')
                ->whereNull('deleted_at')
                ->ignore($record?->id));
    }

    public static function description(): Textarea
    {
        return Textarea::make('description')
            ->label(__('resources/channel/strings.fields.description'))
            ->nullable()
            ->rows(4)
            ->maxLength(500)
            ->columnSpanFull();
    }

    public static function type(): Select
    {
        return Select::make('type')
            ->label(__('resources/channel/strings.fields.type'))
            ->required()
            ->default(ChannelType::Open->value)
            ->options(collect(ChannelType::cases())
                ->mapWithKeys(fn(ChannelType $t) => [$t->value => $t->getLabel()]));
    }

    public static function owner(): Select
    {
        return Select::make('owner_id')
            ->label(__('resources/channel/strings.fields.owner'))
            ->relationship('owner', 'name')
            ->searchable()
            ->preload();
    }
}