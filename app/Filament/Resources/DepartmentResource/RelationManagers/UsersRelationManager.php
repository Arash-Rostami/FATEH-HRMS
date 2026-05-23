<?php

namespace App\Filament\Resources\DepartmentResource\RelationManagers;

use App\Filament\Resources\UserResource\Schemas\UserFormPresenter;
use App\Filament\Resources\UserResource\Schemas\UserInfolistPresenter;
use App\Filament\Resources\UserResource\Schemas\UserTablePresenter;
use App\Traits\FilamentActions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UsersRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'user';

    public function form(Schema $schema): Schema
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

    public static function getModelLabel(): string
    {
        return __('resources/user/strings.navigation.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/user/strings.navigation.plural');
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/department/strings.relations.users');
    }

    public function infolist(Schema $schema): Schema
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
            ]);
    }

    public function table(Table $table): Table
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
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->striped()
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('id', 'desc');
    }
}
