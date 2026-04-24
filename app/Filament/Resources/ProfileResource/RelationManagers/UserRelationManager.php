<?php

namespace App\Filament\Resources\ProfileResource\RelationManagers;

use App\Filament\Resources\UserResource\Schemas\UserFormPresenter;
use App\Filament\Resources\UserResource\Schemas\UserInfolistPresenter;
use App\Filament\Resources\UserResource\Schemas\UserTablePresenter;
use App\Traits\FilamentActions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class UserRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'user';

    protected static ?string $title = 'کاربر';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/user/strings.form.section_identity'))
                ->schema([
                    UserFormPresenter::name(),
                    UserFormPresenter::email(),
                ])
                ->columns(2),

            Section::make(__('resources/user/strings.form.section_access'))
                ->schema([
                    UserFormPresenter::type(),
                    UserFormPresenter::role(),
                    UserFormPresenter::status(),
                    UserFormPresenter::presence(),
                    UserFormPresenter::maximum(),
                ])
                ->columns(2),
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

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/user/strings.infolist.section_identity'))
                ->schema([
                    UserInfolistPresenter::id(),
                    UserInfolistPresenter::name(),
                    UserInfolistPresenter::email(),
                    UserInfolistPresenter::emailVerifiedAt(),
                ])
                ->columns(2),

            Section::make(__('resources/user/strings.infolist.section_access'))
                ->schema([
                    UserInfolistPresenter::type(),
                    UserInfolistPresenter::role(),
                    UserInfolistPresenter::status(),
                    UserInfolistPresenter::presence(),
                    UserInfolistPresenter::maximum(),
                ])
                ->columns(2),

            Section::make(__('resources/user/strings.infolist.section_meta'))
                ->schema([
                    UserInfolistPresenter::lastSeen(),
                    UserInfolistPresenter::createdAt(),
                    UserInfolistPresenter::updatedAt(),
                ])
                ->columns(3),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                UserTablePresenter::id(),
                UserTablePresenter::name(),
                UserTablePresenter::status(),
                UserTablePresenter::role(),
                UserTablePresenter::type(),
                UserTablePresenter::email(),
                UserTablePresenter::presence(),
                UserTablePresenter::lastSeen(),
            ])
            ->searchable(false)
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark');

    }
}
