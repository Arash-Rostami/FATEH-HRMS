<?php

namespace App\Filament\Resources\EventResource\RelationManagers;

use App\Filament\Resources\EventResource\Exports\EventExporter;
use App\Filament\Resources\EventResource\Schemas\{EventFormPresenter, EventInfolistPresenter, EventTablePresenter};
use App\Traits\FilamentActions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\{CreateAction, ViewAction};
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class EventsRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'events';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/event/strings.form.section_info'))
                ->schema([
                    EventFormPresenter::title(),
                    EventFormPresenter::description(),
                ])
                ->columns(1),

            Section::make(__('resources/event/strings.form.section_schedule'))
                ->schema([
                    EventFormPresenter::dateJalali(),
                    EventFormPresenter::dateTimePart(),
                ])
                ->columns(2),
        ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                EventInfolistPresenter::title(),
                EventInfolistPresenter::description(),
                EventInfolistPresenter::date(),
                EventInfolistPresenter::private(),
                EventInfolistPresenter::createdAt(),
            ])->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                EventTablePresenter::id(),
                EventTablePresenter::title(),
                EventTablePresenter::date(),
                EventTablePresenter::private(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-sparkles')
                    ->label(__('resources/event/strings.navigation.singular')),
            ])
            ->recordActions([
                ViewAction::make()->slideOver(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions(self::bulkActions(EventExporter::class))
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('date', 'desc');
    }
}
