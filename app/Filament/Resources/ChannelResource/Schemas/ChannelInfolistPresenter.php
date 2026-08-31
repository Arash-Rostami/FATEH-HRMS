<?php

namespace App\Filament\Resources\ChannelResource\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\IconPosition;

class ChannelInfolistPresenter
{
    public static function name(): TextEntry
    {
        return TextEntry::make('name')
            ->label(__('resources/channel/strings.fields.name'))
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->icon('heroicon-o-chat-bubble-left-right');
    }

    public static function slug(): TextEntry
    {
        return TextEntry::make('slug')
            ->label(__('resources/channel/strings.fields.slug'))
            ->icon('heroicon-o-hashtag')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;']);
    }

    public static function description(): TextEntry
    {
        return TextEntry::make('description')
            ->label(__('resources/channel/strings.fields.description'))
            ->placeholder('—')
            ->extraAttributes(['dir' => 'auto', 'style' => 'white-space: pre-wrap; unicode-bidi: isolate;'])
            ->columnSpanFull();
    }

    public static function type(): IconEntry
    {
        return IconEntry::make('type')
            ->label(__('resources/channel/strings.fields.type'))
            ->icon(fn($state) => $state?->getIcon())
            ->color(fn($state) => $state?->getColor());
    }

    public static function owner(): TextEntry
    {
        return TextEntry::make('owner.name')
            ->label(__('resources/channel/strings.fields.owner'))
            ->placeholder('—')
            ->icon('heroicon-o-user');
    }

    public static function membersCount(): TextEntry
    {
        return TextEntry::make('members_count')
            ->label(__('resources/channel/strings.fields.members_count'))
            ->badge()
            ->color('info')
            ->icon('heroicon-o-users');
    }

    public static function messagesCount(): TextEntry
    {
        return TextEntry::make('messages_count')
            ->label(__('resources/channel/strings.fields.messages_count'))
            ->badge()
            ->color('gray')
            ->icon('heroicon-o-chat-bubble-left');
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/channel/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->color('gray')
            ->icon('heroicon-o-clock');
    }

    public static function updatedAt(): TextEntry
    {
        return TextEntry::make('updated_at')
            ->label(__('resources/channel/strings.fields.updated_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->color('gray')
            ->icon('heroicon-o-arrow-path');
    }

    public static function deletedAt(): TextEntry
    {
        return TextEntry::make('deleted_at')
            ->label(__('resources/channel/strings.fields.deleted_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : null)
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->placeholder('—')
            ->color('danger')
            ->icon('heroicon-o-trash');
    }

    public static function prunableWarning(): TextEntry
    {
        return TextEntry::make('prune_info')
            ->label(__('resources/channel/strings.fields.prune_status'))
            ->getStateUsing(fn($record) => $record->pruneStatusText())
            ->color(fn($record) => $record->pruneStatusColor())
            ->badge()
            ->hidden(fn($record) => !$record->deleted_at);
    }
}