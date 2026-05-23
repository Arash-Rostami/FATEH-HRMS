<?php

namespace App\Filament\Resources\ResourceResource\RelationManagers;

use App\Filament\Resources\ReservationResource\Schemas\ReservationFormPresenter;
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

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/reservation/strings.form.section_main'))
                ->icon('heroicon-o-user')
                ->schema([
                    ReservationFormPresenter::userId(),
                    ReservationFormPresenter::parentId(),

                    divider(),
                    ReservationFormPresenter::status(),
                    ReservationFormPresenter::isRecurring(),
                    ReservationFormPresenter::recurPattern(),
                    ReservationFormPresenter::recurCount(),
                ])
                ->columns(2),
            Section::make(__('resources/reservation/strings.form.section_time'))
                ->icon('heroicon-o-clock')
                ->schema([
                    ReservationFormPresenter::isFullDay(),
                    ReservationFormPresenter::fullDayDate(),
                    ReservationFormPresenter::startTime(),
                    ReservationFormPresenter::endTime(),

                    ReservationFormPresenter::cancelReason()
                ])
                ->columns(3),
        ]);
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/reservation/strings.plural_label');
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/resource/strings.relations.reservations');
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->hiddenLabel()
                ->schema([
                    ReservationInfolistPresenter::user(),
                    ReservationInfolistPresenter::resource(),
                    ReservationInfolistPresenter::status(),
                    ReservationInfolistPresenter::parentId(),
                    ReservationInfolistPresenter::occurrencesCount(),
                    ReservationInfolistPresenter::startTime(),
                    ReservationInfolistPresenter::endTime(),
                    ReservationInfolistPresenter::isFullDay(),
                    ReservationInfolistPresenter::cancelledBy(),
                    ReservationInfolistPresenter::cancelledAt(),
                    ReservationInfolistPresenter::cancelReason(),
                    ReservationInfolistPresenter::createdAt(),

                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
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
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('start_time', 'desc')
            ->striped();
    }
}
