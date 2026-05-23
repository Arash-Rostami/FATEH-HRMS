<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\TaskResource\Schemas\TaskFormPresenter;
use App\Filament\Resources\TaskResource\Schemas\TaskInfolistPresenter;
use App\Filament\Resources\TaskResource\Schemas\TaskTablePresenter;
use App\Traits\FilamentActions;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
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
            Section::make(__('resources/task/strings.form.section_content'))
                ->schema([
                    TaskFormPresenter::title(),
                    TaskFormPresenter::description(),
                ])
                ->columns(2),

            Section::make(__('resources/task/strings.form.section_meta'))
                ->schema([
                    // Note: TaskFormPresenter::userId() excluded — auto-set by RelationManager
                    TaskFormPresenter::assignedTo(),
                    TaskFormPresenter::status(),
                ])
                ->columns(2),

            Section::make(__('resources/task/strings.form.section_deadline'))
                ->schema([
                    TaskFormPresenter::deadlineDate(),
                    TaskFormPresenter::deadlineTime(),
                ])
                ->columns(2),
        ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/task/strings.form.section_content'))
                ->schema([
                    TaskInfolistPresenter::title(),
                    TaskInfolistPresenter::status(),
                    TaskInfolistPresenter::description(),
                ])
                ->columnSpanFull()
                ->columns(2),

            Section::make(__('resources/task/strings.form.section_meta'))
                ->schema([
                    TaskInfolistPresenter::creator(),
                    TaskInfolistPresenter::assignee(),
                    TaskInfolistPresenter::delegatedIcon(),
                    TaskInfolistPresenter::deadline(),
                    TaskInfolistPresenter::createdAt(),
                    TaskInfolistPresenter::updatedAt(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TaskTablePresenter::id(),
                TaskTablePresenter::title(),
                TaskTablePresenter::status(),
                TaskTablePresenter::assignee(),
                TaskTablePresenter::deadline(),
                TaskTablePresenter::createdAt(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-sparkles')
                    ->label('افزودن وظیفه'),
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
