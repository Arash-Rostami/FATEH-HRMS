<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Enums\ReservationStatus;
use App\Filament\Resources\ReservationResource\Schemas\ReservationFormPresenter;
use App\Filament\Resources\ReservationResource\Schemas\ReservationInfolistPresenter;
use App\Filament\Resources\ReservationResource\Schemas\ReservationTablePresenter;
use App\Traits\FilamentActions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
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

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/reservation/strings.form.section_main'))
                ->icon('heroicon-o-user')
                ->schema([
                    ReservationFormPresenter::resourceId(),
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

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/reservation/strings.infolist.section_main'))
                ->icon('heroicon-o-user')
                ->schema([
                    ReservationInfolistPresenter::resource(),
                    ReservationInfolistPresenter::status(),
                    ReservationInfolistPresenter::parentId(),
                    ReservationInfolistPresenter::occurrencesCount(),
                    ReservationInfolistPresenter::createdAt(),
                ])
                ->columns(2),
            Section::make(__('resources/reservation/strings.infolist.section_time'))
                ->icon('heroicon-o-clock')
                ->schema([
                    ReservationInfolistPresenter::startTime(),
                    ReservationInfolistPresenter::endTime(),
                    ReservationInfolistPresenter::isFullDay(),
                ])
                ->columns(3),
            Section::make(__('resources/reservation/strings.infolist.section_cancel'))
                ->icon('heroicon-o-x-circle')
                ->schema([
                    ReservationInfolistPresenter::cancelledBy(),
                    ReservationInfolistPresenter::cancelledAt(),
                    ReservationInfolistPresenter::cancelReason(),
                ])
                ->columns(3),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
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
                self::createdAtFilter(),
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
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
