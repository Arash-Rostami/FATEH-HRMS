<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\TaskResource\Schemas\TaskFormPresenter;
use App\Filament\Resources\TaskResource\Schemas\TaskInfolistPresenter;
use App\Filament\Resources\TaskResource\Schemas\TaskTablePresenter;
use App\Traits\FilamentActions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TasksRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'tasks';

    public static function getModelLabel(): string
    {
        return __('resources/task/strings.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/task/strings.plural_label');
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/task/strings.plural_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/task/strings.form.section_meta'))
                ->icon('heroicon-o-users')
                ->schema([
                    TaskFormPresenter::assignedTo(),
                    TaskFormPresenter::status(),
                ])
                ->columns(2),


            Section::make(__('resources/task/strings.form.section_deadline'))
                ->icon('heroicon-o-calendar')
                ->schema([
                    TaskFormPresenter::deadlineDate(),
                    TaskFormPresenter::deadlineTime(),
                ])
                ->columns(2),

            Section::make(__('resources/task/strings.form.section_content'))
                ->icon('heroicon-o-document-text')
                ->schema([
                    TaskFormPresenter::title(),
                    TaskFormPresenter::description(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->hiddenLabel()
                ->schema([
                    TaskInfolistPresenter::status(),
                    TaskInfolistPresenter::assignee(),
                    TaskInfolistPresenter::delegatedIcon(),

                    TaskInfolistPresenter::title(),
                    TaskInfolistPresenter::description(),
                    TaskInfolistPresenter::deadline(),

                    TaskInfolistPresenter::createdAt(),
                    TaskInfolistPresenter::updatedAt(),
                    TaskInfolistPresenter::deletedAt(),
                ])
                ->columnSpanFull()
                ->columns(3),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TaskTablePresenter::id(),
                TaskTablePresenter::title(),
                TaskTablePresenter::status(),
                TaskTablePresenter::assignee(),
                TaskTablePresenter::isDelegated(),
                TaskTablePresenter::deadline(),
                TaskTablePresenter::description(),
                TaskTablePresenter::deletedAt(),
                TaskTablePresenter::createdAt(),
            ])
            ->groups([
                TaskTablePresenter::statusGroup(),
            ])
            ->filters([
                TrashedFilter::make(),
                TaskTablePresenter::statusFilter(),
                TaskTablePresenter::delegatedFilter(),
                self::createdAtFilter(),
                TaskTablePresenter::overdueFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction()->visible(fn($record) => !$record->trashed()),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
