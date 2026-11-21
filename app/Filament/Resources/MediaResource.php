<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MediaResource\Pages\CreateMedia;
use App\Filament\Resources\MediaResource\Pages\EditMedia;
use App\Filament\Resources\MediaResource\Pages\ListMedia;
use App\Filament\Resources\MediaResource\RelationManagers\EpisodesRelationManager;
use App\Filament\Resources\MediaResource\Schemas\MediaFormPresenter;
use App\Filament\Resources\MediaResource\Schemas\MediaTablePresenter;
use App\Models\Media;
use App\Traits\Filament\AuthorizesByPermission;
use App\Traits\Filament\FilamentActions;
use App\Traits\Filament\FilamentAdminGuide;
use App\Traits\Filament\FilamentFilters;
use App\Traits\Filament\FilamentVisibility;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MediaResource extends Resource
{
    use FilamentAdminGuide, FilamentActions, FilamentFilters, FilamentVisibility, AuthorizesByPermission;

    public static function shouldRegisterNavigation(): bool
    {
        return ! ReportResource::canViewAny() && ! tenantOffName(static::getSlug()) && static::canViewAny();
    }

    protected static ?string $model = Media::class;
    protected static ?string $recordTitleAttribute = 'title';
    protected static ?string $slug = 'media';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-play-circle';

    protected static array $guide = [
        ['label' => 'بررسی', 'icon' => 'video_library', 'view' => 'filament.resources.media.guide.overview'],
        ['label' => 'عملیات ادمین', 'icon' => 'admin_panel_settings', 'view' => 'filament.resources.media.guide.admin-ops'],
        ['label' => 'دسترسی واحدها', 'icon' => 'lock', 'view' => 'filament.resources.media.guide.visibility'],
        ['label' => 'زبانه‌های فهرست و فیلترها', 'icon' => 'filter_list', 'view' => 'filament.resources.media.guide.list-tabs'],
        ['label' => 'تجربهٔ کاربر', 'icon' => 'visibility', 'view' => 'filament.resources.media.guide.user'],
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()
                ->tabs([
                    Tab::make(__('resources/media/strings.form.tab_main'))
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Section::make(__('resources/media/strings.form.section_main'))
                                ->description(__('resources/media/strings.form.section_description'))
                                ->icon('heroicon-o-film')
                                ->schema([
                                    MediaFormPresenter::kind(),
                                    MediaFormPresenter::title(),
                                    MediaFormPresenter::topic(),
                                    MediaFormPresenter::icon(),
                                    MediaFormPresenter::divider(),
                                    MediaFormPresenter::departmentId(),
                                    MediaFormPresenter::departments(),
                                    MediaFormPresenter::userId(),
                                    MediaFormPresenter::active(),
                                    MediaFormPresenter::pinned(),
                                    MediaFormPresenter::divider(),
                                    MediaFormPresenter::reportDate(),
                                    MediaFormPresenter::expiresAt(),
                                    MediaFormPresenter::parentId(),
                                    MediaFormPresenter::reportIds(),
                                    MediaFormPresenter::divider(),
                                    MediaFormPresenter::description(),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),
                        ]),

                    Tab::make(__('resources/media/strings.form.tab_files'))
                        ->icon('heroicon-o-paper-clip')
                        ->schema([
                            Section::make(__('resources/media/strings.form.section_files'))
                                ->icon('heroicon-o-folder-open')
                                ->schema([
                                    MediaFormPresenter::coverImage(),
                                    MediaFormPresenter::divider(),
                                    MediaFormPresenter::filePath(),
                                    MediaFormPresenter::durationSeconds(),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),

                            Section::make(__('resources/media/strings.form.section_tracks'))
                                ->icon('heroicon-o-document-text')
                                ->schema([
                                    MediaFormPresenter::transcriptPath(),
                                    MediaFormPresenter::chaptersPath(),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull()
                ->persistTabInQueryString(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['department', 'user', 'parent']);
    }

    public static function getRelations(): array
    {
        return [
            EpisodesRelationManager::class,
        ];
    }

    public static function getGlobalSearchResultActions(Model $record): array
    {
        return [
            Action::make('edit')
                ->icon('heroicon-m-pencil')
                ->url(static::getUrl('edit', ['record' => $record])),
        ];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('resources/media/strings.table.parent') => $record->parent?->title ?? '-',
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
        return ['title', 'topic', 'description'];
    }

    public static function getModelLabel(): string
    {
        return __('resources/media/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/report/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMedia::route('/'),
            'create' => CreateMedia::route('/create'),
            'edit' => EditMedia::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/media/strings.plural_label');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                MediaTablePresenter::id(),
                MediaTablePresenter::title(),
                MediaTablePresenter::kind(),
                MediaTablePresenter::parent(),
                MediaTablePresenter::duration(),
                MediaTablePresenter::icon(),
                MediaTablePresenter::department(),
                MediaTablePresenter::audience(),
                MediaTablePresenter::user(),
                self::activeColumn(),
                MediaTablePresenter::pinned(),
                MediaTablePresenter::reportDate(),
                MediaTablePresenter::createdAt(),
            ])
            ->groups([
                MediaTablePresenter::activeGroup(),
                MediaTablePresenter::departmentGroup(),
                MediaTablePresenter::pinnedGroup(),
                MediaTablePresenter::kindGroup(),
            ])
            ->filters([
                MediaTablePresenter::activeFilter(),
                MediaTablePresenter::departmentFilter(),
                MediaTablePresenter::visibilityFilter(),
                MediaTablePresenter::pinnedFilter(),
                MediaTablePresenter::reportDateRangeFilter(),
                self::createdAtFilter(),
                MediaTablePresenter::kindFilter(),
                MediaTablePresenter::rootFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions([
                static::bulkDeleteAction(),
                MediaTablePresenter::shareWithDepartmentsBulkAction(),
                ...self::bulkVisibilityActions(),
            ])
            ->emptyStateIcon('heroicon-o-play-circle')
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
