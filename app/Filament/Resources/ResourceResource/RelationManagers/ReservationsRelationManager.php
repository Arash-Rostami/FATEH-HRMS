<?php

namespace App\Filament\Resources\ResourceResource\RelationManagers;

use App\Filament\Resources\ReservationResource;
use App\Filament\Resources\ReservationResource\Schemas\ReservationTablePresenter;
use App\Traits\FilamentActions;
use App\Filament\RelationManagers\RelationManager;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ReservationsRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'reservations';

    protected static ?string $relatedResource = ReservationResource::class;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/resource/strings.relations.reservations');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user']))
            ->columns([
                ReservationTablePresenter::id(),
                ReservationTablePresenter::user(),
                ReservationTablePresenter::startTime(),
                ReservationTablePresenter::isFullDay(),
                ReservationTablePresenter::status(),
                ReservationTablePresenter::isSeries(),
                ReservationTablePresenter::createdAt(),
            ])
            ->filters([
                ReservationTablePresenter::statusFilter(),
                ReservationTablePresenter::isFullDayFilter(),
                ReservationTablePresenter::isSeriesFilter(),
            ])
            ->groups([
                ReservationTablePresenter::byUser(),
                ReservationTablePresenter::byStatus(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                ReservationTablePresenter::cancelAction(),
                ReservationTablePresenter::releaseAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->toolbarActions([])
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('start_time', 'desc')
            ->striped();
    }
}
