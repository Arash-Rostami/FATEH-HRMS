<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentResource\Exports\DepartmentExporter;
use App\Filament\Resources\DepartmentResource\Pages\CreateDepartment;
use App\Filament\Resources\DepartmentResource\Pages\EditDepartment;
use App\Filament\Resources\DepartmentResource\Pages\ListDepartments;
use App\Filament\Resources\DepartmentResource\RelationManagers\AuthoritiesRelationManager;
use App\Filament\Resources\DepartmentResource\RelationManagers\ReportsRelationManager;
use App\Filament\Resources\DepartmentResource\RelationManagers\UsersRelationManager;
use App\Filament\Resources\DepartmentResource\Schemas\DepartmentFormPresenter;
use App\Filament\Resources\DepartmentResource\Schemas\DepartmentInfolistPresenter;
use App\Filament\Resources\DepartmentResource\Schemas\DepartmentTablePresenter;
use App\Models\Department;
use App\Traits\AuthorizesByPermission;
use App\Traits\FilamentActions;
use App\Traits\FilamentAdminGuide;
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

class DepartmentResource extends Resource
{
    use FilamentAdminGuide, FilamentActions, FilamentFilters, AuthorizesByPermission;

    protected static ?string $model = Department::class;
    protected static ?string $recordTitleAttribute = 'name';
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?int $navigationSort = 3;

    protected static array $guide = [
        ['label' => 'بررسی', 'icon' => 'apartment', 'view' => 'filament.resources.department.guide.overview'],
        ['label' => 'ساختار و سلسله‌مراتب', 'icon' => 'account_tree', 'view' => 'filament.resources.department.guide.hierarchy'],
        ['label' => 'گزینه‌های تیکت', 'icon' => 'confirmation_number', 'view' => 'filament.resources.department.guide.ticket-options'],
        ['label' => 'عملیات ادمین', 'icon' => 'admin_panel_settings', 'view' => 'filament.resources.department.guide.admin-ops'],
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/department/strings.form.section_main'))
                ->icon('heroicon-o-building-office-2')
                ->description(__('resources/department/strings.form.section_description'))
                ->schema([
                    DepartmentFormPresenter::code(),
                    DepartmentFormPresenter::name(),
                    DepartmentFormPresenter::divider(),
                    DepartmentFormPresenter::description(),
                    DepartmentFormPresenter::units(),
                    DepartmentFormPresenter::sections(),
                    DepartmentFormPresenter::ticketOptions(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('users');
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
            __('resources/department/strings.fields.code') => $record->code,
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->description ?? $record->name;
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'name', 'description'];
    }

    public static function getModelLabel(): string
    {
        return __('resources/department/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/department/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDepartments::route('/'),
            'create' => CreateDepartment::route('/create'),
            'edit' => EditDepartment::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/department/strings.plural_label');
    }

    public static function getRelations(): array
    {
        return [
            UsersRelationManager::class,
            AuthoritiesRelationManager::class,
            ReportsRelationManager::class
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/department/strings.infolist.section_main'))
                ->icon('heroicon-o-building-office-2')
                ->schema([
                    DepartmentInfolistPresenter::code(),
                    DepartmentInfolistPresenter::name(),
                    DepartmentInfolistPresenter::usersCount(),
                    DepartmentInfolistPresenter::description(),
                    DepartmentInfolistPresenter::units(),
                    DepartmentInfolistPresenter::sections(),
                    DepartmentInfolistPresenter::createdAt(),
                    DepartmentInfolistPresenter::updatedAt(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                DepartmentTablePresenter::id(),
                DepartmentTablePresenter::code(),
                DepartmentTablePresenter::name(),
                DepartmentTablePresenter::description(),
                DepartmentTablePresenter::units(),
                DepartmentTablePresenter::sections(),
                DepartmentTablePresenter::usersCount(),
                DepartmentTablePresenter::unitsCount(),
                DepartmentTablePresenter::sectionsCount(),
                DepartmentTablePresenter::createdAt(),
            ])
            ->groups([DepartmentTablePresenter::usersCountGroup()])
            ->filters([
                DepartmentTablePresenter::hasUsersFilter(),
                self::createdAtFilter(),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions(self::bulkActions(DepartmentExporter::class))
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('name');
    }
}
