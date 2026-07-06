<?php

namespace App\Filament\Resources\ChannelResource\RelationManagers;

use App\Models\ChannelMember;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
            ->headerActions([
                Action::make('addMember')
                    ->label('افزودن عضو')
                    ->icon('heroicon-o-user-plus')
                    ->form([
                        Select::make('user_id')
                            ->label('کاربر')
                            ->options(fn() => User::getCachedActiveOptions()
                                ->except($this->getOwnerRecord()->members()->pluck('user_id')->all())
                                ->all())
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $channel = $this->getOwnerRecord();
                        ChannelMember::insertOrIgnore([
                            'channel_id'           => $channel->id,
                            'user_id'              => (int) $data['user_id'],
                            'joined_at'            => now(),
                            'last_read_message_id' => null,
                            'created_at'           => now(),
                            'updated_at'           => now(),
                        ]);
                    }),
            ])
            ->recordActions([
                Action::make('remove')
                    ->label('حذف عضو')
                    ->icon('heroicon-o-x-mark')
                    ->iconButton()
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('حذف عضو از کانال؟')
                    ->visible(fn(Model $record): bool => (int) $record->user_id !== (int) $this->getOwnerRecord()->owner_id)
                    ->action(fn(Model $record) => $record->delete()),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark');
    }
}