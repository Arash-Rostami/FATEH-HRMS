<?php

namespace App\Filament\Resources\EventResource;

use App\Filament\Resources\EventResource\Exports\EventExporter;
use App\Filament\Resources\EventResource\Schemas\{EventFormPresenter, EventInfolistPresenter, EventTablePresenter};
use App\Traits\FilamentActions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EventsRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'events';

    public static function getModelLabel(): string
    {
        return __('resources/event/strings.navigation.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/event/strings.navigation.plural');
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/event/strings.navigation.plural');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/event/strings.form.section_info'))
                ->icon('heroicon-o-document-text')
                ->schema([
                    EventFormPresenter::title(),
                    EventFormPresenter::description(),
                ])
                ->columns(1),

            Group::make()
                ->schema([
                    Section::make(__('resources/event/strings.form.section_schedule'))
                        ->icon('heroicon-o-calendar')
                        ->schema([
                            EventFormPresenter::dateJalali(),
                            EventFormPresenter::dateTimePart(),
                        ])->columns(3),

                    Section::make(__('resources/event/strings.form.section_access'))
                        ->icon('heroicon-o-lock-closed')
                        ->schema([
                            EventFormPresenter::private(),
                        ])->columns(2),
                ])
        ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->hiddenLabel()
                ->schema([
                    EventInfolistPresenter::date(),
                    EventInfolistPresenter::private(),
                    EventInfolistPresenter::title(),
                    EventInfolistPresenter::description(),
                    EventInfolistPresenter::createdAt(),
                    EventInfolistPresenter::updatedAt(),
                ])
                ->columnSpanFull()
                ->columns(3),
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
                EventTablePresenter::createdAt(),
            ])
            ->groups([
                EventTablePresenter::visibilityGroup(),
            ])
            ->filters([
                EventTablePresenter::visibilityFilter(),
                EventTablePresenter::upcomingFilter(),
                EventTablePresenter::dateRangeFilter(),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('date', 'desc')
            ->striped();
    }
}
