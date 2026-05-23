<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\ThsResource\Schemas\TicketFormPresenter;
use App\Filament\Resources\ThsResource\Schemas\TicketInfolistPresenter;
use App\Filament\Resources\ThsResource\Schemas\TicketTablePresenter;
use App\Traits\FilamentActions;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
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
            Section::make(__('resources/ths/strings.label'))
                ->schema([
                    // Note: TicketFormPresenter::requesterId() excluded — auto-set by RelationManager
                    TicketFormPresenter::requestType(),
                    TicketFormPresenter::requestArea(),
                    TicketFormPresenter::requestSubject(),
                    TicketFormPresenter::description(),
                    TicketFormPresenter::priority(),
                    TicketFormPresenter::requesterFiles(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/ths/strings.label'))
                ->schema([
                    TicketInfolistPresenter::ticketId(),
                    TicketInfolistPresenter::status(),
                    TicketInfolistPresenter::priority(),
                    TicketInfolistPresenter::requestType(),
                    TicketInfolistPresenter::requestArea(),
                    TicketInfolistPresenter::requester(),
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
            ->recordTitleAttribute('request_subject')
            ->columns([
                TicketTablePresenter::ticketId(),
                TicketTablePresenter::status(),
                TicketTablePresenter::priority(),
                TicketTablePresenter::requestType(),
                TicketTablePresenter::assignee(),
                TicketTablePresenter::createdAt(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-sparkles')
                    ->label('افزودن تیکت'),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('created_at', 'desc');
    }
}
