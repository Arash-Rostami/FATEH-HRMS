<?php

namespace App\Filament\Resources\DmsResource\RelationManagers;

use App\Traits\FilamentActions;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ReadsRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'reads';

    public static function getModelLabel(): string
    {
        return 'مطالعه';
    }

    public static function getPluralModelLabel(): string
    {
        return 'آمار مطالعه';
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'آمار مطالعه';
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('جزئیات مطالعه')
                ->schema([
                    TextEntry::make('user.name')->label('کاربر'),
                    TextEntry::make('read_count')->label('تعداد مطالعه'),
                    TextEntry::make('updated_at')->label('آخرین مطالعه')->formatStateUsing(fn ($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—'),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user']))
            ->columns([
                TextColumn::make('user.name')
                    ->label('کاربر')
                    ->searchable(),
                IconColumn::make('read')
                    ->label('خوانده شده')
                    ->boolean(),
                TextColumn::make('read_count')
                    ->label(__('resources/dms/strings.fields.read_count'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('آخرین مطالعه')
                    ->formatStateUsing(fn ($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
                    ->sortable(),
            ])
            ->recordActions([
                self::viewAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('updated_at', 'desc');
    }
}
