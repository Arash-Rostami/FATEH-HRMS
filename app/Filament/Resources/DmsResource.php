<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DmsResource\Enums\DocumentStatus;
use App\Filament\Resources\DmsResource\Exports\DmsExporter;
use App\Filament\Resources\DmsResource\Pages\{CreateDms, EditDms, ListDms};
use App\Filament\Resources\DmsResource\RelationManagers\ReadsRelationManager;
use App\Filament\Resources\DmsResource\Schemas\{DmsFormPresenter, DmsInfolistPresenter, DmsTablePresenter};
use App\Models\DMS;
use App\Traits\AuthorizesByPermission;
use App\Traits\FilamentActions;
use App\Traits\FilamentFilters;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DmsResource extends Resource
{
    use FilamentActions, FilamentFilters, AuthorizesByPermission;

    protected static ?string $model = DMS::class;
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-folder-open';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/dms/strings.form.section_details'))
                ->icon('heroicon-o-document-text')
                ->schema([
                    DmsFormPresenter::title(),
                    DmsFormPresenter::code(),
                    DmsFormPresenter::version(),
                    DmsFormPresenter::status(),
                    DmsFormPresenter::divider(),
                    DmsFormPresenter::file(),
                    DmsFormPresenter::revision(),
                ])
                ->columns(2),

            Section::make(__('resources/dms/strings.form.section_ownership'))
                ->icon('heroicon-o-building-office')
                ->schema([
                    DmsFormPresenter::owners(),
                    DmsFormPresenter::users(),
                    DmsFormPresenter::divider(),
                    DmsFormPresenter::ownersPreview(),
                    DmsFormPresenter::tags(),
                    DmsFormPresenter::extra(),
                ])
                ->columns(2),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['reads.user']);
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('resources/dms/strings.fields.code') => $record->code,
            __('resources/dms/strings.fields.version') => $record->version,
            __('resources/dms/strings.fields.status') => DocumentStatus::tryFrom($record->status)?->getLabel() ?? $record->status,
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
        return ['title', 'code', 'version'];
    }

    public static function getModelLabel(): string
    {
        return __('resources/dms/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/dms/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDms::route('/'),
            'create' => CreateDms::route('/create'),
            'edit' => EditDms::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/dms/strings.plural_label');
    }

    public static function getRelations(): array
    {
        return [
            ReadsRelationManager::class,
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->hiddenLabel()
                ->schema([
                    DmsInfolistPresenter::title(),
                    DmsInfolistPresenter::code(),
                    DmsInfolistPresenter::version(),
                    DmsInfolistPresenter::status(),
                    DmsInfolistPresenter::file(),
                    DmsInfolistPresenter::revision(),

                    DmsInfolistPresenter::owners(),
                    DmsInfolistPresenter::usersCount(),
                    DmsInfolistPresenter::ownersPreview(),

                    DmsInfolistPresenter::tags(),
                    DmsInfolistPresenter::extra(),
                    DmsInfolistPresenter::createdAt(),
                    DmsInfolistPresenter::updatedAt(),
                ])
                ->columnSpanFull()
                ->columns(4),

            Section::make(__('resources/dms/strings.infolist.section_reads'))
                ->icon('heroicon-o-eye')
                ->schema([
                    DmsInfolistPresenter::readCount(),
                    DmsInfolistPresenter::reads(),
                ])
                ->columnSpanFull()
                ->collapsible()
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                DmsTablePresenter::id(),
                DmsTablePresenter::title(),
                DmsTablePresenter::code(),
                DmsTablePresenter::version(),
                DmsTablePresenter::status(),
                DmsTablePresenter::owners(),
                DmsTablePresenter::usersCount(),
                DmsTablePresenter::readCount(),
                DmsTablePresenter::createdAt(),
            ])
            ->groups([
                DmsTablePresenter::statusGroup(),
                DmsTablePresenter::ownersGroup(),
            ])
            ->filters([
                DmsTablePresenter::ownersFilter(),
                self::createdAtFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions(self::bulkActions(DmsExporter::class))
            ->emptyStateIcon('heroicon-o-archive-box')
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
