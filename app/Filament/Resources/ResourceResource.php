<?php

namespace App\Filament\Resources;

use App\Enums\ResourceType;
use App\Filament\Resources\ResourceResource\Exports\ResourceExporter;
use App\Filament\Resources\ResourceResource\Pages\CreateResource;
use App\Filament\Resources\ResourceResource\Pages\EditResource;
use App\Filament\Resources\ResourceResource\Pages\ListResources;
use App\Filament\Resources\ResourceResource\RelationManagers\ReservationsRelationManager;
use App\Filament\Resources\ResourceResource\Schemas\ResourceFormPresenter;
use App\Filament\Resources\ResourceResource\Schemas\ResourceInfolistPresenter;
use App\Filament\Resources\ResourceResource\Schemas\ResourceTablePresenter;
use App\Models\Resource as ResourceModel;
use App\Traits\AuthorizesByPermission;
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

class ResourceResource extends Resource
{
    use FilamentActions, FilamentFilters, AuthorizesByPermission;

    protected static ?string $model = ResourceModel::class;
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-archive-box';
    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/resource/strings.form.section_main'))
                ->icon('heroicon-o-archive-box')
                ->schema([
                    ResourceFormPresenter::name(),
                    ResourceFormPresenter::type(),
                    ResourceFormPresenter::status(),
                ])
                ->columns(3),
            Section::make(__('resources/resource/strings.form.section_metadata'))
                ->icon('heroicon-o-adjustments-horizontal')
                ->schema([
                    ResourceFormPresenter::floor(),
                    ResourceFormPresenter::extension(),
                    ResourceFormPresenter::capacity(),
                    ResourceFormPresenter::divider(),
                    ResourceFormPresenter::notes(),
                ])
                ->columns(2),
            Section::make(__('resources/resource/strings.form.section_image'))
                ->icon('heroicon-o-photo')
                ->schema([ResourceFormPresenter::image()])
                ->columns(1),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('reservations');
    }

    public static function getGlobalSearchResultActions(Model $record): array
    {
        return [
            Action::make('edit')
                ->icon('heroicon-m-pencil')
                ->url(static::getUrl('edit', ['record' => $record])),
        ]
            ->validationMessages([
                'url' => __('resources/resource/strings.validation.edit.url')
            ]);
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('resources/resource/strings.fields.type') => ResourceType::tryFrom($record->type)?->getLabel() ?? $record->type,
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
        return ['name', 'type'];
    }

    public static function getModelLabel(): string
    {
        return __('resources/resource/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/resource/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListResources::route('/'),
            'create' => CreateResource::route('/create'),
            'edit' => EditResource::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/resource/strings.plural_label');
    }

    public static function getRelations(): array
    {
        return [ReservationsRelationManager::class];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->hiddenLabel()
                ->schema([
                    ResourceInfolistPresenter::name(),
                    ResourceInfolistPresenter::type(),
                    ResourceInfolistPresenter::status(),
                    ResourceInfolistPresenter::reservationsCount(),

                    ResourceInfolistPresenter::floor(),
                    ResourceInfolistPresenter::extension(),
                    ResourceInfolistPresenter::capacity(),
                    ResourceInfolistPresenter::notes(),

                    ResourceInfolistPresenter::createdAt(),
                    ResourceInfolistPresenter::updatedAt(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ResourceTablePresenter::id(),
                ResourceTablePresenter::name(),
                ResourceTablePresenter::type(),
                ResourceTablePresenter::status(),
                ResourceTablePresenter::floor(),
                ResourceTablePresenter::reservationsCount(),
                ResourceTablePresenter::createdAt(),
            ])
            ->filters([
                ResourceTablePresenter::typeFilter(),
                ResourceTablePresenter::statusFilter(),
                ResourceTablePresenter::hasReservationsFilter(),
                self::createdAtFilter(),
            ])
            ->groups([ResourceTablePresenter::typeGroup()])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions(self::bulkActions(ResourceExporter::class))
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('name')
            ->striped();
    }
}
