<?php

namespace App\Filament\Resources\FeedResource\RelationManagers;

use App\Traits\FilamentActions;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ReactionsRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'reactions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/feed/strings.fields.reactions_count');
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextEntry::make('user.name')
                    ->label(__('resources/feed/strings.fields.user'))
                    ->icon('heroicon-o-user')
                    ->placeholder('—'),

                TextEntry::make('emoji')
                    ->label(__('resources/feed/strings.fields.emoji'))
                    ->size(TextSize::Large),

                TextEntry::make('created_at')
                    ->label(__('resources/feed/strings.fields.created_at'))
                    ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
                    ->icon('heroicon-o-clock')
                    ->color('gray'),
            ])->columns(3),
        ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label(__('resources/feed/strings.fields.user'))
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('emoji')
                    ->label(__('resources/feed/strings.fields.emoji'))
                    ->size('lg'),

                TextColumn::make('created_at')
                    ->label(__('resources/feed/strings.fields.created_at'))
                    ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
                    ->sortable(),
            ])
            ->groups([
                Group::make('emoji')
                    ->label(__('resources/feed/strings.fields.emoji'))
                    ->collapsible(),
            ])
            ->recordActions([
                self::viewAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions([self::bulkDeleteAction()])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-face-smile')
            ->striped();
    }
}
