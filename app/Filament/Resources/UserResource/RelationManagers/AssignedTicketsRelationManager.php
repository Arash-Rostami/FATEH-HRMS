<?php
namespace App\Filament\Resources\UserResource\RelationManagers;
use Filament\Resources\RelationManagers\RelationManager;
use App\Filament\Resources\ThsResource\Schemas\TicketFormPresenter;
use App\Filament\Resources\ThsResource\Schemas\TicketInfolistPresenter;
use App\Filament\Resources\ThsResource\Schemas\TicketTablePresenter;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Tables\Enums\RecordActionsPosition;
use App\Traits\FilamentActions;
class AssignedTicketsRelationManager extends RelationManager
{
    use FilamentActions;
    protected static string $relationship = 'assignedTickets';
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Ticket/strings.form.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    TicketFormPresenter::actionResult(),
                    TicketFormPresenter::additionalNotes(),
                    TicketFormPresenter::assignedTo(),
                    TicketFormPresenter::assigneeFiles(),
                    TicketFormPresenter::completionDate(),
                    TicketFormPresenter::completionDeadlineDate(),
                    TicketFormPresenter::completionDeadlineTime(),
                    TicketFormPresenter::departmentDisplay(),
                    TicketFormPresenter::description(),
                    TicketFormPresenter::effectiveness(),
                    TicketFormPresenter::priority(),
                    TicketFormPresenter::requestArea(),
                    TicketFormPresenter::requestSubject(),
                    TicketFormPresenter::requestType(),
                    TicketFormPresenter::requesterFiles(),
                    TicketFormPresenter::requesterId(),
                    TicketFormPresenter::satisfactionScore(),
                    TicketFormPresenter::status(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public static function getModelLabel(): string
    {
        return __('resources/Ticket/strings.label');
    }
    public static function getPluralModelLabel(): string
    {
        return __('resources/Ticket/strings.plural_label');
    }
    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('resources/Ticket/strings.plural_label');
    }
    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Ticket/strings.infolist.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    TicketInfolistPresenter::ticketId(),
                    TicketInfolistPresenter::status(),
                    TicketInfolistPresenter::priority(),
                    TicketInfolistPresenter::requestType(),
                    TicketInfolistPresenter::requestArea(),
                    TicketInfolistPresenter::requester(),
                    TicketInfolistPresenter::department(),
                    TicketInfolistPresenter::assignee(),
                    TicketInfolistPresenter::subject(),
                    TicketInfolistPresenter::description(),
                    TicketInfolistPresenter::completionDeadline(),
                    TicketInfolistPresenter::completionDate(),
                    TicketInfolistPresenter::actionResult(),
                    TicketInfolistPresenter::additionalNotes(),
                    TicketInfolistPresenter::effectiveness(),
                    TicketInfolistPresenter::satisfaction(),
                    TicketInfolistPresenter::requesterFiles(),
                    TicketInfolistPresenter::assigneeFiles(),
                    TicketInfolistPresenter::createdAt(),
                    TicketInfolistPresenter::updatedAt(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TicketTablePresenter::assignee(),
                TicketTablePresenter::completionDate(),
                TicketTablePresenter::completionDeadline(),
                TicketTablePresenter::createdAt(),
                TicketTablePresenter::deadlineColor(),
                TicketTablePresenter::deadlineDelta(),
                TicketTablePresenter::department(),
                TicketTablePresenter::effectiveness(),
                TicketTablePresenter::formatTicketId(),
                TicketTablePresenter::priority(),
                TicketTablePresenter::requestArea(),
                TicketTablePresenter::requestType(),
                TicketTablePresenter::requester(),
                TicketTablePresenter::satisfaction(),
                TicketTablePresenter::status(),
                TicketTablePresenter::subject(),
                TicketTablePresenter::ticketId(),
            ])
            ->searchable(false)
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-sparkles')->label(__('resources/Ticket/strings.navigation.singular')),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark');
    }
}
