<?php

namespace App\Filament\Resources\ContactResource\Schemas;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;

class ContactTablePresenter
{
    public static function body(): TextColumn
    {
        return TextColumn::make('body')
            ->label(__('resources/contact/strings.fields.body'))
            ->limit(80)
            ->html()
            ->tooltip(fn($state) => strlen($state ?? '') > 80 ? $state : null)
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/contact/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }


    public static function deletedAt(): TextColumn
    {
        return TextColumn::make('deleted_at')
            ->label(__('resources/contact/strings.fields.deleted_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function editedFilter(): TernaryFilter
    {
        return TernaryFilter::make('is_edited')
            ->label(__('resources/contact/strings.filters.is_edited'))
            ->trueLabel(__('resources/contact/strings.filters.edited'))
            ->falseLabel(__('resources/contact/strings.filters.not_edited'));
    }

    public static function hasReply(): IconColumn
    {
        return IconColumn::make('reply_to_id')
            ->label(__('resources/contact/strings.fields.has_reply'))
            ->getStateUsing(fn($record) => filled($record->reply_to_id))
            ->boolean()
            ->trueIcon('heroicon-o-arrow-uturn-left')
            ->falseIcon('heroicon-o-minus')
            ->trueColor('info')
            ->falseColor('gray')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function hasReplyFilter(): TernaryFilter
    {
        return TernaryFilter::make('has_reply')
            ->label(__('resources/contact/strings.filters.has_reply'))
            ->trueLabel(__('resources/contact/strings.filters.is_reply'))
            ->falseLabel(__('resources/contact/strings.filters.not_reply'))
            ->queries(
                true: fn($q) => $q->whereNotNull('reply_to_id'),
                false: fn($q) => $q->whereNull('reply_to_id'),
            );
    }

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label('ID')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function isEdited(): IconColumn
    {
        return IconColumn::make('is_edited')
            ->label(__('resources/contact/strings.fields.is_edited'))
            ->boolean()
            ->trueIcon('heroicon-o-pencil-square')
            ->falseIcon('heroicon-o-minus')
            ->trueColor('warning')
            ->falseColor('gray')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function prunableWarning(): TextColumn
    {
        return TextColumn::make('prune_status')
            ->label(__('resources/contact/strings.fields.prune_status'))
            ->getStateUsing(fn($record) => $record->pruneStatusText())
            ->color(fn($record) => $record->pruneStatusColor())
            ->badge()
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: true);
    }


    public static function pruningSoonFilter(): Filter
    {
        return Filter::make('pruning_soon')
            ->label(__('resources/contact/strings.filters.pruning_soon'))
            ->query(fn(Builder $query) => $query
                ->whereNotNull('deleted_at')
                ->where('deleted_at', '<=', now()->subDays(30))
            )
            ->toggle();
    }

    public static function readAt(): TextColumn
    {
        return TextColumn::make('read_at')
            ->label(__('resources/contact/strings.fields.read_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->placeholder('—')
            ->color(fn($state) => $state ? 'success' : 'gray')
            ->icon(fn($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-clock')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function recipient(): TextColumn
    {
        return TextColumn::make('recipient.name')
            ->label(__('resources/contact/strings.fields.recipient'))
            ->searchable()
            ->sortable()
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function recipientFilter(): SelectFilter
    {
        return SelectFilter::make('recipient_id')
            ->label(__('resources/contact/strings.fields.recipient'))
            ->relationship('recipient', 'name')
            ->searchable()
            ->preload();
    }

    public static function recipientGroup(): Group
    {
        return Group::make('recipient.name')
            ->label(__('resources/contact/strings.fields.recipient'))
            ->collapsible();
    }

    public static function sender(): TextColumn
    {
        return TextColumn::make('sender.name')
            ->label(__('resources/contact/strings.fields.sender'))
            ->searchable()
            ->sortable()
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function senderFilter(): SelectFilter
    {
        return SelectFilter::make('sender_id')
            ->label(__('resources/contact/strings.fields.sender'))
            ->relationship('sender', 'name')
            ->searchable()
            ->preload();
    }

    public static function senderGroup(): Group
    {
        return Group::make('sender.name')
            ->label(__('resources/contact/strings.fields.sender'))
            ->collapsible();
    }

    public static function unreadFilter(): TernaryFilter
    {
        return TernaryFilter::make('read_at')
            ->label(__('resources/contact/strings.filters.read_status'))
            ->trueLabel(__('resources/contact/strings.filters.read'))
            ->falseLabel(__('resources/contact/strings.filters.unread'))
            ->queries(
                true: fn($q) => $q->whereNotNull('read_at'),
                false: fn($q) => $q->whereNull('read_at'),
            );
    }
}
