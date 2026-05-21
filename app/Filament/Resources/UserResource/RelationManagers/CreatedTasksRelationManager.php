<?php
namespace App\Filament\Resources\UserResource\RelationManagers;
use Filament\Resources\RelationManagers\RelationManager;
use App\Filament\Resources\TaskResource\Schemas\TaskFormPresenter;
use App\Filament\Resources\TaskResource\Schemas\TaskInfolistPresenter;
use App\Filament\Resources\TaskResource\Schemas\TaskTablePresenter;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Tables\Enums\RecordActionsPosition;
use App\Traits\FilamentActions;
class CreatedTasksRelationManager extends RelationManager
{
    use FilamentActions;
    protected static string $relationship = 'createdTasks';
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Task/strings.form.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    TaskFormPresenter::assignedTo(),
                    TaskFormPresenter::deadlineDate(),
                    TaskFormPresenter::deadlineTime(),
                    TaskFormPresenter::description(),
                    TaskFormPresenter::status(),
                    TaskFormPresenter::title(),
                    TaskFormPresenter::userId(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public static function getModelLabel(): string
    {
        return __('resources/Task/strings.label');
    }
    public static function getPluralModelLabel(): string
    {
        return __('resources/Task/strings.plural_label');
    }
    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('resources/Task/strings.plural_label');
    }
    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Task/strings.infolist.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    TaskInfolistPresenter::assignee(),
                    TaskInfolistPresenter::createdAt(),
                    TaskInfolistPresenter::creator(),
                    TaskInfolistPresenter::deadline(),
                    TaskInfolistPresenter::delegatedIcon(),
                    TaskInfolistPresenter::deletedAt(),
                    TaskInfolistPresenter::description(),
                    TaskInfolistPresenter::status(),
                    TaskInfolistPresenter::title(),
                    TaskInfolistPresenter::updatedAt(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TaskTablePresenter::assignee(),
                TaskTablePresenter::createdAt(),
                TaskTablePresenter::creator(),
                TaskTablePresenter::deadline(),
                TaskTablePresenter::deletedAt(),
                TaskTablePresenter::description(),
                TaskTablePresenter::id(),
                TaskTablePresenter::isDelegated(),
                TaskTablePresenter::status(),
                TaskTablePresenter::title(),
            ])
            ->searchable(false)
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-sparkles')->label(__('resources/Task/strings.navigation.singular')),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark');
    }
}
