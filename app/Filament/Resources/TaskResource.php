<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Exports\TaskExporter;
use App\Filament\Resources\TaskResource\Pages\{CreateTask, EditTask, ListTasks};
use App\Filament\Resources\TaskResource\Schemas\{TaskFormPresenter, TaskInfolistPresenter, TaskTablePresenter};
use App\Models\Task;
use App\Traits\AuthorizesByPermission;
use App\Traits\FilamentActions;
use App\Traits\FilamentFilters;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaskResource extends Resource
{
    use FilamentActions, FilamentFilters, AuthorizesByPermission;

    protected static ?string $model = Task::class;
    protected static ?string $recordTitleAttribute = 'title';
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-view-columns';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()
                ->tabs([
                    Tab::make(__('resources/task/strings.form.tab_main'))
                        ->icon('heroicon-o-clipboard-document-list')
                        ->schema([
                            Section::make(__('resources/task/strings.form.section_meta'))
                                ->icon('heroicon-o-users')
                                ->schema([
                                    TaskFormPresenter::userId(),
                                    TaskFormPresenter::assignedTo(),
                                    TaskFormPresenter::divider(),
                                    TaskFormPresenter::status(),
                                ])
                                ->columns(3),

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
                                    TaskFormPresenter::divider(),
                                    TaskFormPresenter::description(),
                                ])
                                ->columnSpanFull()
                                ->columns(2),
                        ]),

                    Tab::make(__('resources/task/strings.form.tab_bi'))
                        ->icon('heroicon-o-chart-bar-square')
                        ->schema([
                            Section::make(__('resources/task/strings.form.section_bi'))
                                ->icon('heroicon-o-chart-bar-square')
                                ->relationship('detail')
                                ->schema([
                                    TaskFormPresenter::departmentId(),
                                    TaskFormPresenter::unit(),
                                    TaskFormPresenter::section(),
                                    TaskFormPresenter::project(),
                                    TaskFormPresenter::scheme(),
                                    TaskFormPresenter::actionSourceDomain(),
                                    TaskFormPresenter::actionSource(),
                                    TaskFormPresenter::collaborators(),
                                    TaskFormPresenter::responsibleUserId(),
                                    TaskFormPresenter::state(),
                                    TaskFormPresenter::attachments(),
                                ])
                                ->columns(2),
                        ]),
                ])
                ->columnSpanFull()
                ->persistTabInQueryString(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->with(['creator', 'assignee', 'detail', 'detail.department', 'detail.responsibleUser']);
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('resources/task/strings.fields.creator') => $record->creator?->name ?? '—',
            __('resources/task/strings.fields.assignee') => $record->assignee?->name ?? '—',
            __('resources/task/strings.fields.status') => $record->status,
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->title;
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'description', 'creator.name', 'assignee.name'];
    }

    public static function getModelLabel(): string
    {
        return __('resources/task/strings.label');
    }


    public static function getNavigationGroup(): ?string
    {
        return __('resources/task/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTasks::route('/'),
            'create' => CreateTask::route('/create'),
            'edit' => EditTask::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/task/strings.plural_label');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()
                ->tabs([
                    Tab::make(__('resources/task/strings.infolist.tab_main'))
                        ->icon('heroicon-o-clipboard-document-list')
                        ->schema([
                            Section::make()
                                ->hiddenLabel()
                                ->schema([
                                    TaskInfolistPresenter::status(),
                                    TaskInfolistPresenter::creator(),
                                    TaskInfolistPresenter::assignee(),
                                    TaskInfolistPresenter::delegatedIcon(),

                                    TaskInfolistPresenter::title(),
                                    TaskInfolistPresenter::description(),
                                    TaskInfolistPresenter::deadline(),

                                    TaskInfolistPresenter::createdAt(),
                                    TaskInfolistPresenter::updatedAt(),
                                    TaskInfolistPresenter::deletedAt(),
                                    TaskInfolistPresenter::prunableWarning(),
                                ])
                                ->columnSpanFull()
                                ->columns(3),
                        ]),

                    Tab::make(__('resources/task/strings.infolist.tab_bi'))
                        ->icon('heroicon-o-chart-bar-square')
                        ->schema([
                            Section::make()
                                ->hiddenLabel()
                                ->schema([
                                    TaskInfolistPresenter::department(),
                                    TaskInfolistPresenter::unit(),
                                    TaskInfolistPresenter::section(),
                                    TaskInfolistPresenter::project(),
                                    TaskInfolistPresenter::scheme(),
                                    TaskInfolistPresenter::actionSourceDomain(),
                                    TaskInfolistPresenter::actionSource(),
                                    TaskInfolistPresenter::collaborators(),
                                    TaskInfolistPresenter::responsibleUser(),
                                    TaskInfolistPresenter::state(),
                                    TaskInfolistPresenter::attachments(),
                                ])
                                ->columnSpanFull()
                                ->columns(2),
                        ]),
                ])
                ->columnSpanFull()
                ->persistTabInQueryString(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TaskTablePresenter::id(),
                TaskTablePresenter::title(),
                TaskTablePresenter::status(),
                TaskTablePresenter::creator(),
                TaskTablePresenter::assignee(),
                TaskTablePresenter::isDelegated(),
                TaskTablePresenter::deadline(),
                TaskTablePresenter::description(),
                TaskTablePresenter::department(),
                TaskTablePresenter::unit(),
                TaskTablePresenter::project(),
                TaskTablePresenter::scheme(),
                TaskTablePresenter::state(),
                TaskTablePresenter::responsibleUser(),
                TaskTablePresenter::deletedAt(),
                TaskTablePresenter::prunableWarning(),
                TaskTablePresenter::createdAt(),
            ])
            ->groups([
                TaskTablePresenter::statusGroup(),
                TaskTablePresenter::creatorGroup(),
                TaskTablePresenter::assigneeGroup(),
            ])
            ->filters([
                TrashedFilter::make(),
                TaskTablePresenter::statusFilter(),
                TaskTablePresenter::creatorFilter(),
                TaskTablePresenter::assigneeFilter(),
                TaskTablePresenter::delegatedFilter(),
                self::createdAtFilter(),
                TaskTablePresenter::overdueFilter(),
                TaskTablePresenter::pruningSoonFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction()->visible(fn($record) => !$record->trashed()),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions(self::bulkActions(TaskExporter::class))
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
