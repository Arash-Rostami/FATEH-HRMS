<?php

namespace App\Filament\Resources\ChannelResource\RelationManagers;

use App\Traits\FilamentActions;
use App\Traits\FilamentFilters;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChannelMessagesRelationManager extends RelationManager
{
    use FilamentActions, FilamentFilters;

    protected static string $relationship = 'messages';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/channel/strings.fields.messages');
    }

    public function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['sender']);
    }

    public static function getModelLabel(): string
    {
        return __('resources/channel/strings.fields.body');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/channel/strings.fields.messages_count');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),

                TextColumn::make('sender.name')
                    ->label(__('resources/channel/strings.fields.sender'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('body')
                    ->label(__('resources/channel/strings.fields.body'))
                    ->getStateUsing(fn($record) => mb_substr(strip_tags((string) ($record->body ?? '')), 0, 77))
                    ->limit(80)
                    ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: false),

                IconColumn::make('is_edited')
                    ->label(__('resources/channel/strings.fields.is_edited'))
                    ->boolean()
                    ->trueIcon('heroicon-o-pencil-square')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                TextColumn::make('deleted_at')
                    ->label(__('resources/channel/strings.fields.deleted_at'))
                    ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
                    ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('prune_status')
                    ->label(__('resources/channel/strings.fields.prune_status'))
                    ->getStateUsing(fn($record) => $record->pruneStatusText())
                    ->color(fn($record) => $record->pruneStatusColor())
                    ->badge()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('resources/channel/strings.fields.created_at'))
                    ->alignCenter()
                    ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
                    ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
                    ->sortable(),
            ])
            ->filters([
                Filter::make('pruning_soon')
                    ->label(__('resources/channel/strings.filters.pruning_soon'))
                    ->query(fn(Builder $query) => $query
                        ->whereNotNull('channel_messages.deleted_at')
                        ->where('channel_messages.deleted_at', '<=', now()->subDays(30))
                    )
                    ->toggle(),
                self::createdAtFilter(),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::restoreAction(),
                self::deleteAction()->visible(fn($record) => !$record->trashed()),
            ], RecordActionsPosition::AfterCells)
            ->defaultSort('channel_messages.created_at', 'asc')
            ->emptyStateIcon('heroicon-o-bookmark');
    }
}
