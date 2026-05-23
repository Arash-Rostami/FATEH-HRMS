<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Exports\PostExporter;
use App\Traits\AuthorizesByPermission;
use App\Filament\Resources\PostResource\Pages\{CreatePost, EditPost, ListPosts};
use App\Filament\Resources\PostResource\Schemas\{PostFormPresenter, PostInfolistPresenter, PostTablePresenter};
use App\Models\Post;
use App\Traits\FilamentActions;
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

class PostResource extends Resource
{
    use FilamentActions, FilamentFilters, AuthorizesByPermission;

    protected static ?string $model = Post::class;
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-megaphone';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/post/strings.form.section_content'))
                ->icon('heroicon-o-document-text')
                ->schema([
                    PostFormPresenter::title(),
                    PostFormPresenter::divider(),
                    PostFormPresenter::body(),
                ])
                ->columnSpan(2),
            Section::make(__('resources/post/strings.form.section_meta'))
                ->icon('heroicon-o-user')
                ->schema([
                    PostFormPresenter::userId(),
                    PostFormPresenter::divider(),
                    PostFormPresenter::pinned(),
                    PostFormPresenter::image(),
                ])
                ->columns(1),


        ])->columns(3);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
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
            __('resources/post/strings.fields.user') => $record->user?->name ?? '—',
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return strip_tags($record->title ?? '');
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'body', 'user.name'];
    }

    public static function getModelLabel(): string
    {
        return __('resources/post/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/post/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/post/strings.plural_label');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([

            Section::make()
                ->hiddenLabel()
                ->schema([
                    PostInfolistPresenter::title(),
                    PostInfolistPresenter::body(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make()
                ->hiddenLabel()
                ->schema([
                    PostInfolistPresenter::image(),
                    PostInfolistPresenter::user(),
                    PostInfolistPresenter::pinned(),
                    PostInfolistPresenter::createdAt(),
                    PostInfolistPresenter::updatedAt(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                PostTablePresenter::id(),
                PostTablePresenter::image(),
                PostTablePresenter::title(),
                PostTablePresenter::user(),
                PostTablePresenter::pinned(),
                PostTablePresenter::body(),
                PostTablePresenter::createdAt(),
            ])
            ->filters([
                PostTablePresenter::pinnedFilter(),
                PostTablePresenter::hasImageFilter(),
                self::createdAtFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions(self::bulkActions(PostExporter::class))
            ->emptyStateIcon('heroicon-o-document-text')
            ->defaultSort('pinned', 'desc')
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
