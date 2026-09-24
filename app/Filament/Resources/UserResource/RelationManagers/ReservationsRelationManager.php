<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

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
        return __('resources/reservation/strings.plural_label');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['resource']))
            ->columns([
                ReservationTablePresenter::id(),
                ReservationTablePresenter::resource(),
                ReservationTablePresenter::startTime(),
                ReservationTablePresenter::isFullDay(),
                ReservationTablePresenter::status(),
                ReservationTablePresenter::isSeries(),
                ReservationTablePresenter::createdAt(),
            ])
            ->filters([
                ReservationTablePresenter::statusFilter(),
                ReservationTablePresenter::resourceTypeFilter(),
                ReservationTablePresenter::isFullDayFilter(),
                ReservationTablePresenter::isSeriesFilter(),
            ])
            ->groups([
                ReservationTablePresenter::byStatus(),
                ReservationTablePresenter::byResource(),
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
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
