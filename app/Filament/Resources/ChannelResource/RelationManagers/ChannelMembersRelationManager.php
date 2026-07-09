<?php

namespace App\Filament\Resources\ChannelResource\RelationManagers;

use App\Models\ChannelMessage;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ChannelMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'memberUsers';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function getModelLabel(): string
    {
        return __('resources/channel/strings.fields.user');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/channel/strings.fields.members_count');
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/channel/strings.fields.member');
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        $ownerId = (int)$this->getOwnerRecord()->owner_id;

        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->addSelect([
                'last_read_message_body' => ChannelMessage::select('body')
                    ->whereColumn('channel_messages.id', 'channel_members.last_read_message_id')
                    ->limit(1)
            ]))
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('resources/channel/strings.fields.user'))
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('joined_at')
                    ->label(__('resources/channel/strings.fields.joined_at'))
                    ->formatStateUsing(fn(mixed $state): string => $state ? toJalali($state, 'Y/m/d H:i') : '—')
                    ->alignCenter()
                    ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;']),

                TextColumn::make('pivot.entered_at')
                    ->label(__('resources/channel/strings.fields.entered_at'))
                    ->formatStateUsing(fn(mixed $state): string => $state ? toJalali($state, 'Y/m/d H:i') : 'هنوز وارد نشده')
                    ->alignCenter()
                    ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
                    ->toggleable(),

                TextColumn::make('last_read_message_body')
                    ->label(__('resources/channel/strings.fields.last_read_message'))
                    ->html()
                    ->words(25)
                    ->tooltip(function (?string $state): ?string {
                        $plain = strip_tags((string)$state);
                        return Str::words($plain, 25) !== $plain ? $plain : null;
                    })
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('pivot.updated_at')
                    ->label(__('resources/channel/strings.fields.updated_at'))
                    ->formatStateUsing(fn(mixed $state): string => $state ? toJalali($state, 'Y/m/d H:i') : '—')
                    ->alignCenter()
                    ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('افزودن عضو')
                    ->icon('heroicon-o-user-plus')
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['name'])
                    ->mutateDataUsing(fn(array $data): array => array_merge($data, [
                        'joined_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]))
            ])
            ->recordActions([
                DetachAction::make()
                    ->label('حذف عضو')
                    ->icon('heroicon-o-x-mark')
                    ->iconButton()
                    ->visible(fn(Model $record): bool => (int)$record->id !== $ownerId)
            ], position: RecordActionsPosition::AfterCells)
            ->defaultSort('channel_members.created_at', 'desc')
            ->emptyStateIcon('heroicon-o-bookmark');
    }
}
