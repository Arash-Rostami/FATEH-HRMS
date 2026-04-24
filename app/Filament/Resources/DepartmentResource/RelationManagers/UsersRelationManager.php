<?php

namespace App\Filament\Resources\DepartmentResource\RelationManagers;

use App\Filament\Resources\UserResource\Schemas\UserInfolistPresenter;
use App\Filament\Resources\UserResource\Schemas\UserTablePresenter;
use App\Traits\FilamentActions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UsersRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'user';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/department/strings.relations.users');
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/department/strings.relations.user_details'))
                ->icon('heroicon-o-user')
                ->schema([
                    UserInfolistPresenter::name(),
                    UserInfolistPresenter::email(),
                    UserInfolistPresenter::type(),
                    UserInfolistPresenter::role(),
                    UserInfolistPresenter::status(),
                    UserInfolistPresenter::lastSeen(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        $unassign = fn(Model $user) => $user->profile?->update(['department_id' => null]);

        return $table
            ->recordTitleAttribute('name')
            ->columns([
                UserTablePresenter::name(),
                UserTablePresenter::status(),
                UserTablePresenter::role(),
                UserTablePresenter::email(),
                UserTablePresenter::lastSeen(),
            ])
            ->recordActions([
                self::viewAction(),
                self::unassignAction($unassign),
            ])
            ->groupedBulkActions([
                self::bulkUnassignAction($unassign),
            ])
            ->defaultSort('name');
    }
}
