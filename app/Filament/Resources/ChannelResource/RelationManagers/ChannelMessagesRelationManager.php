<?php

namespace App\Filament\Resources\ChannelResource\RelationManagers;

use App\Traits\FilamentActions;
use App\Traits\FilamentFilters;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
        return parent::getEloquentQuery()->with(['sender']);
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
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: false),

                IconColumn::make('is_edited')
                    ->label(__('resources/channel/strings.fields.is_edited'))
                    ->boolean()
                    ->trueIcon('heroicon-o-pencil-square')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                TextColumn::make('created_at')
                    ->label(__('resources/channel/strings.fields.created_at'))
                    ->alignCenter()
                    ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
                    ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
                    ->sortable(),
            ])
            ->filters([
                self::createdAtFilter(),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->defaultSort('channel_messages.created_at', 'asc')
            ->emptyStateIcon('heroicon-o-bookmark');
    }
}
