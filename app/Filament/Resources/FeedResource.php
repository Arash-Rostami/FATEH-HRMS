<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeedResource\Exports\FeedExporter;
use App\Traits\AuthorizesByPermission;
use BackedEnum;
use App\Filament\Resources\FeedResource\Pages\{CreateFeed, EditFeed, ListFeeds};
use App\Filament\Resources\FeedResource\RelationManagers\{CommentsRelationManager, ReactionsRelationManager};
use App\Filament\Resources\FeedResource\Schemas\{FeedFormPresenter, FeedInfolistPresenter, FeedTablePresenter};
use App\Models\Feed;
use App\Traits\FilamentActions;
use App\Traits\FilamentFilters;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FeedResource extends Resource
{
    use FilamentActions, FilamentFilters, AuthorizesByPermission;

    protected static ?string $model = Feed::class;
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-rss';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/feed/strings.form.section_content'))
                ->icon('heroicon-o-document-text')
                ->schema([
                    FeedFormPresenter::content(),
                ])
                ->columns(1),

            Section::make(__('resources/feed/strings.form.section_author'))
                ->icon('heroicon-o-user')
                ->schema([
                    FeedFormPresenter::userId(),
                    FeedFormPresenter::category(),
                    FeedFormPresenter::divider(),
                    FeedFormPresenter::pollOptions(),
                    FeedFormPresenter::mediaImages(),
                    FeedFormPresenter::mediaVideos(),
                ])
                ->columns(2),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('user')
            ->withCount(['comments', 'reactions']);
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('resources/feed/strings.fields.user') => $record->user?->name ?? '—',
            __('resources/feed/strings.fields.category') => $record->category ?? '—',
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return strip_tags(mb_substr($record->content ?? '', 0, 80));
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['content', 'user.name'];
    }

    public static function getModelLabel(): string
    {
        return __('resources/feed/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/feed/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeeds::route('/'),
            'create' => CreateFeed::route('/create'),
            'edit' => EditFeed::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/feed/strings.plural_label');
    }

    public static function getRelations(): array
    {
        return [
            CommentsRelationManager::class,
            ReactionsRelationManager::class,
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->hiddenLabel()
                ->icon('heroicon-o-user')
                ->schema([
                    FeedInfolistPresenter::user(),
                    FeedInfolistPresenter::category(),
                    FeedInfolistPresenter::commentsCount(),
                    FeedInfolistPresenter::reactionsCount(),

                    FeedInfolistPresenter::content(),
                    FeedInfolistPresenter::pollOptions(),

                    FeedInfolistPresenter::mediaImages(),
                    FeedInfolistPresenter::mediaVideosCount(),

                    FeedInfolistPresenter::createdAt(),
                    FeedInfolistPresenter::updatedAt(),
                ])
                ->columns(4)
                ->columnSpanFull()
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                FeedTablePresenter::id(),
                FeedTablePresenter::mediaPreview(),
                FeedTablePresenter::user(),
                FeedTablePresenter::category(),
                FeedTablePresenter::content(),
                FeedTablePresenter::commentsCount(),
                FeedTablePresenter::reactionsCount(),
                FeedTablePresenter::mediaCount(),
                FeedTablePresenter::createdAt(),
            ])
            ->groups([
                FeedTablePresenter::categoryGroup(),
            ])
            ->filters([
                FeedTablePresenter::categoryFilter(),
                FeedTablePresenter::userFilter(),
                self::createdAtFilter(),
                FeedTablePresenter::hasMediaFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions(self::bulkActions(FeedExporter::class))
            ->emptyStateIcon('heroicon-o-newspaper')
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
