<?php

namespace App\Filament\Resources\AdResource\Schemas;

use App\Filament\Resources\AdResource\Enums\AdGender;
use App\Traits\FilamentFormDivider;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class AdFormPresenter
{
    use FilamentFormDivider;
    public static function active(): Toggle
    {
        return Toggle::make('active')
            ->label(__('resources/ad/strings.fields.active'))
            ->default(true)
            ->onColor('success')
            ->offColor('danger')
            ->inline(false)
            ->helperText(__('resources/ad/strings.hints.active'));
    }

    public static function certificate(): Textarea
    {
        return Textarea::make('certificate')
            ->label(__('resources/ad/strings.fields.certificate'))
            ->rows(4)
            ->maxLength(2000)
            ->validationMessages([
                'max' => __('resources/ad/strings.validation.certificate.max_length'),
            ])
            ->columnSpanFull()
            ->helperText(__('resources/ad/strings.hints.certificate'));
    }

    public static function experience(): Textarea
    {
        return Textarea::make('experience')
            ->label(__('resources/ad/strings.fields.experience'))
            ->rows(4)
            ->maxLength(2000)
            ->validationMessages([
                'max' => __('resources/ad/strings.validation.experience.max_length'),
            ])
            ->columnSpanFull()
            ->helperText(__('resources/ad/strings.hints.experience'));
    }

    public static function gender(): Select
    {
        return Select::make('gender')
            ->label(__('resources/ad/strings.fields.gender'))
            ->options(AdGender::class)
            ->default(AdGender::Any->value)
            ->required()
            ->native(false)
            ->helperText(__('resources/ad/strings.hints.gender'))
            ->validationMessages([
                'required' => __('resources/ad/strings.validation.gender.required'),
            ]);
    }

    public static function link(): TextInput
    {
        return TextInput::make('link')
            ->label(__('resources/ad/strings.fields.link'))
            ->required()
            ->url()
            ->maxLength(500)
            ->helperText(__('resources/ad/strings.hints.link'))
            ->validationMessages([
                'required' => __('resources/ad/strings.validation.link.required'),
                'url' => __('resources/ad/strings.validation.link.url'),
            ]);
    }

    public static function position(): TextInput
    {
        return TextInput::make('position')
            ->label(__('resources/ad/strings.fields.position'))
            ->maxLength(255)
            ->helperText(__('resources/ad/strings.hints.position'))
            ->validationMessages([
                'max' => __('resources/ad/strings.validation.position.max_length'),
            ]);
    }

    public static function skill(): Textarea
    {
        return Textarea::make('skill')
            ->label(__('resources/ad/strings.fields.skill'))
            ->rows(4)
            ->maxLength(2000)
            ->validationMessages([
                'max' => __('resources/ad/strings.validation.skill.max_length'),
            ])
            ->columnSpanFull()
            ->helperText(__('resources/ad/strings.hints.skill'));
    }
}
