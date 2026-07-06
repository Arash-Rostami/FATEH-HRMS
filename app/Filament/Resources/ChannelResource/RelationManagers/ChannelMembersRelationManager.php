<?php

namespace App\Filament\Resources\ChannelResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ChannelMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    public function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user']);
    }

    public static function getModelLabel(): string
    {
        return __('resources/channel/strings.fields.user');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/channel/strings.fields.members_count');
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
                TextColumn::make('user.name')
                    ->label(__('resources/channel/strings.fields.user'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('joined_at')
                    ->label(__('resources/channel/strings.fields.joined_at'))
                    ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
                    ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
                    ->sortable(),

                TextColumn::make('last_read_message_id')
                    ->label(__('resources/channel/strings.fields.last_read_message'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('resources/channel/strings.fields.created_at'))
                    ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
                    ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->emptyStateIcon('heroicon-o-bookmark');
    }
}