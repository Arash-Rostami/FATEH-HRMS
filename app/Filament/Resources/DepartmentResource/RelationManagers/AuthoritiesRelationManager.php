<?php

namespace App\Filament\Resources\DepartmentResource\RelationManagers;

use App\Filament\Resources\AuthorityResource\Schemas\AuthorityFormPresenter;
use App\Filament\Resources\AuthorityResource\Schemas\AuthorityInfolistPresenter;
use App\Filament\Resources\AuthorityResource\Schemas\AuthorityTablePresenter;
use App\Traits\FilamentActions;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AuthoritiesRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'authorities';

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

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/authority/strings.label'))
                ->schema([
                    AuthorityFormPresenter::userId(),
                    // Note: AuthorityFormPresenter::departmentId() excluded — auto-set by RelationManager
                    AuthorityFormPresenter::impactScore(),
                    AuthorityFormPresenter::executionProcedure(),
                    AuthorityFormPresenter::repeatFrequency(),
                    AuthorityFormPresenter::proposedDelegation(),
                    AuthorityFormPresenter::approvedDelegation(),
                    AuthorityFormPresenter::coDelegate(),
                    AuthorityFormPresenter::subDuty(),
                ])
                ->columns(2),

            Section::make(__('resources/authority/strings.fields.duty'))
                ->schema([
                    AuthorityFormPresenter::duty(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/authority/strings.label'))
                ->schema([
                    AuthorityInfolistPresenter::user(),
                    AuthorityInfolistPresenter::department(),
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

            Section::make(__('resources/authority/strings.fields.duty'))
                ->schema([
                    AuthorityInfolistPresenter::duty(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('duty')
            ->columns([
                AuthorityTablePresenter::id(),
                AuthorityTablePresenter::user(),
                AuthorityTablePresenter::duty(),
                AuthorityTablePresenter::subDuty(),
                AuthorityTablePresenter::impactScore(),
                AuthorityTablePresenter::approvedDelegation(),
                AuthorityTablePresenter::createdAt(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-sparkles')
                    ->label('افزودن اختیار'),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('created_at', 'desc');
    }
}
