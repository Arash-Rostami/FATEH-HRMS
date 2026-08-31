<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Exports\TaskExporter;
use App\Filament\Resources\TaskResource\Pages\{CreateTask, EditTask, ListTasks};
use App\Filament\Resources\TaskResource\Schemas\{TaskFormPresenter, TaskInfolistPresenter, TaskTablePresenter};
use App\Models\Task;
use App\Services\ProjectTask\ApproveTaskAction;
use App\Support\TaskAccessPolicy;
use App\Traits\AuthorizesByPermission;
use App\Traits\FilamentActions;
use App\Traits\FilamentAdminGuide;
use App\Traits\FilamentFilters;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
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
    use FilamentAdminGuide, FilamentActions, FilamentFilters, AuthorizesByPermission;

    protected static ?string $model = Task::class;
    protected static ?string $recordTitleAttribute = 'title';
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-view-columns';
    protected static ?int $navigationSort = 1;

    protected static array $guide = [
        ['label' => 'بررسی', 'icon' => 'menu_book', 'view' => 'filament.resources.task.guide.overview'],
        ['label' => 'زبانه‌های فهرست', 'icon' => 'tab', 'view' => 'filament.resources.task.guide.list-tabs'],
        ['label' => 'عملیات ادمین', 'icon' => 'admin_panel_settings', 'view' => 'filament.resources.task.guide.admin-ops'],
        ['label' => 'تجربهٔ کاربر', 'icon' => 'visibility', 'view' => 'filament.resources.task.guide.user'],
        ['label' => 'پروژه و فعالیت‌ها', 'icon' => 'workspaces', 'view' => 'filament.resources.task.guide.project-activity'],
    ];

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
                                ])
                                ->columns(1),

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
                                    TaskFormPresenter::meta(),
                                    TaskFormPresenter::attachments(),
                                    TaskFormPresenter::checklist(),
                                ])
                                ->columns(2),
                        ]),

                    Tab::make(__('resources/task/strings.form.tab_project'))
                        ->icon('heroicon-o-rectangle-stack')
                        ->schema([
                            Section::make(__('resources/task/strings.form.section_project'))
                                ->icon('heroicon-o-rectangle-stack')
                                ->schema([
                                    TaskFormPresenter::projectId(),
                                    TaskFormPresenter::priority(),
                                    TaskFormPresenter::labels(),
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
            ->with(['creator', 'assignee', 'detail', 'detail.department', 'detail.responsibleUser', 'project']);
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
                                    TaskInfolistPresenter::approval(),
                                    TaskInfolistPresenter::creator(),
                                    TaskInfolistPresenter::assignee(),
                                    TaskInfolistPresenter::delegatedIcon(),

                                    TaskInfolistPresenter::title(),
                                    TaskInfolistPresenter::description(),
                                    TaskInfolistPresenter::deadline(),

                                    TaskInfolistPresenter::createdAt(),
                                    TaskInfolistPresenter::updatedAt(),
                                    TaskInfolistPresenter::lastTouchedBy(),
                                    TaskInfolistPresenter::deletedAt(),
                                    TaskInfolistPresenter::archivedAt(),
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
                                    TaskInfolistPresenter::meta(),
                                    TaskInfolistPresenter::attachments(),
                                ])
                                ->columnSpanFull()
                                ->columns(2),
                        ]),

                    Tab::make(__('resources/task/strings.form.tab_project'))
                        ->icon('heroicon-o-rectangle-stack')
                        ->schema([
                            Section::make()
                                ->hiddenLabel()
                                ->schema([
                                    TaskInfolistPresenter::linkedProject(),
                                    TaskInfolistPresenter::priority(),
                                    TaskInfolistPresenter::labels(),
                                    TaskInfolistPresenter::checklistCompletion(),
                                ])
                                ->columnSpanFull()
                                ->columns(2),

                            Section::make(__('resources/task/strings.infolist.section_activity'))
                                ->icon('heroicon-o-chat-bubble-left-right')
                                ->schema([
                                    TaskInfolistPresenter::activityStream(),
                                ])
                                ->columnSpanFull(),
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
                TaskTablePresenter::isArchived(),
                TaskTablePresenter::creator(),
                TaskTablePresenter::assignee(),
                TaskTablePresenter::isDelegated(),
                TaskTablePresenter::deadline(),
                TaskTablePresenter::description(),
                TaskTablePresenter::department(),
                TaskTablePresenter::unit(),
                TaskTablePresenter::project(),
                TaskTablePresenter::linkedProject(),
                TaskTablePresenter::priority(),
                TaskTablePresenter::labels(),
                TaskTablePresenter::scheme(),
                TaskTablePresenter::state(),
                TaskTablePresenter::responsibleUser(),
                TaskTablePresenter::deletedAt(),
                TaskTablePresenter::archivedAt(),
                TaskTablePresenter::approvedAt(),
                TaskTablePresenter::meta(),
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
                TaskTablePresenter::archivedFilter(),
                self::createdAtFilter(),
                TaskTablePresenter::overdueFilter(),
                TaskTablePresenter::pruningSoonFilter(),
                TaskTablePresenter::linkedProjectFilter(),
                TaskTablePresenter::priorityFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                Action::make('approve')
                    ->label(__('resources/task/strings.actions.approve'))
                    ->icon('heroicon-o-check-badge')
                    ->iconButton()
                    ->color('success')
                    ->tooltip(__('resources/task/strings.actions.approve'))
                    ->visible(fn($record) => $record->isPendingApproval()
                        && TaskAccessPolicy::canApprove($record, auth()->user()))
                    ->action(function (Model $record) {
                        if (app(ApproveTaskAction::class)->execute($record, auth()->user())) {
                            Notification::make()
                                ->success()
                                ->title(__('resources/task/strings.notifications.approved'))
                                ->send();
                        }
                    }),
                Action::make('tasksheet')
                    ->label('مشاهده تسک‌شیت')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->iconButton()
                    ->url(fn($record) => route('tasksheet', ['user' => $record->assigned_to ?? $record->user_id]))
                    ->openUrlInNewTab(),
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
