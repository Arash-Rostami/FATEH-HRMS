<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EnergyResource\Exports\EnergyTestExporter;
use App\Traits\AuthorizesByPermission;
use App\Filament\Resources\EnergyResource\Pages\{ListEnergyTests, ViewEnergyTest};
use App\Filament\Resources\EnergyResource\Schemas\{EnergyTestInfolistPresenter, EnergyTestTablePresenter};
use App\Models\EnergyTest;
use App\Traits\FilamentActions;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EnergyTestResource extends Resource
{
    use FilamentActions, AuthorizesByPermission;

    protected static ?string $model = EnergyTest::class;
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-bolt';
    protected static ?int $navigationSort = 21;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('resources/energy/strings.fields.overall_score') => number_format($record->overall_score ?? 0, 1),
            __('resources/energy/strings.fields.completed_at') => $record->completed_at?->format('Y-m-d') ?? '—',
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return ($record->user?->name ?? '—') . ' — ' . number_format($record->overall_score ?? 0, 1);
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('view', ['record' => $record]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['user.name'];
    }

    public static function getModelLabel(): string
    {
        return 'پرسشنامه انرژی';
    }


    public static function getNavigationGroup(): ?string
    {
        return __('resources/energy/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEnergyTests::route('/'),
            'view' => ViewEnergyTest::route('/{record}'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return 'پرسشنامه انرژی';
    }


    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->hiddenLabel()
                ->schema([
                    Section::make()
                        ->hiddenLabel()
                        ->schema([
                            EnergyTestInfolistPresenter::user(),
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                EnergyTestTablePresenter::id(),
                EnergyTestTablePresenter::user(),
                EnergyTestTablePresenter::overallScore(),
                EnergyTestTablePresenter::physiqueScore(),
                EnergyTestTablePresenter::emotionScore(),
                EnergyTestTablePresenter::mindScore(),
                EnergyTestTablePresenter::soulScore(),
                EnergyTestTablePresenter::completedAt(),
                EnergyTestTablePresenter::createdAt(),
            ])
            ->groups([
                EnergyTestTablePresenter::userGroup(),
                EnergyTestTablePresenter::monthGroup(),
            ])
            ->filters([
                EnergyTestTablePresenter::scoreRangeFilter(),
                EnergyTestTablePresenter::lowScoreFilter(),
                EnergyTestTablePresenter::dateRangeFilter(),
                EnergyTestTablePresenter::lastMonthFilter(),
                EnergyTestTablePresenter::userFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions(self::bulkActions(EnergyTestExporter::class))
            ->emptyStateIcon('heroicon-o-bolt')
            ->defaultSort('completed_at', 'desc')
            ->striped();
    }
}
