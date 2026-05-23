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
            Section::make(__('resources/energy/strings.label'))
                ->schema([
                    EnergyTestInfolistPresenter::overallScore(),
                    EnergyTestInfolistPresenter::mindScore(),
                    EnergyTestInfolistPresenter::emotionScore(),
                    EnergyTestInfolistPresenter::physiqueScore(),
                    EnergyTestInfolistPresenter::soulScore(),
                    EnergyTestInfolistPresenter::completedAt(),
                    EnergyTestInfolistPresenter::createdAt(),
                ])
                ->columnSpanFull()
                ->columns(2),

            Section::make(__('resources/energy/strings.fields.answers'))
                ->schema([
                    EnergyTestInfolistPresenter::answers(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                EnergyTestTablePresenter::id(),
                EnergyTestTablePresenter::overallScore(),
                EnergyTestTablePresenter::mindScore(),
                EnergyTestTablePresenter::emotionScore(),
                EnergyTestTablePresenter::physiqueScore(),
                EnergyTestTablePresenter::soulScore(),
                EnergyTestTablePresenter::completedAt(),
            ])
            ->filters([
                EnergyTestTablePresenter::dateRangeFilter(),
                EnergyTestTablePresenter::lowScoreFilter(),
                EnergyTestTablePresenter::scoreRangeFilter(),
            ])
            ->recordActions([
                self::viewAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('completed_at', 'desc');
    }
}
