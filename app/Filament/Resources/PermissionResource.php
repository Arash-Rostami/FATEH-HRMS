<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Exports\PermissionExporter;
use App\Filament\Resources\PermissionResource\Pages\CreatePermission;
use App\Filament\Resources\PermissionResource\Pages\EditPermission;
use App\Filament\Resources\PermissionResource\Pages\ListPermissions;
use App\Filament\Resources\PermissionResource\Schemas\PermissionFormPresenter;
use App\Filament\Resources\PermissionResource\Schemas\PermissionInfolistPresenter;
use App\Filament\Resources\PermissionResource\Schemas\PermissionTablePresenter;
use App\Models\Permission;
use App\Traits\AuthorizesByPermission;
use App\Traits\FilamentActions;
use App\Traits\FilamentFilters;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PermissionResource extends Resource
{
    use FilamentActions, FilamentFilters, AuthorizesByPermission;

    protected static ?string $model = Permission::class;
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-shield-check';
    protected static ?int $navigationSort = 11;

    public static function canViewAny(): bool
    {
        return (bool)Permission::forUser(Auth::id())?->is_super_admin;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/permission/strings.form.section_main'))
                ->icon('heroicon-o-shield-check')
                ->description(__('resources/permission/strings.form.section_description'))
                ->schema([
                    PermissionFormPresenter::user(),
                    PermissionFormPresenter::isSuperAdmin(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make(__('resources/permission/strings.form.section_super'))
                ->icon('heroicon-o-no-symbol')
                ->description(__('resources/permission/strings.form.super_hint'))
                ->schema([
                    PermissionFormPresenter::excludedModules(),
                ])
                ->visible(fn(Get $get) => (bool)$get('is_super_admin'))
                ->columnSpanFull(),

            Section::make(__('resources/permission/strings.form.section_abilities'))
                ->icon('heroicon-o-key')
                ->schema([
                    PermissionFormPresenter::abilities(),
                ])
                ->visible(fn(Get $get) => !$get('is_super_admin'))
                ->columnSpanFull(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user:id,name,email');
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
            __('resources/permission/strings.fields.user') => $record->user?->email,
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->user?->name ?? '-';
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['user.name', 'user.email'];
    }

    public static function getModelLabel(): string
    {
        return __('resources/permission/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/permission/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermissions::route('/'),
            'create' => CreatePermission::route('/create'),
            'edit' => EditPermission::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/permission/strings.plural_label');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->hiddenLabel()
                ->schema([
                    PermissionInfolistPresenter::user(),
                    PermissionInfolistPresenter::isSuperAdmin(),

                    PermissionInfolistPresenter::abilities(),
                    PermissionInfolistPresenter::excludedModules(),

                    PermissionInfolistPresenter::createdAt(),
                    PermissionInfolistPresenter::updatedAt(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                PermissionTablePresenter::id(),
                PermissionTablePresenter::user(),
                PermissionTablePresenter::isSuperAdmin(),
                PermissionTablePresenter::modulesCount(),
                PermissionTablePresenter::createdAt(),
            ])
            ->filters([
                PermissionTablePresenter::superOnlyFilter(),
                self::createdAtFilter(),
            ])
            ->groups([
                PermissionTablePresenter::moduleGroup(),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions(self::bulkActions(PermissionExporter::class))
            ->emptyStateIcon('heroicon-o-shield-exclamation')
            ->defaultSort('id', 'desc');
    }
}
