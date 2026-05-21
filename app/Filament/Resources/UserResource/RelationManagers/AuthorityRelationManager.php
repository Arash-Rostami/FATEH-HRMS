<?php
namespace App\Filament\Resources\UserResource\RelationManagers;
use Filament\Resources\RelationManagers\RelationManager;
use App\Filament\Resources\AuthorityResource\Schemas\AuthorityFormPresenter;
use App\Filament\Resources\AuthorityResource\Schemas\AuthorityInfolistPresenter;
use App\Filament\Resources\AuthorityResource\Schemas\AuthorityTablePresenter;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Tables\Enums\RecordActionsPosition;
use App\Traits\FilamentActions;
class AuthorityRelationManager extends RelationManager
{
    use FilamentActions;
    protected static string $relationship = 'authority';
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Authority/strings.form.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    AuthorityFormPresenter::approvedDelegation(),
                    AuthorityFormPresenter::coDelegate(),
                    AuthorityFormPresenter::departmentId(),
                    AuthorityFormPresenter::duty(),
                    AuthorityFormPresenter::executionProcedure(),
                    AuthorityFormPresenter::impactScore(),
                    AuthorityFormPresenter::proposedDelegation(),
                    AuthorityFormPresenter::repeatFrequency(),
                    AuthorityFormPresenter::subDuty(),
                    AuthorityFormPresenter::userId(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public static function getModelLabel(): string
    {
        return __('resources/Authority/strings.label');
    }
    public static function getPluralModelLabel(): string
    {
        return __('resources/Authority/strings.plural_label');
    }
    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('resources/Authority/strings.plural_label');
    }
    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Authority/strings.infolist.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    AuthorityInfolistPresenter::duty(),
                    AuthorityInfolistPresenter::department(),
                    AuthorityInfolistPresenter::user(),
                    AuthorityInfolistPresenter::subDuty(),
                    AuthorityInfolistPresenter::executionProcedure(),
                    AuthorityInfolistPresenter::repeatFrequency(),
                    AuthorityInfolistPresenter::impactScore(),
                    AuthorityInfolistPresenter::proposedDelegation(),
                    AuthorityInfolistPresenter::approvedDelegation(),
                    AuthorityInfolistPresenter::coDelegate(),
                    AuthorityInfolistPresenter::createdAt(),
                    AuthorityInfolistPresenter::updatedAt(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                AuthorityTablePresenter::approvedDelegation(),
                AuthorityTablePresenter::createdAt(),
                AuthorityTablePresenter::department(),
                AuthorityTablePresenter::duty(),
                AuthorityTablePresenter::id(),
                AuthorityTablePresenter::impactScore(),
                AuthorityTablePresenter::subDuty(),
                AuthorityTablePresenter::user(),
            ])
            ->searchable(false)
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-sparkles')->label(__('resources/Authority/strings.navigation.singular')),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark');
    }
}
