<?php

namespace App\Filament\Resources\CredentialResource\Schemas;

use App\Traits\FilamentFormDivider;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class CredentialFormPresenter
{
    use FilamentFormDivider;

    public static function appName(): TextInput
    {
        $isAdmin = auth()->user()?->hasElevatedRole() ?? false;
        return TextInput::make('app_name')
            ->label(__('resources/credential/strings.fields.app_name'))
            ->required()
            ->maxLength(255)
            ->columnSpan($isAdmin ? 1 : 'full')
            ->helperText(__('resources/credential/strings.hints.app_name'))
            ;
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
            ;
    }

    public static function note(): Textarea
    {
        return Textarea::make('note')
            ->label(__('resources/credential/strings.fields.note'))
            ->nullable()
            ->hint('⚠️')
            ->placeholder(__('resources/credential/strings.fields.note_placeholder'),)
            ->rows(3)
            ->columnSpanFull()
            ->helperText(__('resources/credential/strings.hints.note'));
    }

    public static function password(): TextInput
    {
        return TextInput::make('password')
            ->label(__('resources/credential/strings.fields.password'))
            ->password()
            ->revealable()
            ->required()
            ->placeholder(__('resources/credential/strings.fields.note_password'),)
            ->helperText(__('resources/credential/strings.hints.password'))
            ;
    }

    public static function userId(): Select
    {
        $isAdmin = auth()->user()?->hasElevatedRole() ?? false;
        return Select::make('user_id')
            ->label(__('resources/credential/strings.fields.user'))
            ->relationship('user', 'name')
            ->searchable()
            ->preload()
            ->native(false)
            ->required()
            ->columnSpan($isAdmin ? 1 : 'full')
            ->visible(fn() => $isAdmin)
            ->helperText(__('resources/credential/strings.hints.user_id'))
            ;
    }

    public static function username(): TextInput
    {
        return TextInput::make('username')
            ->label(__('resources/credential/strings.fields.username'))
            ->required()
            ->maxLength(255)
            ->helperText(__('resources/credential/strings.hints.username'))
            ;
    }
}
