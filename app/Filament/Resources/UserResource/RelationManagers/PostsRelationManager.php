<?php
namespace App\Filament\Resources\UserResource\RelationManagers;
use Filament\Resources\RelationManagers\RelationManager;
use App\Filament\Resources\PostResource\Schemas\PostFormPresenter;
use App\Filament\Resources\PostResource\Schemas\PostInfolistPresenter;
use App\Filament\Resources\PostResource\Schemas\PostTablePresenter;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Tables\Enums\RecordActionsPosition;
use App\Traits\FilamentActions;
class PostsRelationManager extends RelationManager
{
    use FilamentActions;
    protected static string $relationship = 'posts';
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Post/strings.form.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    PostFormPresenter::body(),
                    PostFormPresenter::image(),
                    PostFormPresenter::pinned(),
                    PostFormPresenter::title(),
                    PostFormPresenter::userId(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public static function getModelLabel(): string
    {
        return __('resources/Post/strings.label');
    }
    public static function getPluralModelLabel(): string
    {
        return __('resources/Post/strings.plural_label');
    }
    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('resources/Post/strings.plural_label');
    }
    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Post/strings.infolist.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    PostInfolistPresenter::body(),
                    PostInfolistPresenter::createdAt(),
                    PostInfolistPresenter::image(),
                    PostInfolistPresenter::pinned(),
                    PostInfolistPresenter::title(),
                    PostInfolistPresenter::updatedAt(),
                    PostInfolistPresenter::user(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                PostTablePresenter::body(),
                PostTablePresenter::createdAt(),
                PostTablePresenter::id(),
                PostTablePresenter::image(),
                PostTablePresenter::pinned(),
                PostTablePresenter::title(),
                PostTablePresenter::user(),
            ])
            ->searchable(false)
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-sparkles')->label(__('resources/Post/strings.navigation.singular')),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark');
    }
}
