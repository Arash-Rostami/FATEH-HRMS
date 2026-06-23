<?php

namespace App\Filament\Resources\ResourceResource\Schemas;

use App\Enums\ResourceType;
use App\Filament\Resources\ResourceResource\Enums\ResourceStatus;
use App\Traits\FilamentFormDivider;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

class ResourceFormPresenter
{
    use FilamentFormDivider;

    public static function capacity(): TextInput
    {
        return TextInput::make('metadata.capacity')
            ->label(__('resources/resource/strings.fields.capacity'))
            ->numeric()
            ->nullable()
            ->helperText(__('resources/resource/strings.hints.capacity'))
            ->visible(fn(Get $get) => in_array($get('type'), ['meeting', 'car']))
            ;
    }

    public static function extension(): TextInput
    {
        return TextInput::make('metadata.extension')
            ->label(__('resources/resource/strings.fields.extension'))
            ->maxLength(255)
            ->nullable()
            ->helperText(__('resources/resource/strings.hints.extension'))
            ->visible(fn(Get $get) => in_array($get('type'), ['seat', 'meeting']))
            ;
    }

    public static function floor(): TextInput
    {
        return TextInput::make('metadata.floor')
            ->label(__('resources/resource/strings.fields.floor'))
            ->maxLength(255)
            ->nullable()
            ->helperText(__('resources/resource/strings.hints.floor'))
            ->visible(fn(Get $get) => in_array($get('type'), ['seat', 'spot']))
            ;
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
            ->columnSpanFull()
            ;
    }

    public static function name(): TextInput
    {
        return TextInput::make('name')
            ->label(__('resources/resource/strings.fields.name'))
            ->required()
            ->maxLength(255)
            ->helperText(__('resources/resource/strings.hints.name'))
            ;
    }

    public static function notes(): Textarea
    {
        return Textarea::make('metadata.notes')
            ->label(__('resources/resource/strings.fields.notes'))
            ->maxLength(1000)
            ->nullable()
            ->rows(2)
            ->helperText(__('resources/resource/strings.hints.notes'))
            ->columnSpanFull()
            ;
    }

    public static function status(): Select
    {
        return Select::make('status')
            ->label(__('resources/resource/strings.fields.status'))
            ->options(ResourceStatus::class)
            ->default('active')
            ->required()
            ->helperText(__('resources/resource/strings.hints.status'))
            ;
    }

    public static function type(): Select
    {
        return Select::make('type')
            ->label(__('resources/resource/strings.fields.type'))
            ->options(ResourceType::class)
            ->required()
            ->live()
            ->helperText(__('resources/resource/strings.hints.type'))
            ;
    }
}
