<?php
namespace App\Filament\Resources\UserResource\RelationManagers;
use Filament\Resources\RelationManagers\RelationManager;
use App\Filament\Resources\EventResource\Schemas\EventFormPresenter;
use App\Filament\Resources\EventResource\Schemas\EventInfolistPresenter;
use App\Filament\Resources\EventResource\Schemas\EventTablePresenter;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Tables\Enums\RecordActionsPosition;
use App\Traits\FilamentActions;
class EventsRelationManager extends RelationManager
{
    use FilamentActions;
    protected static string $relationship = 'events';
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Event/strings.form.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    EventFormPresenter::dateJalali(),
                    EventFormPresenter::dateTimePart(),
                    EventFormPresenter::description(),
                    EventFormPresenter::private(),
                    EventFormPresenter::title(),
                    EventFormPresenter::userId(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public static function getModelLabel(): string
    {
        return __('resources/Event/strings.label');
    }
    public static function getPluralModelLabel(): string
    {
        return __('resources/Event/strings.plural_label');
    }
    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('resources/Event/strings.plural_label');
    }
    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Event/strings.infolist.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    EventInfolistPresenter::createdAt(),
                    EventInfolistPresenter::date(),
                    EventInfolistPresenter::description(),
                    EventInfolistPresenter::private(),
                    EventInfolistPresenter::title(),
                    EventInfolistPresenter::updatedAt(),
                    EventInfolistPresenter::user(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                EventTablePresenter::createdAt(),
                EventTablePresenter::date(),
                EventTablePresenter::id(),
                EventTablePresenter::private(),
                EventTablePresenter::title(),
                EventTablePresenter::user(),
            ])
            ->searchable(false)
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-sparkles')->label(__('resources/Event/strings.navigation.singular')),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark');
    }
}
