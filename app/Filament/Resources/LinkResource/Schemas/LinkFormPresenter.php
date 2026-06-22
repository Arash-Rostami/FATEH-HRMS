<?php

namespace App\Filament\Resources\LinkResource\Schemas;

use App\Filament\Resources\LinkResource\Enums\LinkType;
use App\Traits\FilamentFormDivider;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class LinkFormPresenter
{
    use FilamentFormDivider;

    public static function companyIps(): TagsInput
    {
        return TagsInput::make('extra')
            ->label(__('resources/link/strings.fields.extra'))
            ->placeholder(__('resources/link/strings.fields.extra_placeholder'))
            ->helperText(__('resources/link/strings.fields.extra_hint'))
            ->splitKeys(['Enter', ',', ' '])
            ->visible(fn($get) => filled($get('internal_url')))
            ->nullable();
    }

    public static function icon(): FileUpload
    {
        return FileUpload::make('icon')
            ->label(__('resources/link/strings.fields.icon'))
            ->disk('public')
            ->directory('link/icon')
            ->maxSize(512)
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'])
            ->getUploadedFileNameForStorageUsing(
                fn(TemporaryUploadedFile $file): string => Str::random(12) . '-' . time() . '.' . $file->getClientOriginalExtension()
            )
            ->image()
            ->imagePreviewHeight('80')
            ->required()
            ->helperText(__('resources/link/strings.hints.icon'))
            ->validationMessages([
                'required' => __('resources/link/strings.validation.required'),
                'max' => __('resources/link/strings.validation.max_file', ['max' => 512,
                'mimes' => __('resources/link/strings.validation.icon.mimes')
            ]),
                'mimetypes' => __('resources/link/strings.validation.mimetypes'),
                'image' => __('resources/link/strings.validation.image'),
            ]);
    }

    public static function iconDescription(): Textarea
    {
        return Textarea::make('icon_description')
            ->label(__('resources/link/strings.fields.icon_description'))
            ->rows(2)
            ->maxLength(500)
            ->helperText(__('resources/link/strings.hints.icon_description'))
            ->validationMessages([
                'max' => __('resources/link/strings.validation.max_string', ['max' => 500]),
            ]);
    }

    public static function image(): FileUpload
    {
        return FileUpload::make('image')
            ->label(__('resources/link/strings.fields.image'))
            ->disk('public')
            ->directory('link/image')
            ->maxSize(1024)
            ->downloadable()
            ->openable()
            ->previewable()
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'])
            ->getUploadedFileNameForStorageUsing(
                fn(TemporaryUploadedFile $file): string => Str::random(12) . '-' . time() . '.' . $file->getClientOriginalExtension()
            )
            ->image()
            ->imagePreviewHeight('120')
            ->required()
            ->helperText(__('resources/link/strings.hints.image'))
            ->validationMessages([
                'required' => __('resources/link/strings.validation.required'),
                'max' => __('resources/link/strings.validation.max_file', ['max' => 1024,
                'mimes' => __('resources/link/strings.validation.image.mimes')
            ]),
                'mimetypes' => __('resources/link/strings.validation.mimetypes'),
                'image' => __('resources/link/strings.validation.image'),
            ]);
    }

    public static function imageDescription(): Textarea
    {
        return Textarea::make('image_description')
            ->label(__('resources/link/strings.fields.image_description'))
            ->rows(2)
            ->maxLength(500)
            ->helperText(__('resources/link/strings.hints.image_description'))
            ->validationMessages([
                'max' => __('resources/link/strings.validation.max_string', ['max' => 500]),
            ]);
    }

    public static function internalUrl(): TextInput
    {
        return TextInput::make('internal_url')
            ->label(__('resources/link/strings.fields.internal_url'))
            ->url()
            ->maxLength(2048)
            ->live()
            ->helperText(__('resources/link/strings.fields.internal_url_hint'))
            ->nullable()
            ->validationMessages([
                'url' => __('resources/link/strings.validation.url'),
                'max' => __('resources/link/strings.validation.max_string', ['max' => 2048]),
            ]);
    }

    public static function linkType(): Radio
    {
        return Radio::make('link')
            ->label(__('resources/link/strings.fields.link_type'))
            ->options(LinkType::class)
            ->required()
            ->live()
            ->inline()
            ->helperText(__('resources/link/strings.hints.link_type'))
            ->validationMessages([
                'required' => __('resources/link/strings.validation.required'),
                'in' => __('resources/link/strings.validation.link.in')
            ]);
    }

    public static function sequence(): Select
    {
        return Select::make('sequence')
            ->label(__('resources/link/strings.fields.sequence'))
            ->options(fn() => collect(range(0, 100))->mapWithKeys(fn($i) => [$i => $i])->toArray())
            ->default(0)
            ->searchable()
            ->native(false)
            ->helperText(__('resources/link/strings.fields.sequence_hint'))
            ->validationMessages([
                'in' => __('resources/link/strings.validation.sequence.in')
            ]);
    }

    public static function url(): TextInput
    {
        return TextInput::make('url')
            ->label(__('resources/link/strings.fields.url'))
            ->required()
            ->url()
            ->maxLength(2048)
            ->placeholder('//:https')
            ->helperText(__('resources/link/strings.fields.url_hint'))
            ->validationMessages([
                'required' => __('resources/link/strings.validation.required'),
                'url' => __('resources/link/strings.validation.url'),
                'max' => __('resources/link/strings.validation.max_string', ['max' => 2048]),
            ]);
    }

    public static function urlDescription(): Textarea
    {
        return Textarea::make('url_description')
            ->label(__('resources/link/strings.fields.url_description'))
            ->rows(2)
            ->maxLength(1000)
            ->helperText(__('resources/link/strings.hints.url_description'))
            ->validationMessages([
                'max' => __('resources/link/strings.validation.max_string', ['max' => 1000]),
            ]);
    }

    public static function urlTitle(): TextInput
    {
        return TextInput::make('url_title')
            ->label(__('resources/link/strings.fields.url_title'))
            ->required()
            ->maxLength(255)
            ->helperText(__('resources/link/strings.hints.url_title'))
            ->validationMessages([
                'required' => __('resources/link/strings.validation.required'),
                'max' => __('resources/link/strings.validation.max_string', ['max' => 255]),
            ]);
    }
}
