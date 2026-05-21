<?php
namespace App\Filament\Resources\UserResource\RelationManagers;
use Filament\Resources\RelationManagers\RelationManager;
use App\Filament\Resources\OnboardingResource\Schemas\OnboardingFormPresenter;
use App\Filament\Resources\OnboardingResource\Schemas\OnboardingInfolistPresenter;
use App\Filament\Resources\OnboardingResource\Schemas\OnboardingTablePresenter;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Tables\Enums\RecordActionsPosition;
use App\Traits\FilamentActions;
class OnboardingsRelationManager extends RelationManager
{
    use FilamentActions;
    protected static string $relationship = 'onboardings';
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Onboarding/strings.form.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    OnboardingFormPresenter::extras(),
                    OnboardingFormPresenter::guides(),
                    OnboardingFormPresenter::hydrateGuidesWithFileMeta(),
                    OnboardingFormPresenter::isActive(),
                    OnboardingFormPresenter::mission(),
                    OnboardingFormPresenter::schedule(),
                    OnboardingFormPresenter::userId(),
                    OnboardingFormPresenter::videos(),
                    OnboardingFormPresenter::vision(),
                    OnboardingFormPresenter::welcome(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public static function getModelLabel(): string
    {
        return __('resources/Onboarding/strings.label');
    }
    public static function getPluralModelLabel(): string
    {
        return __('resources/Onboarding/strings.plural_label');
    }
    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('resources/Onboarding/strings.plural_label');
    }
    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Onboarding/strings.infolist.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    OnboardingInfolistPresenter::createdAt(),
                    OnboardingInfolistPresenter::extras(),
                    OnboardingInfolistPresenter::guides(),
                    OnboardingInfolistPresenter::isActive(),
                    OnboardingInfolistPresenter::mission(),
                    OnboardingInfolistPresenter::schedule(),
                    OnboardingInfolistPresenter::updatedAt(),
                    OnboardingInfolistPresenter::user(),
                    OnboardingInfolistPresenter::videos(),
                    OnboardingInfolistPresenter::vision(),
                    OnboardingInfolistPresenter::welcome(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                OnboardingTablePresenter::createdAt(),
                OnboardingTablePresenter::extrasCount(),
                OnboardingTablePresenter::guidesCount(),
                OnboardingTablePresenter::id(),
                OnboardingTablePresenter::isActive(),
                OnboardingTablePresenter::sections(),
                OnboardingTablePresenter::user(),
                OnboardingTablePresenter::videosCount(),
            ])
            ->searchable(false)
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-sparkles')->label(__('resources/Onboarding/strings.navigation.singular')),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark');
    }
}
