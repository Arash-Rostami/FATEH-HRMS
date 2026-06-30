<?php

namespace App\Filament\Resources\DepartmentResource\RelationManagers;

use App\Filament\Resources\AuthorityResource\Schemas\AuthorityFormPresenter;
use App\Filament\Resources\AuthorityResource\Schemas\AuthorityInfolistPresenter;
use App\Filament\Resources\AuthorityResource\Schemas\AuthorityTablePresenter;
use App\Traits\FilamentActions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AuthoritiesRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'authorities';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/authority/strings.form.section_general'))
                ->icon('heroicon-o-information-circle')
                ->schema([
                    AuthorityFormPresenter::userId(),
                    AuthorityFormPresenter::subDuty(),
                    AuthorityFormPresenter::duty(),
                ])
                ->columns(2),

            Section::make(__('resources/authority/strings.form.section_details'))
                ->icon('heroicon-o-chart-bar')
                ->schema([
                    AuthorityFormPresenter::executionProcedure(),
                    AuthorityFormPresenter::repeatFrequency(),
                    AuthorityFormPresenter::impactScore(),
                    AuthorityFormPresenter::proposedDelegation(),
                    AuthorityFormPresenter::approvedDelegation(),
                    AuthorityFormPresenter::coDelegate(),
                ])
                ->columns(2),
        ]);
    }

    public static function getModelLabel(): string
    {
        return __('resources/authority/strings.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/authority/strings.plural_label');
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/authority/strings.plural_label');
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/authority/strings.infolist.section_general'))
                ->icon('heroicon-o-information-circle')
                ->schema([
                    AuthorityInfolistPresenter::duty(),
                    AuthorityInfolistPresenter::user(),
                    AuthorityInfolistPresenter::subDuty(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make(__('resources/authority/strings.infolist.section_details'))
                ->icon('heroicon-o-chart-bar')
                ->schema([
                    AuthorityInfolistPresenter::executionProcedure(),
                    AuthorityInfolistPresenter::repeatFrequency(),
                    AuthorityInfolistPresenter::impactScore(),
                    AuthorityInfolistPresenter::proposedDelegation(),
                    AuthorityInfolistPresenter::approvedDelegation(),
                    AuthorityInfolistPresenter::coDelegate(),
                    AuthorityInfolistPresenter::createdAt(),
                    AuthorityInfolistPresenter::updatedAt(),
                ])
                ->columns(3)
                ->columnSpanFull(),

        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user']))
            ->columns([
                AuthorityTablePresenter::id(),
                AuthorityTablePresenter::duty(),
                AuthorityTablePresenter::user(),
                AuthorityTablePresenter::approvedDelegation(),
                AuthorityTablePresenter::impactScore(),
                AuthorityTablePresenter::subDuty(),
                AuthorityTablePresenter::createdAt(),
            ])
            ->groups([
                AuthorityTablePresenter::delegationGroup(),
                AuthorityTablePresenter::impactScoreGroup(),
                AuthorityTablePresenter::executionProcedureGroup(),
                AuthorityTablePresenter::repeatFrequencyGroup(),
            ])
            ->filters([
                AuthorityTablePresenter::approvedDelegationFilter(),
                AuthorityTablePresenter::impactScoreFilter(),
                AuthorityTablePresenter::executionProcedureFilter(),
                AuthorityTablePresenter::repeatFrequencyFilter(),
                AuthorityTablePresenter::subDutyFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
