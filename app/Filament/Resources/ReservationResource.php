<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReservationResource\Exports\ReservationExporter;
use App\Filament\Resources\ReservationResource\Pages\CreateReservation;
use App\Filament\Resources\ReservationResource\Pages\EditReservation;
use App\Filament\Resources\ReservationResource\Pages\ListReservations;
use App\Filament\Resources\ReservationResource\RelationManagers\OccurrencesRelationManager;
use App\Filament\Resources\ReservationResource\Schemas\ReservationFormPresenter;
use App\Filament\Resources\ReservationResource\Schemas\ReservationInfolistPresenter;
use App\Filament\Resources\ReservationResource\Schemas\ReservationTablePresenter;
use App\Models\Reservation;
use App\Traits\AuthorizesByPermission;
use App\Traits\FilamentActions;
use App\Traits\FilamentFilters;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ReservationResource extends Resource
{
    use FilamentActions, FilamentFilters, AuthorizesByPermission;

    protected static ?string $model = Reservation::class;
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-building-office';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/reservation/strings.form.section_main'))
                ->icon('heroicon-o-user')
                ->schema([
                    ReservationFormPresenter::userId(),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'resource', 'cancelledBy', 'parent'])
            ->withCount('occurrences');
    }

    public static function getGlobalSearchResultActions(Model $record): array
    {
        return [
            Action::make('edit')
                ->icon('heroicon-m-pencil')
                ->url(static::getUrl('edit', ['record' => $record])),
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return "#{$record->id} — {$record->user?->name} → {$record->resource?->name}";
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['user.name', 'resource.name'];
    }

    public static function getModelLabel(): string
    {
        return __('resources/reservation/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/reservation/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReservations::route('/'),
            'create' => CreateReservation::route('/create'),
            'edit' => EditReservation::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/reservation/strings.plural_label');
    }

    public static function getRelations(): array
    {
        return [OccurrencesRelationManager::class];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/reservation/strings.infolist.section_main'))
                ->icon('heroicon-o-user')
                ->schema([
                    ReservationInfolistPresenter::user(),
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

    public static function table(Table $table): Table
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
            ->filters([
                ReservationTablePresenter::statusFilter(),
                ReservationTablePresenter::resourceTypeFilter(),
                ReservationTablePresenter::isFullDayFilter(),
                ReservationTablePresenter::isSeriesFilter(),
                self::createdAtFilter(),
            ])
            ->groups([
                ReservationTablePresenter::byUser(),
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
            ->groupedBulkActions(self::bulkActions(ReservationExporter::class))
            ->emptyStateIcon('heroicon-o-building-office')
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
