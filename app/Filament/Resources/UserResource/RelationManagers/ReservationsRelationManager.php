<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\ReservationResource\Schemas\ReservationInfolistPresenter;
use App\Filament\Resources\ReservationResource\Schemas\ReservationTablePresenter;
use App\Traits\FilamentActions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ReservationsRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'reservations';

    public static function getModelLabel(): string
    {
        return __('resources/reservation/strings.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/reservation/strings.plural_label');
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/reservation/strings.plural_label');
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/reservation/strings.label'))
                ->schema([
                    ReservationInfolistPresenter::resource(),
                    ReservationInfolistPresenter::startTime(),
                    ReservationInfolistPresenter::endTime(),
                    ReservationInfolistPresenter::isFullDay(),
                    ReservationInfolistPresenter::status(),
                    ReservationInfolistPresenter::occurrencesCount(),
                    ReservationInfolistPresenter::cancelReason(),
                    ReservationInfolistPresenter::cancelledAt(),
                    ReservationInfolistPresenter::cancelledBy(),
                    ReservationInfolistPresenter::createdAt(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                ReservationTablePresenter::id(),
                ReservationTablePresenter::resource(),
                ReservationTablePresenter::startTime(),
                ReservationTablePresenter::isFullDay(),
                ReservationTablePresenter::status(),
                ReservationTablePresenter::isSeries(),
            ])
            ->recordActions([
                self::viewAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('created_at', 'desc');
    }
}
