<?php
namespace App\Filament\Resources\DepartmentResource\RelationManagers;
use Filament\Resources\RelationManagers\RelationManager;
use App\Filament\Resources\UserResource\Schemas\UserFormPresenter;
use App\Filament\Resources\UserResource\Schemas\UserInfolistPresenter;
use App\Filament\Resources\UserResource\Schemas\UserTablePresenter;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Tables\Enums\RecordActionsPosition;
use App\Traits\FilamentActions;
class UserRelationManager extends RelationManager
{
    use FilamentActions;
    protected static string $relationship = 'user';
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/User/strings.form.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    UserFormPresenter::booking(),
                    UserFormPresenter::email(),
                    UserFormPresenter::extra(),
                    UserFormPresenter::maximum(),
                    UserFormPresenter::name(),
                    UserFormPresenter::password(),
                    UserFormPresenter::passwordConfirmation(),
                    UserFormPresenter::presence(),
                    UserFormPresenter::role(),
                    UserFormPresenter::status(),
                    UserFormPresenter::type(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public static function getModelLabel(): string
    {
        return __('resources/User/strings.label');
    }
    public static function getPluralModelLabel(): string
    {
        return __('resources/User/strings.plural_label');
    }
    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('resources/User/strings.plural_label');
    }
    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/User/strings.infolist.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    UserInfolistPresenter::id(),
                    UserInfolistPresenter::name(),
                    UserInfolistPresenter::email(),
                    UserInfolistPresenter::emailVerifiedAt(),
                    UserInfolistPresenter::type(),
                    UserInfolistPresenter::role(),
                    UserInfolistPresenter::status(),
                    UserInfolistPresenter::presence(),
                    UserInfolistPresenter::maximum(),
                    UserInfolistPresenter::booking(),
                    UserInfolistPresenter::extra(),
                    UserInfolistPresenter::lastSeen(),
                    UserInfolistPresenter::createdAt(),
                    UserInfolistPresenter::updatedAt(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                UserTablePresenter::avatar(),
                UserTablePresenter::createdAt(),
                UserTablePresenter::email(),
                UserTablePresenter::id(),
                UserTablePresenter::lastSeen(),
                UserTablePresenter::maximum(),
                UserTablePresenter::name(),
                UserTablePresenter::presence(),
                UserTablePresenter::role(),
                UserTablePresenter::status(),
                UserTablePresenter::type(),
            ])
            ->searchable(false)
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-sparkles')->label(__('resources/User/strings.navigation.singular')),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark');
    }
}
