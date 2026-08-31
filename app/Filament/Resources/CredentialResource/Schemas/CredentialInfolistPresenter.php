<?php

namespace App\Filament\Resources\CredentialResource\Schemas;

use App\Models\Credential;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\TextSize;

class CredentialInfolistPresenter
{
    public static function appName(): TextEntry
    {
        return TextEntry::make('app_name')
            ->label(__('resources/credential/strings.fields.app_name'))
            ->badge()
            ->color('primary')
            ->icon('heroicon-m-squares-2x2')
            ->size(TextSize::Large)
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;']);
    }

    public static function owner(): TextEntry
    {
        return TextEntry::make('user.name')
            ->label(__('resources/credential/strings.fields.user'))
            ->icon('heroicon-m-user')
            ->color('info')
            ->placeholder('-')
            ->visible(fn () => auth()->user()?->hasElevatedRole());
    }

    public static function username(): TextEntry
    {
        return TextEntry::make('username')
            ->label(__('resources/credential/strings.fields.username'))
            ->copyable()
            ->copyMessage(__('resources/credential/strings.notifications.username_copied'))
            ->copyMessageDuration(1500)
            ->icon('heroicon-m-at-symbol');
    }

    public static function password(): TextEntry
    {
        return TextEntry::make('password')
            ->label(__('resources/credential/strings.fields.password'))
            ->copyable()
            ->copyMessage(__('resources/credential/strings.notifications.password_copied'))
            ->copyMessageDuration(1500)
            ->fontFamily(FontFamily::Mono)
            ->icon('heroicon-m-lock-closed')
            ->color('warning');
    }

    public static function link(): TextEntry
    {
        return TextEntry::make('link')
            ->label(__('resources/credential/strings.fields.link'))
            ->url(fn (Credential $record): ?string => $record->link, shouldOpenInNewTab: true)
            ->copyable()
            ->copyMessage(__('resources/credential/strings.notifications.link_copied'))
            ->copyMessageDuration(1500)
            ->icon('heroicon-m-link')
            ->color('info')
            ->columnSpanFull()
            ->placeholder('-');
    }

    public static function note(): TextEntry
    {
        return TextEntry::make('note')
            ->label(__('resources/credential/strings.fields.note'))
            ->extraAttributes(['dir' => 'auto', 'style' => 'white-space: pre-wrap; unicode-bidi: isolate;'])
            ->columnSpanFull()
            ->placeholder('-');
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/credential/strings.fields.created_at'))
            ->formatStateUsing(fn ($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->color('gray')
            ->placeholder('-');
    }

    public static function updatedAt(): TextEntry
    {
        return TextEntry::make('updated_at')
            ->label(__('resources/credential/strings.fields.updated_at'))
            ->formatStateUsing(fn ($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->color('gray')
            ->placeholder('-');
    }
}
