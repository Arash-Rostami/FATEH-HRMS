<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Exports\UserExporter;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use app\Filament\Resources\UserResource\RelationManagers\CredentialRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\ProfileRelationManager;
use App\Filament\Resources\UserResource\Schemas\UserFormPresenter;
use App\Filament\Resources\UserResource\Schemas\UserInfolistPresenter;
use App\Filament\Resources\UserResource\Schemas\UserTablePresenter;
use App\Models\User;
use App\Traits\AuthorizesByPermission;
use App\Traits\FilamentActions;
use App\Traits\FilamentFilters;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    use FilamentActions, FilamentFilters, AuthorizesByPermission;

    protected static ?string $model = User::class;
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-users';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/user/strings.form.section_identity'))
                ->schema([
                    UserFormPresenter::name(),
                    UserFormPresenter::email(),
                    UserFormPresenter::password(),
                    UserFormPresenter::passwordConfirmation(),
                ])->columns(1),

            Section::make(__('resources/user/strings.form.section_access'))
                ->schema([
                    UserFormPresenter::type(),
                    UserFormPresenter::role(),
                    UserFormPresenter::status(),
                    UserFormPresenter::presence(),
                    UserFormPresenter::maximum(),
                ])->columns(2),

            Section::make(__('resources/user/strings.form.section_booking'))
                ->schema([
                    UserFormPresenter::booking(),
                ])->columnSpanFull(),

            Section::make(__('resources/user/strings.form.section_extra'))
                ->schema([
                    UserFormPresenter::extra(),
                ])->columnSpanFull(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['profile', 'profile.department']);
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
            'ایمیل' => $record->email,
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
        return ['name', 'email'];
    }

    public static function getModelLabel(): string
    {
        return __('resources/user/strings.navigation.singular');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/user/strings.navigation.group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/user/strings.navigation.plural');
    }

    public static function getRelations(): array
    {
        return [
            ProfileRelationManager::class,
            CredentialRelationManager::class,
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->tabs([
                        Tabs\Tab::make(__('resources/user/strings.infolist.section_identity'))
                            ->icon('heroicon-o-user-circle')
                            ->schema([
                                Section::make(__('resources/user/strings.infolist.section_identity'))
                                    ->schema([
                                        UserInfolistPresenter::id(),
                                        UserInfolistPresenter::name(),
                                        UserInfolistPresenter::email(),
                                        UserInfolistPresenter::emailVerifiedAt(),
                                    ])->columns(2),

                                Section::make(__('resources/user/strings.infolist.section_access'))
                                    ->schema([
                                        UserInfolistPresenter::type(),
                                        UserInfolistPresenter::role(),
                                        UserInfolistPresenter::status(),
                                        UserInfolistPresenter::presence(),
                                        UserInfolistPresenter::maximum(),
                                    ])->columns(2),

                                Section::make(__('resources/user/strings.infolist.section_booking'))
                                    ->schema([
                                        UserInfolistPresenter::booking(),
                                    ])->columnSpanFull(),

                            ])
                            ->columnSpanFull(),

                        Tabs\Tab::make(__('resources/user/strings.infolist.section_extra'))
                            ->icon('heroicon-o-user-circle')
                            ->schema([
                                Section::make(__('resources/user/strings.infolist.section_extra'))
                                    ->schema([
                                        UserInfolistPresenter::extra(),
                                    ])->columnSpanFull(),

                                Section::make(__('resources/user/strings.infolist.section_meta'))
                                    ->schema([
                                        UserInfolistPresenter::lastSeen(),
                                        UserInfolistPresenter::createdAt(),
                                        UserInfolistPresenter::updatedAt(),
                                    ])->columns(3),
                            ])
                            ->columnSpanFull()
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                UserTablePresenter::id(),
                UserTablePresenter::avatar(),
                UserTablePresenter::name(),
                UserTablePresenter::status(),
                UserTablePresenter::role(),
                UserTablePresenter::type(),
                UserTablePresenter::email(),
                UserTablePresenter::presence(),
                UserTablePresenter::maximum(),
                UserTablePresenter::lastSeen(),
                UserTablePresenter::createdAt(),
            ])
            ->groups([
                UserTablePresenter::statusGroup(),
                UserTablePresenter::roleGroup(),
                UserTablePresenter::presenceGroup(),
            ])
            ->filters([
                UserTablePresenter::statusFilter(),
                UserTablePresenter::roleFilter(),
                UserTablePresenter::typeFilter(),
                UserTablePresenter::presenceFilter(),
                self::createdAtFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->striped()
            ->groupedBulkActions(self::bulkActions(UserExporter::class))
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('id', 'desc');
    }
}
