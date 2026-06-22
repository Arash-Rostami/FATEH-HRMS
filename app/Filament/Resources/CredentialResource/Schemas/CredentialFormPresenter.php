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
            ->validationMessages([
                'required' => __('resources/credential/strings.validation.generic.required'),
                'unique' => __('resources/credential/strings.validation.generic.unique'),
                'max' => __('resources/credential/strings.validation.generic.max'),
                'min' => __('resources/credential/strings.validation.generic.min'),
                'email' => __('resources/credential/strings.validation.generic.email'),
                'numeric' => __('resources/credential/strings.validation.generic.numeric'),
                'mimes' => __('resources/credential/strings.validation.generic.mimes'),
                'url' => __('resources/credential/strings.validation.generic.url'),
                'in' => __('resources/credential/strings.validation.generic.in'),
                'exists' => __('resources/credential/strings.validation.generic.exists')
            ])
            ->label(__('resources/credential/strings.fields.app_name'))
            ->required()
            ->maxLength(255)
            ->columnSpan($isAdmin ? 1 : 'full')
            ->helperText(__('resources/credential/strings.hints.app_name'))
            ->validationMessages([
                'required' => __('resources/credential/strings.validation.app_name_required'),
                'max' => __('resources/credential/strings.validation.app_name_max'),
            ]);
    }

    public static function link(): TextInput
    {
        return TextInput::make('link')
            ->validationMessages([
                'required' => __('resources/credential/strings.validation.generic.required'),
                'unique' => __('resources/credential/strings.validation.generic.unique'),
                'max' => __('resources/credential/strings.validation.generic.max'),
                'min' => __('resources/credential/strings.validation.generic.min'),
                'email' => __('resources/credential/strings.validation.generic.email'),
                'numeric' => __('resources/credential/strings.validation.generic.numeric'),
                'mimes' => __('resources/credential/strings.validation.generic.mimes'),
                'url' => __('resources/credential/strings.validation.generic.url'),
                'in' => __('resources/credential/strings.validation.generic.in'),
                'exists' => __('resources/credential/strings.validation.generic.exists')
            ])
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
            ->validationMessages([
                'required' => __('resources/credential/strings.validation.generic.required'),
                'unique' => __('resources/credential/strings.validation.generic.unique'),
                'max' => __('resources/credential/strings.validation.generic.max'),
                'min' => __('resources/credential/strings.validation.generic.min'),
                'email' => __('resources/credential/strings.validation.generic.email'),
                'numeric' => __('resources/credential/strings.validation.generic.numeric'),
                'mimes' => __('resources/credential/strings.validation.generic.mimes'),
                'url' => __('resources/credential/strings.validation.generic.url'),
                'in' => __('resources/credential/strings.validation.generic.in'),
                'exists' => __('resources/credential/strings.validation.generic.exists')
            ])
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
            ->validationMessages([
                'required' => __('resources/credential/strings.validation.generic.required'),
                'unique' => __('resources/credential/strings.validation.generic.unique'),
                'max' => __('resources/credential/strings.validation.generic.max'),
                'min' => __('resources/credential/strings.validation.generic.min'),
                'email' => __('resources/credential/strings.validation.generic.email'),
                'numeric' => __('resources/credential/strings.validation.generic.numeric'),
                'mimes' => __('resources/credential/strings.validation.generic.mimes'),
                'url' => __('resources/credential/strings.validation.generic.url'),
                'in' => __('resources/credential/strings.validation.generic.in'),
                'exists' => __('resources/credential/strings.validation.generic.exists')
            ])
            ->label(__('resources/credential/strings.fields.password'))
            ->password()
            ->revealable()
            ->required()
            ->placeholder(__('resources/credential/strings.fields.note_password'),)
            ->helperText(__('resources/credential/strings.hints.password'))
            ->validationMessages([
                'required' => __('resources/credential/strings.validation.password_required'),
            ]);
    }

    public static function userId(): Select
    {
        $isAdmin = auth()->user()?->hasElevatedRole() ?? false;
        return Select::make('user_id')
            ->validationMessages([
                'required' => __('resources/credential/strings.validation.generic.required'),
                'unique' => __('resources/credential/strings.validation.generic.unique'),
                'max' => __('resources/credential/strings.validation.generic.max'),
                'min' => __('resources/credential/strings.validation.generic.min'),
                'email' => __('resources/credential/strings.validation.generic.email'),
                'numeric' => __('resources/credential/strings.validation.generic.numeric'),
                'mimes' => __('resources/credential/strings.validation.generic.mimes'),
                'url' => __('resources/credential/strings.validation.generic.url'),
                'in' => __('resources/credential/strings.validation.generic.in'),
                'exists' => __('resources/credential/strings.validation.generic.exists')
            ])
            ->label(__('resources/credential/strings.fields.user'))
            ->relationship('user', 'name')
            ->searchable()
            ->preload()
            ->native(false)
            ->required()
            ->columnSpan($isAdmin ? 1 : 'full')
            ->visible(fn() => $isAdmin)
            ->helperText(__('resources/credential/strings.hints.user_id'))
            ->validationMessages([
                'required' => __('resources/credential/strings.validation.user_required'),
            ]);
    }

    public static function username(): TextInput
    {
        return TextInput::make('username')
            ->validationMessages([
                'required' => __('resources/credential/strings.validation.generic.required'),
                'unique' => __('resources/credential/strings.validation.generic.unique'),
                'max' => __('resources/credential/strings.validation.generic.max'),
                'min' => __('resources/credential/strings.validation.generic.min'),
                'email' => __('resources/credential/strings.validation.generic.email'),
                'numeric' => __('resources/credential/strings.validation.generic.numeric'),
                'mimes' => __('resources/credential/strings.validation.generic.mimes'),
                'url' => __('resources/credential/strings.validation.generic.url'),
                'in' => __('resources/credential/strings.validation.generic.in'),
                'exists' => __('resources/credential/strings.validation.generic.exists')
            ])
            ->label(__('resources/credential/strings.fields.username'))
            ->required()
            ->maxLength(255)
            ->helperText(__('resources/credential/strings.hints.username'))
            ->validationMessages([
                'required' => __('resources/credential/strings.validation.username_required'),
                'max' => __('resources/credential/strings.validation.username_max'),
            ]);
    }
}
