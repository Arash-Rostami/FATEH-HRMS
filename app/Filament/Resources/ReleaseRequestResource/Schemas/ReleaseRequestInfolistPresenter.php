<?php

namespace App\Filament\Resources\ReleaseRequestResource\Schemas;

use App\Enums\ReleaseRequestStatus;
use App\Enums\ReleaseRequestType;
use App\Traits\FilamentFormDivider;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\IconPosition;
use Illuminate\Database\Eloquent\Model;

class ReleaseRequestInfolistPresenter
{
    use FilamentFormDivider;

    public static function attachments(): RepeatableEntry
    {
        return RepeatableEntry::make('attachments')
            ->label(__('resources/release_request/strings.fields.attachments'))
            ->schema([
                TextEntry::make('path')
                    ->hiddenLabel()
                    ->formatStateUsing(fn($state) => __('resources/release_request/strings.fields.view_file'))
                    ->url(fn($state) => $state ? asset('storage/' . $state) : null)
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->placeholder('—'),
            ])
            ->columnSpanFull();
    }

    public static function response(): TextEntry
    {
        return TextEntry::make('response')
            ->label(__('resources/release_request/strings.fields.response'))
            ->color(fn(?Model $record) => $record?->status === ReleaseRequestStatus::Rejected->value ? 'danger' : 'primary')
            ->extraAttributes(['dir' => 'auto', 'style' => 'white-space: pre-wrap; unicode-bidi: isolate;'])
            ->columnSpanFull()
            ->visible(fn(?Model $record) => filled($record?->response))
            ->placeholder('-');
    }

    public static function body(): TextEntry
    {
        return TextEntry::make('body')
            ->label(__('resources/release_request/strings.fields.body'))
            ->html()
            ->formatStateUsing(fn(?string $state): string => $state === null ? '' : nl2br(e($state)))
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->columnSpanFull()
            ->placeholder('-');
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/release_request/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '-')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->icon('heroicon-o-clock')
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
            ->icon('heroicon-o-hashtag')
            ->copyable()
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
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
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
            ->icon('heroicon-o-user')
            ->placeholder('-');
    }
}
