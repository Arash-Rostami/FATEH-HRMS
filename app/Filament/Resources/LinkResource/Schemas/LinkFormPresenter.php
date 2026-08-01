<?php

namespace App\Filament\Resources\LinkResource\Schemas;

use Closure;
use App\Filament\Resources\LinkResource\Enums\LinkType;
use App\Traits\FilamentFormDivider;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class LinkFormPresenter
{
    use FilamentFormDivider;

    private const URL_HOST_REGEX = '/^((?:mailto|tel):[A-Za-z0-9._~:\/?#\[\]@!$&()*+,;=%-]+|[a-zA-Z][a-zA-Z0-9+.-]*:\/\/[A-Za-z0-9._~:\/?#\[\]@!$&()*+,;=%-]+|[A-Za-z0-9][A-Za-z0-9._-]+(:[0-9]{1,5})?([\/?#][A-Za-z0-9._~:\/?#@!$&()*+,;=%-]*)?)$/';

    public static function companyIps(): TagsInput
    {
        return TagsInput::make('extra')
            ->label(__('resources/link/strings.fields.extra'))
            ->placeholder(__('resources/link/strings.fields.extra_placeholder'))
            ->helperText(__('resources/link/strings.fields.extra_hint'))
            ->splitKeys(['Enter', ',', ' '])
            ->visible(fn (Get $get): bool => filled($get('internal_url')))
            ->requiredWith('internal_url')
            ->dehydrateStateUsing(fn (mixed $state): mixed => is_array($state)
                ? array_values(array_filter(array_map('trim', $state), fn (string $v): bool => $v !== ''))
                : $state
            )
            ->rule(self::extraRequiresInternalUrl());
    }

    private static function extraRequiresInternalUrl(): Closure
    {
        return fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
            $evaluateState = fn (mixed $state): array => is_array($state)
                ? array_values(array_filter(array_map('trim', $state), fn (string $v): bool => $v !== ''))
                : [];

            $isExtraBlank = empty($evaluateState($attribute === 'extra' ? $value : $get('extra')));
            $isUrlBlank = blank($attribute === 'internal_url' ? $value : $get('internal_url'));

            if ($isExtraBlank !== $isUrlBlank) {
                $fail(__('resources/link/strings.validation.extra_requires_internal_url'));
            }
        };
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
                fn (TemporaryUploadedFile $file): string => Str::random(12) . '-' . time() . '.' . $file->getClientOriginalExtension()
            )
            ->image()
            ->imagePreviewHeight('80')
            ->required()
            ->helperText(__('resources/link/strings.hints.icon'));
    }

    public static function iconDescription(): Textarea
    {
        return Textarea::make('icon_description')
            ->label(__('resources/link/strings.fields.icon_description'))
            ->rows(2)
            ->maxLength(500)
            ->helperText(__('resources/link/strings.hints.icon_description'));
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
                fn (TemporaryUploadedFile $file): string => Str::random(12) . '-' . time() . '.' . $file->getClientOriginalExtension()
            )
            ->image()
            ->imagePreviewHeight('120')
            ->required()
            ->helperText(__('resources/link/strings.hints.image'));
    }

    public static function imageDescription(): Textarea
    {
        return Textarea::make('image_description')
            ->label(__('resources/link/strings.fields.image_description'))
            ->rows(2)
            ->maxLength(500)
            ->helperText(__('resources/link/strings.hints.image_description'));
    }

    public static function internalUrl(): TextInput
    {
        return TextInput::make('internal_url')
            ->label(__('resources/link/strings.fields.internal_url'))
            ->rules(['string', 'regex:' . self::URL_HOST_REGEX])
            ->maxLength(2048)
            ->live()
            ->helperText(__('resources/link/strings.fields.internal_url_hint'))
            ->rule(self::extraRequiresInternalUrl());
    }

    public static function linkType(): Radio
    {
        return Radio::make('link')
            ->label(__('resources/link/strings.fields.link_type'))
            ->options(LinkType::class)
            ->required()
            ->live()
            ->inline()
            ->helperText(__('resources/link/strings.hints.link_type'));
    }

    public static function sequence(): Select
    {
        return Select::make('sequence')
            ->label(__('resources/link/strings.fields.sequence'))
            ->options(fn (): array => array_combine(range(0, 100), range(0, 100)))
            ->default(0)
            ->searchable()
            ->native(false)
            ->helperText(__('resources/link/strings.fields.sequence_hint'));
    }

    public static function url(): TextInput
    {
        return TextInput::make('url')
            ->label(__('resources/link/strings.fields.url'))
            ->required()
            ->rules(['string', 'regex:' . self::URL_HOST_REGEX])
            ->maxLength(2048)
            ->placeholder('//:https')
            ->helperText(__('resources/link/strings.fields.url_hint'));
    }

    public static function urlDescription(): Textarea
    {
        return Textarea::make('url_description')
            ->label(__('resources/link/strings.fields.url_description'))
            ->rows(2)
            ->maxLength(1000)
            ->helperText(__('resources/link/strings.hints.url_description'));
    }

    public static function urlTitle(): TextInput
    {
        return TextInput::make('url_title')
            ->label(__('resources/link/strings.fields.url_title'))
            ->required()
            ->maxLength(255)
            ->helperText(__('resources/link/strings.hints.url_title'));
    }
}
