<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages\{CreateProject, EditProject, ListProjects};
use App\Filament\Resources\ProjectResource\RelationManagers\TasksRelationManager;
use App\Filament\Resources\ProjectResource\Schemas\{ProjectFormPresenter, ProjectInfolistPresenter, ProjectTablePresenter};
use App\Models\Project;
use App\Traits\AuthorizesByPermission;
use App\Traits\FilamentActions;
use App\Traits\FilamentAdminGuide;
use App\Traits\FilamentFilters;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProjectResource extends Resource
{
    use FilamentAdminGuide, FilamentActions, FilamentFilters, AuthorizesByPermission;

    protected static ?string $model = Project::class;
    protected static ?string $recordTitleAttribute = 'name';
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 2;

    protected static array $guide = [
        ['label' => 'بررسی', 'icon' => 'menu_book', 'view' => 'filament.resources.project.guide.overview'],
        ['label' => 'دسترسی و اعضا', 'icon' => 'group', 'view' => 'filament.resources.project.guide.membership'],
        ['label' => 'کانال گفتگو', 'icon' => 'forum', 'view' => 'filament.resources.project.guide.channel'],
        ['label' => 'عملیات ادمین', 'icon' => 'admin_panel_settings', 'view' => 'filament.resources.project.guide.admin-ops'],
    ];

    public static function canForceDelete(Model $record): bool
    {
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/project/strings.form.section_meta'))
                ->icon('heroicon-o-rectangle-stack')
                ->schema([
                    ProjectFormPresenter::name(),
                    ProjectFormPresenter::owner(),
                ])
                ->columns(2),

            Section::make(__('resources/project/strings.form.section_audience'))
                ->icon('heroicon-o-users')
                ->description(__('resources/project/strings.hints.audience_empty'))
                ->schema([
                    ProjectFormPresenter::memberIds(),
                    ProjectFormPresenter::departments(),
                ])
                ->columns(2),

            ProjectFormPresenter::settings(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->with(['owner', 'channel'])
            ->withCount('tasks');
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('resources/project/strings.fields.owner') => $record->owner?->name ?? '—',
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name;
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug'];
    }

    public static function getModelLabel(): string
    {
        return __('resources/project/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/project/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/project/strings.plural_label');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->hiddenLabel()
                ->schema([
                    ProjectInfolistPresenter::name(),
                    ProjectInfolistPresenter::owner(),
                    ProjectInfolistPresenter::channel(),
                    ProjectInfolistPresenter::tasksCount(),
                    ProjectInfolistPresenter::progress(),
                    ProjectInfolistPresenter::memberIds(),
                    ProjectInfolistPresenter::departments(),
                    ProjectInfolistPresenter::settingsSummary(),
                    ProjectInfolistPresenter::createdAt(),
                    ProjectInfolistPresenter::updatedAt(),
                    ProjectInfolistPresenter::deletedAt(),
                    ProjectInfolistPresenter::prunableWarning(),
                ])
                ->columnSpanFull()
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ProjectTablePresenter::id(),
                ProjectTablePresenter::name(),
                ProjectTablePresenter::owner(),
                ProjectTablePresenter::audienceSummary(),
                ProjectTablePresenter::channel(),
                ProjectTablePresenter::tasksCount(),
                ProjectTablePresenter::progress(),
                ProjectTablePresenter::settingsSummary(),
                ProjectTablePresenter::createdAt(),
                ProjectTablePresenter::deletedAt(),
                ProjectTablePresenter::prunableWarning(),
            ])
            ->filters([
                ProjectTablePresenter::pruningSoonFilter(),
                self::createdAtFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                Action::make('tasksheet')
                    ->label('مشاهده تسک‌شیت')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->iconButton()
                    ->url(fn($record) => route('tasksheet', ['user' => $record->owner_id]))
                    ->openUrlInNewTab(),
                self::viewAction(),
                self::editAction(),
                self::deleteAction()->visible(fn($record) => !$record->trashed()),
                self::restoreAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions([
                self::bulkDeleteAction(),
            ])
            ->emptyStateIcon('heroicon-o-rectangle-stack')
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
            TasksRelationManager::class,
        ];
    }
}
