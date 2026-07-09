<?php

namespace App\Filament\Resources\SuggestionResource\RelationManagers;

use App\Traits\FilamentActions;
use Filament\Infolists\Components\IconEntry;
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

class ReviewsRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'reviews';

    public static function getModelLabel(): string
    {
        return 'بررسی';
    }

    public static function getPluralModelLabel(): string
    {
        return 'بررسی‌ها';
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'بررسی‌ها';
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بررسی')
                ->schema([
                    TextEntry::make('user.name')->label('بررسی‌کننده'),
                    TextEntry::make('department.name')->label('واحد سازمانی')->formatStateUsing(fn(?Model $record): string => $record?->department?->displayLabel() ?? '-')->tooltip(fn(?Model $record): string => $record?->department?->tooltipLabel() ?? '-'),
                    TextEntry::make('feedback')
                        ->label('نظر')
                        ->formatStateUsing(fn ($state) => \App\Models\Review::FEEDBACKS[$state] ?? $state),
                    IconEntry::make('complete')
                        ->label('تکمیل شده')
                        ->boolean(),
                    TextEntry::make('comments')->label('توضیحات')->columnSpanFull(),
                    TextEntry::make('actions')->label('اقدامات')->columnSpanFull(),
                    TextEntry::make('created_at')->label('تاریخ')->formatStateUsing(fn ($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—'),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user', 'department']))
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('user.name')
                    ->label('بررسی‌کننده')
                    ->searchable(),
                TextColumn::make('department.name')
                    ->label('واحد سازمانی')
                    ->formatStateUsing(fn(?Model $record): string => $record?->department?->displayLabel() ?? '-')
                    ->tooltip(fn(?Model $record): string => $record?->department?->tooltipLabel() ?? '-'),
                TextColumn::make('feedback')
                    ->label('نظر')
                    ->formatStateUsing(fn ($state) => \App\Models\Review::FEEDBACKS[$state] ?? $state),
                IconColumn::make('complete')
                    ->label('تکمیل')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('تاریخ')
                    ->formatStateUsing(fn ($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
                    ->sortable(),
            ])
            ->recordActions([
                self::viewAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('created_at', 'desc');
    }
}
