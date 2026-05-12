<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EnergyTestResource\Exports\EnergyTestExporter;
use App\Filament\Resources\EnergyTestResource\Pages\{ListEnergyTests, ViewEnergyTest};
use App\Filament\Resources\EnergyTestResource\Schemas\{EnergyTestInfolistPresenter, EnergyTestTablePresenter};
use App\Filament\Resources\EnergyTestResource\Widgets\EnergyTestStatsWidget;
use App\Models\EnergyTest;
use App\Traits\FilamentActions;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EnergyTestResource extends Resource
{
    use FilamentActions;

    protected static ?string $model = EnergyTest::class;
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-bolt';
    protected static ?int $navigationSort = 11;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getModelLabel(): string
    {
        return __('resources/energy_test/strings.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/energy_test/strings.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/energy_test/strings.nav_group');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) EnergyTest::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['user.name'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return ($record->user?->name ?? '—') . ' — ' . number_format($record->overall_score ?? 0, 1);
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('resources/energy_test/strings.fields.overall_score') => number_format($record->overall_score ?? 0, 1),
            __('resources/energy_test/strings.fields.completed_at')  => $record->completed_at?->format('Y-m-d') ?? '—',
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('view', ['record' => $record]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                EnergyTestTablePresenter::id(),
                EnergyTestTablePresenter::user(),
                EnergyTestTablePresenter::overallScore(),
                EnergyTestTablePresenter::mindScore(),
                EnergyTestTablePresenter::emotionScore(),
                EnergyTestTablePresenter::physiqueScore(),
                EnergyTestTablePresenter::soulScore(),
                EnergyTestTablePresenter::monthIndex(),
                EnergyTestTablePresenter::completedAt(),
                EnergyTestTablePresenter::createdAt(),
            ])
            ->groups([
                EnergyTestTablePresenter::userGroup(),
                EnergyTestTablePresenter::monthGroup(),
            ])
            ->filters([
                EnergyTestTablePresenter::userFilter(),
                EnergyTestTablePresenter::lastMonthFilter(),
                EnergyTestTablePresenter::lowScoreFilter(),
                EnergyTestTablePresenter::scoreRangeFilter(),
                EnergyTestTablePresenter::dateRangeFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                ViewAction::make()->slideOver(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions(self::bulkActions(EnergyTestExporter::class))
            ->emptyStateIcon('heroicon-o-bolt')
            ->defaultSort('completed_at', 'desc')
            ->striped();
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/energy_test/strings.infolist.section_meta'))
                ->icon('heroicon-o-user')
                ->schema([
                    EnergyTestInfolistPresenter::user(),
                    EnergyTestInfolistPresenter::monthIndex(),
                    EnergyTestInfolistPresenter::completedAt(),
                ])
                ->columns(3),

            Section::make(__('resources/energy_test/strings.infolist.section_scores'))
                ->icon('heroicon-o-chart-bar')
                ->schema([
                    EnergyTestInfolistPresenter::overallScore(),
                    EnergyTestInfolistPresenter::mindScore(),
                    EnergyTestInfolistPresenter::emotionScore(),
                    EnergyTestInfolistPresenter::physiqueScore(),
                    EnergyTestInfolistPresenter::soulScore(),
                ])
                ->columns(5),

            Section::make(__('resources/energy_test/strings.infolist.section_answers'))
                ->icon('heroicon-o-clipboard-document-list')
                ->schema([
                    EnergyTestInfolistPresenter::answers(),
                    EnergyTestInfolistPresenter::createdAt(),
                ])
                ->columns(1)
                ->collapsed(),
        ]);
    }

    public static function getWidgets(): array
    {
        return [EnergyTestStatsWidget::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEnergyTests::route('/'),
            'view'  => ViewEnergyTest::route('/{record}'),
        ];
    }
}
