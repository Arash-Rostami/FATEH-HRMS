<?php

namespace App\Filament\Resources\ReleaseRequestResource\Schemas;

use App\Enums\ReleaseRequestStatus;
use App\Enums\ReleaseRequestType;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Model;

class ReleaseRequestInfolistPresenter
{
    public static function body(): TextEntry
    {
        return TextEntry::make('body')
            ->label(__('resources/release_request/strings.fields.body'))
            ->html()
            ->formatStateUsing(fn(?string $state): string => $state === null ? '' : nl2br(e($state)))
            ->columnSpanFull()
            ->placeholder('-');
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/release_request/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '-')
            ->color('gray')
            ->placeholder('-');
    }


    public static function id(): TextEntry
    {
        return TextEntry::make('id')
            ->label(__('resources/release_request/strings.fields.id'))
            ->formatStateUsing(fn (string $state): string => implode(' - ', array_filter([
                config('app.name') ?? config('app.company_name'),
                config('app.organization_name'),
                $state,
            ], 'strlen')))
            ->color('gray');
    }

    public static function status(): TextEntry
    {
        return TextEntry::make('status')
            ->label(__('resources/release_request/strings.fields.status'))
            ->badge()
            ->color(fn($state) => ReleaseRequestStatus::tryFrom($state)?->getColor() ?? 'gray')
            ->icon(fn($state) => ReleaseRequestStatus::tryFrom($state)?->getIcon() ?? null)
            ->formatStateUsing(fn($state) => ReleaseRequestStatus::tryFrom($state)?->getLabel() ?? $state)
            ->placeholder('-');
    }

    public static function title(): TextEntry
    {
        return TextEntry::make('title')
            ->label(__('resources/release_request/strings.fields.title'))
            ->columnSpanFull()
            ->placeholder('-');
    }

    public static function type(): TextEntry
    {
        return TextEntry::make('type')
            ->label(__('resources/release_request/strings.fields.type'))
            ->badge()
            ->color(fn($state) => ReleaseRequestType::tryFrom($state)?->getColor() ?? 'gray')
            ->icon(fn($state) => ReleaseRequestType::tryFrom($state)?->getIcon() ?? null)
            ->formatStateUsing(fn($state) => ReleaseRequestType::tryFrom($state)?->getLabel() ?? $state)
            ->placeholder('-');
    }

    public static function user(): TextEntry
    {
        return TextEntry::make('user.name')
            ->label(__('resources/release_request/strings.fields.user'))
            ->formatStateUsing(fn(?Model $record): string => $record?->user?->name
                ?? __('resources/release_request/strings.deleted_user'))
            ->placeholder('-');
    }
}
