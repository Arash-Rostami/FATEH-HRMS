<?php

namespace App\Filament\Resources\ReleaseRequestResource\Schemas;

use App\Enums\ReleaseRequestStatus;
use App\Enums\ReleaseRequestType;
use App\Traits\StoresAttachedFiles;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ReleaseRequestFormPresenter
{
    use StoresAttachedFiles;

    public static function attachments(): Repeater
    {
        return Repeater::make('attachments')
            ->label(__('resources/release_request/strings.fields.attachments'))
            ->schema([
                FileUpload::make('path')
                    ->label(__('resources/release_request/strings.fields.file'))
                    ->disk('public')
                    ->directory('release_request/attachments')
                    ->maxSize(4096)
                    ->acceptedFileTypes(self::acceptedMimeTypes())
                    ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, callable $set) {
                        $meta = static::storeAttachment($file, 'release_request/attachments', fn($f) => self::fileName($f));

                        $set('name', $meta['name']);
                        $set('mime', $meta['mime']);
                        $set('size', $meta['size']);
                        return $meta['path'];
                    })
                    ->openable()
                    ->downloadable(),
                Hidden::make('name'),
                Hidden::make('mime'),
                Hidden::make('size'),
            ])
            ->maxItems(5)
            ->helperText(__('resources/release_request/strings.hints.attachments'))
            ->columnSpanFull();
    }

    private static function acceptedMimeTypes(): array
    {
        return [
            'image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/webp', 'image/svg+xml',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
    }

    private static function fileName(TemporaryUploadedFile $file): string
    {
        return sprintf(
            'RR-%s-%s.%s',
            now()->format('Ymd'),
            Str::random(10),
            $file->getClientOriginalExtension()
        );
    }

    public static function body(): Textarea
    {
        return Textarea::make('body')
            ->label(__('resources/release_request/strings.fields.body'))
            ->required()
            ->minLength(5)
            ->maxLength(5000)
            ->rows(6)
            ->columnSpanFull()
            ->placeholder(__('resources/release_request/strings.placeholders.body'));
    }

    public static function response(): Textarea
    {
        return Textarea::make('response')
            ->label(__('resources/release_request/strings.fields.response'))
            ->nullable()
            ->maxLength(1000)
            ->rows(3)
            ->columnSpanFull()
            ->helperText(__('resources/release_request/strings.hints.response'))
            ->placeholder(__('resources/release_request/strings.placeholders.response'));
    }

    public static function status(): Select
    {
        return Select::make('status')
            ->label(__('resources/release_request/strings.fields.status'))
            ->options(ReleaseRequestStatus::selectableOptions())
            ->required()
            ->default(ReleaseRequestStatus::Open->value)
            ->disabled(fn(?Model $record) => $record?->status === ReleaseRequestStatus::Rejected->value)
            ->validatedWhenNotDehydrated(false)
            ->hint(fn(?Model $record) => $record?->status === ReleaseRequestStatus::Rejected->value
                ? __('resources/release_request/strings.hint.status_locked_rejected')
                : null);
    }

    public static function title(): TextInput
    {
        return TextInput::make('title')
            ->label(__('resources/release_request/strings.fields.title'))
            ->required()
            ->minLength(3)
            ->maxLength(191)
            ->columnSpanFull()
            ->placeholder(__('resources/release_request/strings.placeholders.title'));
    }

    public static function type(): Select
    {
        return Select::make('type')
            ->label(__('resources/release_request/strings.fields.type'))
            ->options(ReleaseRequestType::options())
            ->required()
            ->default(ReleaseRequestType::Recommendation->value);
    }

    public static function userId(): TextEntry
    {
        return TextEntry::make('user_id')
            ->label(__('resources/release_request/strings.fields.user'))
            ->state(fn(?Model $record): string => $record?->user?->name
                ?? ($record === null ? auth()->user()?->name : null)
                ?? __('resources/release_request/strings.deleted_user'))
            ->hint(__('resources/release_request/strings.hint.user_locked'));
    }
}
