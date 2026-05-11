<?php

namespace App\Filament\Resources\ContactResource\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\IconPosition;

class ContactInfolistPresenter
{
    public static function body(): TextEntry
    {
        return TextEntry::make('body')
            ->label(__('resources/contact/strings.fields.body'))
            ->columnSpanFull();
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/contact/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->color('gray')
            ->icon('heroicon-o-clock');
    }

    public static function deletedAt(): TextEntry
    {
        return TextEntry::make('deleted_at')
            ->label(__('resources/contact/strings.fields.deleted_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : null)
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->placeholder('—')
            ->color('danger')
            ->icon('heroicon-o-trash');
    }

    public static function isEdited(): IconEntry
    {
        return IconEntry::make('is_edited')
            ->label(__('resources/contact/strings.fields.is_edited'))
            ->boolean()
            ->trueIcon('heroicon-o-pencil-square')
            ->falseIcon('heroicon-o-minus')
            ->trueColor('warning')
            ->falseColor('gray');
    }

    public static function prunableWarning(): TextEntry
    {
        return TextEntry::make('prune_info')
            ->label(__('resources/contact/strings.fields.prune_status'))
            ->getStateUsing(fn ($record) => $record->pruneStatusText())
            ->color(fn ($record) => $record->pruneStatusColor())
            ->badge()
            ->hidden(fn($record) => !$record->deleted_at);
    }

    public static function readAt(): TextEntry
    {
        return TextEntry::make('read_at')
            ->label(__('resources/contact/strings.fields.read_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : null)
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->placeholder(__('resources/contact/strings.fields.unread'))
            ->color(fn($state) => $state ? 'success' : 'warning')
            ->icon(fn($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-clock');
    }

    public static function recipient(): TextEntry
    {
        return TextEntry::make('recipient.name')
            ->label(__('resources/contact/strings.fields.recipient'))
            ->placeholder('—')
            ->icon('heroicon-o-user');
    }

    public static function replyTo(): TextEntry
    {
        return TextEntry::make('reply_preview')
            ->label(__('resources/contact/strings.fields.reply_to'))
            ->getStateUsing(fn($record) => $record?->replyTo
                ? '[' . ($record->replyTo->sender?->name ?? '?') . ']: ' . mb_substr(strip_tags($record->replyTo->body ?? ''), 0, 200)
                : null
            )
            ->placeholder('—')
            ->color('gray')
            ->icon('heroicon-o-arrow-uturn-left')
            ->columnSpanFull();
    }

    public static function sender(): TextEntry
    {
        return TextEntry::make('sender.name')
            ->label(__('resources/contact/strings.fields.sender'))
            ->placeholder('—')
            ->icon('heroicon-o-user');
    }

    public static function updatedAt(): TextEntry
    {
        return TextEntry::make('updated_at')
            ->label(__('resources/contact/strings.fields.updated_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->color('gray')
            ->icon('heroicon-o-arrow-path');
    }
}
