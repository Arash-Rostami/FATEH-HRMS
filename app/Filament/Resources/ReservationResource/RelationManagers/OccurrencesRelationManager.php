<?php

namespace App\Filament\Resources\ReservationResource\RelationManagers;

use App\Enums\ReservationStatus;
use App\Traits\FilamentActions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OccurrencesRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'occurrences';

    public static function getPluralModelLabel(): string
    {
        return __('resources/reservation/strings.relations.occurrences');
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/reservation/strings.relations.occurrences');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('start_time')
                    ->label(__('resources/reservation/strings.fields.start_time'))
                    ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '-')
                    ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
                    ->alignRight()
                    ->icon('heroicon-m-clock')
                    ->iconPosition(IconPosition::After)
                    ->sortable()
                    ->sortable(),
                TextColumn::make('end_time')
                    ->label(__('resources/reservation/strings.fields.end_time'))
                    ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
                    ->alignRight()
                    ->icon('heroicon-m-clock')
                    ->iconPosition(IconPosition::After)
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '-'),
                TextColumn::make('status')
                    ->label(__('resources/reservation/strings.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn(string $state) => ReservationStatus::tryFrom($state)?->getLabel() ?? $state)->color(fn(string $state) => ReservationStatus::tryFrom($state)?->getColor() ?? 'gray'),
            ])
            ->headerActions([])
            ->recordActions([self::editAction(), self::deleteAction()], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('start_time');
    }
}
