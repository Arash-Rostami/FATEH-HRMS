<?php
namespace App\Filament\Resources\DepartmentResource\RelationManagers;
use Filament\Resources\RelationManagers\RelationManager;
use App\Filament\Resources\GalleryResource\Schemas\GalleryFormPresenter;
use App\Filament\Resources\GalleryResource\Schemas\GalleryInfolistPresenter;
use App\Filament\Resources\GalleryResource\Schemas\GalleryTablePresenter;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Tables\Enums\RecordActionsPosition;
use App\Traits\FilamentActions;
class PhotosRelationManager extends RelationManager
{
    use FilamentActions;
    protected static string $relationship = 'photos';
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Photo/strings.form.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    GalleryFormPresenter::departmentId(),
                    GalleryFormPresenter::description(),
                    GalleryFormPresenter::eventDate(),
                    GalleryFormPresenter::path(),
                    GalleryFormPresenter::title(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public static function getModelLabel(): string
    {
        return __('resources/Photo/strings.label');
    }
    public static function getPluralModelLabel(): string
    {
        return __('resources/Photo/strings.plural_label');
    }
    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('resources/Photo/strings.plural_label');
    }
    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Photo/strings.infolist.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    GalleryInfolistPresenter::createdAt(),
                    GalleryInfolistPresenter::department(),
                    GalleryInfolistPresenter::description(),
                    GalleryInfolistPresenter::eventDate(),
                    GalleryInfolistPresenter::photos(),
                    GalleryInfolistPresenter::photosCount(),
                    GalleryInfolistPresenter::title(),
                    GalleryInfolistPresenter::updatedAt(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                GalleryTablePresenter::createdAt(),
                GalleryTablePresenter::department(),
                GalleryTablePresenter::eventDate(),
                GalleryTablePresenter::id(),
                GalleryTablePresenter::photosCount(),
                GalleryTablePresenter::preview(),
                GalleryTablePresenter::title(),
            ])
            ->searchable(false)
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-sparkles')->label(__('resources/Photo/strings.navigation.singular')),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark');
    }
}
