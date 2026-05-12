<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GalleryResource\Exports\GalleryExporter;
use App\Filament\Resources\GalleryResource\Pages\{CreateGallery, EditGallery, ListGalleries};
use App\Filament\Resources\GalleryResource\Schemas\{GalleryFormPresenter, GalleryInfolistPresenter, GalleryTablePresenter};
use App\Models\Photo;
use App\Traits\FilamentActions;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GalleryResource extends Resource
{
    use FilamentActions;

    protected static ?string $model = Photo::class;
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-photo';
    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return __('resources/gallery/strings.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/gallery/strings.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/gallery/strings.nav_group');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'description', 'department.name'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->title;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('resources/gallery/strings.fields.department') => $record->department?->name
                ?? __('resources/gallery/strings.fields.public_gallery'),
            __('resources/gallery/strings.fields.count')      => count($record->path ?? []),
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('department');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/gallery/strings.form.section_info'))
                ->icon('heroicon-o-information-circle')
                ->schema([
                    GalleryFormPresenter::title(),
                    GalleryFormPresenter::departmentId(),
                    GalleryFormPresenter::eventDate(),
                    GalleryFormPresenter::description(),
                ])
                ->columns(2),

            Section::make(__('resources/gallery/strings.form.section_photos'))
                ->icon('heroicon-o-photo')
                ->schema([
                    GalleryFormPresenter::path(),
                ])
                ->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                GalleryTablePresenter::id(),
                GalleryTablePresenter::preview(),
                GalleryTablePresenter::title(),
                GalleryTablePresenter::department(),
                GalleryTablePresenter::photosCount(),
                GalleryTablePresenter::eventDate(),
                GalleryTablePresenter::createdAt(),
            ])
            ->groups([
                GalleryTablePresenter::departmentGroup(),
            ])
            ->filters([
                GalleryTablePresenter::visibilityFilter(),
                GalleryTablePresenter::departmentFilter(),
                GalleryTablePresenter::eventDateRangeFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                ViewAction::make()->slideOver(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions(self::bulkActions(GalleryExporter::class))
            ->emptyStateIcon('heroicon-o-photo')
            ->defaultSort('event_date', 'desc')
            ->striped();
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/gallery/strings.infolist.section_info'))
                ->icon('heroicon-o-information-circle')
                ->schema([
                    GalleryInfolistPresenter::title(),
                    GalleryInfolistPresenter::description(),
                    GalleryInfolistPresenter::department(),
                    GalleryInfolistPresenter::eventDate(),
                    GalleryInfolistPresenter::photosCount(),
                ])
                ->columns(3),

            Section::make(__('resources/gallery/strings.infolist.section_photos'))
                ->icon('heroicon-o-photo')
                ->schema([
                    GalleryInfolistPresenter::photos(),
                ])
                ->columns(1),

            Section::make(__('resources/gallery/strings.infolist.section_meta'))
                ->icon('heroicon-o-clock')
                ->schema([
                    GalleryInfolistPresenter::createdAt(),
                    GalleryInfolistPresenter::updatedAt(),
                ])
                ->columns(2)
                ->collapsed(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListGalleries::route('/'),
            'create' => CreateGallery::route('/create'),
            'edit'   => EditGallery::route('/{record}/edit'),
        ];
    }
}
