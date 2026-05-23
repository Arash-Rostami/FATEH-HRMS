<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\ThsResource\Schemas\TicketFormPresenter;
use App\Filament\Resources\ThsResource\Schemas\TicketInfolistPresenter;
use App\Filament\Resources\ThsResource\Schemas\TicketTablePresenter;
use App\Traits\FilamentActions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TicketsRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'tickets';

    public static function getModelLabel(): string
    {
        return __('resources/ths/strings.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/ths/strings.plural_label');
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/ths/strings.plural_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()
                ->tabs([
                    Tab::make(__('resources/ths/strings.form.tab_request'))
                        ->icon('heroicon-o-inbox')
                        ->schema([
                            Section::make(__('resources/ths/strings.form.section_requester'))
                                ->icon('heroicon-o-user')
                                ->schema([
                                    TicketFormPresenter::departmentDisplay(),
                                ])
                                ->columns(2),

                            Section::make(__('resources/ths/strings.form.section_request'))
                                ->icon('heroicon-o-document-text')
                                ->schema([
                                    TicketFormPresenter::requestType(),
                                    TicketFormPresenter::requestArea(),
                                    TicketFormPresenter::priority(),
                                    TicketFormPresenter::requestSubject(),
                                    TicketFormPresenter::description(),
                                    TicketFormPresenter::requesterFiles(),
                                ])
                                ->columns(3),
                        ]),

                    Tab::make(__('resources/ths/strings.form.tab_response'))
                        ->icon('heroicon-o-chat-bubble-bottom-center-text')
                        ->schema([
                            Section::make(__('resources/ths/strings.form.section_assignment'))
                                ->icon('heroicon-o-user-circle')
                                ->schema([
                                    TicketFormPresenter::assignedTo(),
                                    TicketFormPresenter::status(),
                                    TicketFormPresenter::completionDate(),
                                    TicketFormPresenter::completionDeadlineDate(),
                                    TicketFormPresenter::completionDeadlineTime(),
                                ])
                                ->columns(3),

                            Section::make(__('resources/ths/strings.form.section_response'))
                                ->icon('heroicon-o-chat-bubble-left-right')
                                ->schema([
                                    TicketFormPresenter::effectiveness(),
                                    TicketFormPresenter::satisfactionScore(),
                                    TicketFormPresenter::actionResult(),
                                    TicketFormPresenter::additionalNotes(),
                                    TicketFormPresenter::assigneeFiles(),
                                ])
                                ->columns(2),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->hiddenLabel()
                ->schema([
                    TicketInfolistPresenter::ticketId(),
                    TicketInfolistPresenter::status(),
                    TicketInfolistPresenter::priority(),
                    TicketInfolistPresenter::requestType(),
                    TicketInfolistPresenter::requestArea(),

                    TicketInfolistPresenter::department(),
                    TicketInfolistPresenter::assignee(),
                    TicketInfolistPresenter::subject(),
                    TicketInfolistPresenter::description(),
                    TicketInfolistPresenter::requesterFiles(),

                    TicketInfolistPresenter::completionDeadline(),
                    TicketInfolistPresenter::completionDate(),
                    TicketInfolistPresenter::effectiveness(),
                    TicketInfolistPresenter::satisfaction(),
                    TicketInfolistPresenter::actionResult(),
                    TicketInfolistPresenter::additionalNotes(),
                    TicketInfolistPresenter::assigneeFiles(),

                    TicketInfolistPresenter::createdAt(),
                    TicketInfolistPresenter::updatedAt(),
                ])
                ->columnSpanFull()
                ->columns(4),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('request_subject')
            ->columns([
                TicketTablePresenter::ticketId(),
                TicketTablePresenter::status(),
                TicketTablePresenter::priority(),
                TicketTablePresenter::requestType(),
                TicketTablePresenter::requestArea(),
                TicketTablePresenter::department(),
                TicketTablePresenter::subject(),
                TicketTablePresenter::assignee(),
                TicketTablePresenter::completionDeadline(),
                TicketTablePresenter::completionDate(),
                TicketTablePresenter::satisfaction(),
                TicketTablePresenter::effectiveness(),
                TicketTablePresenter::createdAt(),
            ])
            ->groups([
                TicketTablePresenter::statusGroup(),
                TicketTablePresenter::assigneeGroup(),
                TicketTablePresenter::typeGroup(),
            ])
            ->filters([
                TicketTablePresenter::priorityFilter(),
                TicketTablePresenter::typeFilter(),
                TicketTablePresenter::assigneeFilter(),
                TicketTablePresenter::unassignedFilter(),
                self::createdAtFilter(),
                TicketTablePresenter::overdueFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-ticket')
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
