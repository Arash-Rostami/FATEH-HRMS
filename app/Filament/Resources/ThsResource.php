<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ThsResource\Exports\ThsExporter;
use App\Traits\AuthorizesByPermission;
use App\Filament\Resources\ThsResource\Pages\{CreateTicket, EditTicket, ListTickets};
use App\Filament\Resources\ThsResource\Schemas\{TicketFormPresenter, TicketInfolistPresenter, TicketTablePresenter};
use App\Models\Ticket;
use App\Traits\FilamentActions;
use App\Traits\FilamentFilters;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ThsResource extends Resource
{
    use FilamentActions, FilamentFilters, AuthorizesByPermission;

    protected static ?string $model = Ticket::class;
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-lifebuoy';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
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
                                    TicketFormPresenter::requesterId(),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['requester', 'assignee']);
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('resources/ths/strings.fields.requester') => $record->requester?->name ?? '—',
            __('resources/ths/strings.fields.status') => $record->status,
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return TicketTablePresenter::formatTicketId($record) . ' — ' . $record->request_subject;
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['request_subject', 'description', 'requester.name', 'assignee.name'];
    }

    public static function getModelLabel(): string
    {
        return __('resources/ths/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/ths/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
            'create' => CreateTicket::route('/create'),
            'edit' => EditTicket::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/ths/strings.plural_label');
    }

    public static function infolist(Schema $schema): Schema
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

                    TicketInfolistPresenter::requester(),
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TicketTablePresenter::ticketId(),
                TicketTablePresenter::status(),
                TicketTablePresenter::requester(),
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
                TicketTablePresenter::requesterFilter(),
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
            ->groupedBulkActions(self::bulkActions(ThsExporter::class))
            ->emptyStateIcon('heroicon-o-ticket')
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
