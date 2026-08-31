<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Filament\Resources\TaskResource\Schemas\TaskFormPresenter;
use App\Filament\Resources\TaskResource\Schemas\TaskInfolistPresenter;
use App\Filament\Resources\TaskResource\Schemas\TaskTablePresenter;
use App\Models\Task;
use App\Services\ProjectTask\ReportingService;
use App\Traits\FilamentActions;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TasksRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'tasks';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/task/strings.form.section_meta'))
                ->icon('heroicon-o-users')
                ->schema([
                    TaskFormPresenter::userId(),
                    TaskFormPresenter::assignedTo(),
                    TaskFormPresenter::status(),
                ])
                ->columns(3),

            Section::make(__('resources/task/strings.form.section_deadline'))
                ->icon('heroicon-o-calendar')
                ->schema([
                    TaskFormPresenter::deadlineDate(),
                ])
                ->columns(1),

            Section::make(__('resources/task/strings.form.section_content'))
                ->icon('heroicon-o-document-text')
                ->schema([
                    TaskFormPresenter::title(),
                    TaskFormPresenter::description(),
                ])
                ->columnSpanFull()
                ->columns(2),

            Section::make(__('resources/task/strings.form.section_project'))
                ->icon('heroicon-o-rectangle-stack')
                ->schema([
                    TaskFormPresenter::priority(),
                    TaskFormPresenter::labels(),
                ])
                ->columns(2),
        ]);
    }

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

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->hiddenLabel()
                ->schema([
                    TaskInfolistPresenter::status(),
                    TaskInfolistPresenter::assignee(),
                    TaskInfolistPresenter::title(),
                    TaskInfolistPresenter::description(),
                    TaskInfolistPresenter::deadline(),
                    TaskInfolistPresenter::priority(),
                    TaskInfolistPresenter::labels(),
                    TaskInfolistPresenter::checklistCompletion(),
                ])
                ->columnSpanFull()
                ->columns(3),
        ]);
    }

    public function table(Table $table): Table
    {
        $reportingService = app(ReportingService::class);

        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['assignee', 'creator', 'detail']))
            ->columns([
                TaskTablePresenter::id(),
                TaskTablePresenter::title(),
                TaskTablePresenter::status(),
                TaskTablePresenter::priority(),
                TaskTablePresenter::assignee(),
                TaskTablePresenter::deadline(),
                TaskTablePresenter::labels(),
                TextColumn::make('progress')
                    ->label(__('resources/task/strings.fields.progress'))
                    ->html()
                    ->state(function (Task $record) use ($reportingService) {
                        $percent = $reportingService->progressPercent($record);

                        return <<<HTML
                            <div style="display:flex;align-items:center;gap:8px;width:96px;">
                                <div style="flex:1;height:6px;border-radius:9999px;background:rgba(148,163,184,0.35);overflow:hidden;">
                                    <div style="height:100%;border-radius:9999px;background:#6366f1;width:{$percent}%;"></div>
                                </div>
                                <span style="font-size:11px;white-space:nowrap;">{$percent}٪</span>
                            </div>
                            HTML;
                    }),
                TaskTablePresenter::createdAt(),
            ])
            ->groups([
                TaskTablePresenter::statusGroup(),
            ])
            ->filters([
                TrashedFilter::make(),
                TaskTablePresenter::statusFilter(),
                TaskTablePresenter::priorityFilter(),
                TaskTablePresenter::overdueFilter(),
            ])
            ->filtersFormColumns(2)
            ->headerActions([
                CreateAction::make(),
            ])
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
