<?php

namespace App\Filament\Resources\ReleaseRequestResource\Schemas;

use App\Enums\ReleaseRequestStatus;
use App\Enums\ReleaseRequestType;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Model;

class ReleaseRequestFormPresenter
{
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

    public static function status(): Select
    {
        return Select::make('status')
            ->label(__('resources/release_request/strings.fields.status'))
            ->options(ReleaseRequestStatus::options())
            ->required()
            ->default(ReleaseRequestStatus::Open->value);
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
