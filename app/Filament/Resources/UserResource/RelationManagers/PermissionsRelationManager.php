<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\PermissionResource\Schemas\PermissionFormPresenter;
use App\Filament\Resources\PermissionResource\Schemas\PermissionInfolistPresenter;
use App\Filament\Resources\PermissionResource\Schemas\PermissionTablePresenter;
use App\Models\Permission;
use App\Traits\FilamentActions;
use App\Traits\FilamentFilters;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PermissionsRelationManager extends RelationManager
{
    use FilamentActions, FilamentFilters;

    protected static string $relationship = 'permissions';

    public function canViewAny(): bool
    {
        // Developers are super-admin by role and may administer permissions.
        if (Auth::user()?->isDeveloper()) {
            return true;
        }

        // Permission rows are admin-only — hide when the owner is a developer or plain user.
        $owner = $this->getOwnerRecord();
        if (($owner->role ?? null) !== 'admin') {
            return false;
        }

        return (bool)Permission::forUser(Auth::id())?->is_super_admin;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/permission/strings.form.section_main'))
                ->icon('heroicon-o-shield-check')
                ->description(__('resources/permission/strings.form.section_description'))
                ->schema([
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

    public static function getModelLabel(): string
    {
        return __('resources/permission/strings.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/permission/strings.plural_label');
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/permission/strings.plural_label');
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->hiddenLabel()
                ->schema([
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                PermissionTablePresenter::id(),
                PermissionTablePresenter::isSuperAdmin(),
                PermissionTablePresenter::modulesCount(),
                PermissionTablePresenter::createdAt(),
            ])
            ->filters([
                PermissionTablePresenter::superOnlyFilter(),
                self::createdAtFilter(),
            ])
            ->headerActions([])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions([])
            ->emptyStateIcon('heroicon-o-shield-exclamation');
    }
}
