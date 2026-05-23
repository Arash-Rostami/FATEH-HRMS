<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\EnergyResource\Schemas\EnergyTestInfolistPresenter;
use App\Filament\Resources\EnergyResource\Schemas\EnergyTestTablePresenter;
use App\Traits\FilamentActions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EnergyTestsRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'energyTests';

    public static function getModelLabel(): string
    {
        return __('resources/energy/strings.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/energy/strings.plural_label');
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/energy/strings.plural_label');
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->hiddenLabel()
                ->schema([
                    Section::make()
                        ->hiddenLabel()
                        ->schema([
                            EnergyTestInfolistPresenter::completedAt(),
                            EnergyTestInfolistPresenter::createdAt(),

                            EnergyTestInfolistPresenter::overallScore(),
                            EnergyTestInfolistPresenter::physiqueScore(),
                            EnergyTestInfolistPresenter::emotionScore(),
                            EnergyTestInfolistPresenter::mindScore(),
                            EnergyTestInfolistPresenter::soulScore(),
                            EnergyTestInfolistPresenter::questionsDetail(),

                            EnergyTestInfolistPresenter::answers(),
                        ])->columns(4),
                ])
                ->columnSpanFull()
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                EnergyTestTablePresenter::id(),
                EnergyTestTablePresenter::overallScore(),
                EnergyTestTablePresenter::physiqueScore(),
                EnergyTestTablePresenter::emotionScore(),
                EnergyTestTablePresenter::mindScore(),
                EnergyTestTablePresenter::soulScore(),
                EnergyTestTablePresenter::completedAt(),
                EnergyTestTablePresenter::createdAt(),
            ])
            ->groups([
                EnergyTestTablePresenter::monthGroup(),
            ])
            ->filters([
                EnergyTestTablePresenter::scoreRangeFilter(),
                EnergyTestTablePresenter::lowScoreFilter(),
                EnergyTestTablePresenter::dateRangeFilter(),
                EnergyTestTablePresenter::lastMonthFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bolt')
            ->defaultSort('completed_at', 'desc')
            ->striped();
    }
}
