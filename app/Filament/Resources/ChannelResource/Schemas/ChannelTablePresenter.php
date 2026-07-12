<?php

namespace App\Filament\Resources\ChannelResource\Schemas;

use App\Enums\ChannelType;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;

class ChannelTablePresenter
{
    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label('ID')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function name(): TextColumn
    {
        return TextColumn::make('name')
            ->label(__('resources/channel/strings.fields.name'))
            ->searchable()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function slug(): TextColumn
    {
        return TextColumn::make('slug')
            ->label(__('resources/channel/strings.fields.slug'))
            ->searchable()
            ->sortable()
            ->copyable()
            ->copyMessage(__('resources/channel/strings.notifications.slug_copied'))
            ->copyMessageDuration(1500)
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignCenter()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function type(): TextColumn
    {
        return TextColumn::make('type')
            ->label(__('resources/channel/strings.fields.type'))
            ->badge()
            ->getStateUsing(fn($record) => $record->type?->getLabel())
            ->color(fn($record) => $record->type?->getColor())
            ->icon(fn($record) => $record->type?->getIcon())
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function membersCount(): TextColumn
    {
        return TextColumn::make('members_count')
            ->label(__('resources/channel/strings.fields.members_count'))
            ->badge()
            ->color('info')
            ->icon('heroicon-o-users')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function messagesCount(): TextColumn
    {
        return TextColumn::make('messages_count')
            ->label(__('resources/channel/strings.fields.messages_count'))
            ->badge()
            ->color('gray')
            ->icon('heroicon-o-chat-bubble-left')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/channel/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function deletedAt(): TextColumn
    {
        return TextColumn::make('deleted_at')
            ->label(__('resources/channel/strings.fields.deleted_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function typeFilter(): SelectFilter
    {
        return SelectFilter::make('type')
            ->label(__('resources/channel/strings.filters.type'))
            ->options(collect(ChannelType::cases())
                ->mapWithKeys(fn(ChannelType $t) => [$t->value => $t->getLabel()]));
    }

    public static function prunableWarning(): TextColumn
    {
        return TextColumn::make('prune_status')
            ->label(__('resources/channel/strings.fields.prune_status'))
            ->getStateUsing(fn($record) => $record->pruneStatusText())
            ->color(fn($record) => $record->pruneStatusColor())
            ->badge()
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function pruningSoonFilter(): Filter
    {
        return Filter::make('pruning_soon')
            ->label(__('resources/channel/strings.filters.pruning_soon'))
            ->query(fn(Builder $query) => $query
                ->whereNotNull('deleted_at')
                ->where('deleted_at', '<=', now()->subDays(30))
            )
            ->toggle();
    }

    public static function typeGroup(): Group
    {
        return Group::make('type')
            ->label(__('resources/channel/strings.fields.type'))
            ->collapsible();
    }
}
