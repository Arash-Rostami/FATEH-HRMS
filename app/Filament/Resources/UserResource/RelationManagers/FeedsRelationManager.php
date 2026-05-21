<?php
namespace App\Filament\Resources\UserResource\RelationManagers;
use Filament\Resources\RelationManagers\RelationManager;
use App\Filament\Resources\FeedResource\Schemas\FeedFormPresenter;
use App\Filament\Resources\FeedResource\Schemas\FeedInfolistPresenter;
use App\Filament\Resources\FeedResource\Schemas\FeedTablePresenter;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Tables\Enums\RecordActionsPosition;
use App\Traits\FilamentActions;
class FeedsRelationManager extends RelationManager
{
    use FilamentActions;
    protected static string $relationship = 'feeds';
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Feed/strings.form.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    FeedFormPresenter::category(),
                    FeedFormPresenter::content(),
                    FeedFormPresenter::mediaImages(),
                    FeedFormPresenter::mediaVideos(),
                    FeedFormPresenter::mergeMediaPaths(),
                    FeedFormPresenter::pollOptions(),
                    FeedFormPresenter::splitMediaPaths(),
                    FeedFormPresenter::userId(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public static function getModelLabel(): string
    {
        return __('resources/Feed/strings.label');
    }
    public static function getPluralModelLabel(): string
    {
        return __('resources/Feed/strings.plural_label');
    }
    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('resources/Feed/strings.plural_label');
    }
    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Feed/strings.infolist.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    FeedInfolistPresenter::category(),
                    FeedInfolistPresenter::commentsCount(),
                    FeedInfolistPresenter::content(),
                    FeedInfolistPresenter::createdAt(),
                    FeedInfolistPresenter::mediaImages(),
                    FeedInfolistPresenter::mediaVideosCount(),
                    FeedInfolistPresenter::pollOptions(),
                    FeedInfolistPresenter::reactionsCount(),
                    FeedInfolistPresenter::updatedAt(),
                    FeedInfolistPresenter::user(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                FeedTablePresenter::category(),
                FeedTablePresenter::commentsCount(),
                FeedTablePresenter::content(),
                FeedTablePresenter::createdAt(),
                FeedTablePresenter::id(),
                FeedTablePresenter::mediaCount(),
                FeedTablePresenter::mediaPreview(),
                FeedTablePresenter::reactionsCount(),
                FeedTablePresenter::user(),
            ])
            ->searchable(false)
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-sparkles')->label(__('resources/Feed/strings.navigation.singular')),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark');
    }
}
