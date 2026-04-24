<?php

namespace App\Filament\Resources\CredentialResource\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class CredentialFormPresenter
{

    public static function appName(): TextInput
    {
        $isAdmin = auth()->user()?->isAdmin() ?? false;
        return TextInput::make('app_name')
            ->label(__('resources/credential/strings.fields.app_name'))
            ->required()
            ->maxLength(255)
            ->columnSpan($isAdmin ? 1 : 'full')
            ->validationMessages([
                'required' => __('resources/credential/strings.validation.app_name_required'),
                'max' => __('resources/credential/strings.validation.app_name_max'),
            ]);
    }

    public static function link(): TextInput
    {
        return TextInput::make('link')
            ->label(__('resources/credential/strings.fields.link'))
            ->url()
            ->maxLength(500)
            ->nullable()
            ->columnSpanFull()
            ->extraAttributes(['dir' => 'ltr'])
            ->placeholder('https://www.example.com/')
            ->helperText(__('resources/credential/strings.form.link_helper'))
            ->validationMessages([
                'url' => __('resources/credential/strings.validation.link_url'),
                'max' => __('resources/credential/strings.validation.link_max'),
            ]);
    }

    public static function note(): Textarea
    {
        return Textarea::make('note')
            ->label(__('resources/credential/strings.fields.note'))
            ->nullable()
            ->hint('⚠️')
            ->placeholder(__('resources/credential/strings.fields.note_placeholder'),)
            ->rows(3)
            ->columnSpanFull();
    }

    public static function password(): TextInput
    {
        return TextInput::make('password')
            ->label(__('resources/credential/strings.fields.password'))
            ->password()
            ->revealable()
            ->required()
            ->placeholder(__('resources/credential/strings.fields.note_password'),)
            ->validationMessages([
                'required' => __('resources/credential/strings.validation.password_required'),
            ]);
    }

    public static function userId(): Select
    {
        $isAdmin = auth()->user()?->isAdmin() ?? false;
        return Select::make('user_id')
            ->label(__('resources/credential/strings.fields.user'))
            ->relationship('user', 'name')
            ->searchable()
            ->preload()
            ->native(false)
            ->required()
            ->columnSpan($isAdmin ? 1 : 'full')
            ->visible(fn() => $isAdmin)
            ->validationMessages([
                'required' => __('resources/credential/strings.validation.user_required'),
            ]);
    }

    public static function username(): TextInput
    {
        return TextInput::make('username')
            ->label(__('resources/credential/strings.fields.username'))
            ->required()
            ->maxLength(255)
            ->validationMessages([
                'required' => __('resources/credential/strings.validation.username_required'),
                'max' => __('resources/credential/strings.validation.username_max'),
            ]);
    }
}
