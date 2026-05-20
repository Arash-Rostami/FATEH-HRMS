<?php

namespace App\Filament\Resources\ResourceResource\RelationManagers;

use App\Enums\ReservationStatus;
use App\Filament\Resources\ReservationResource\Schemas\ReservationTablePresenter;
use App\Traits\FilamentActions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ReservationsRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'reservations';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/resource/strings.relations.reservations');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/reservation/strings.plural_label');
    }


    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ReservationTablePresenter::id(),
                ReservationTablePresenter::user(),
                ReservationTablePresenter::resource(),
                ReservationTablePresenter::startTime(),
                ReservationTablePresenter::isFullDay(),
                ReservationTablePresenter::status(),
                ReservationTablePresenter::isSeries(),
                ReservationTablePresenter::createdAt(),
            ])
            ->headerActions([])
            ->recordActions([self::viewAction()], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('start_time', 'desc');
    }
}
