<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OnboardingResource\Exports\OnboardingExporter;
use App\Filament\Resources\OnboardingResource\Pages\{CreateOnboarding, EditOnboarding, ListOnboardings};
use App\Filament\Resources\OnboardingResource\Schemas\{OnboardingFormPresenter,
    OnboardingInfolistPresenter,
    OnboardingTablePresenter};
use App\Models\Onboarding;
use App\Traits\AuthorizesByPermission;
use App\Traits\FilamentActions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OnboardingResource extends Resource
{
    use FilamentActions, AuthorizesByPermission;

    protected static ?string $model = Onboarding::class;
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 4])
                ->schema([
                    Section::make(__('resources/onboarding/strings.form.section_settings'))
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
                            OnboardingFormPresenter::userId(),
                            OnboardingFormPresenter::isActive(),
                        ])
                        ->columnSpan(['default' => 1, 'lg' => 1]),

                    Tabs::make()
                        ->tabs([
                            Tab::make(__('resources/onboarding/strings.fields.welcome'))
                                ->icon('heroicon-o-hand-raised')
                                ->schema([OnboardingFormPresenter::welcome()]),

                            Tab::make(__('resources/onboarding/strings.fields.mission'))
                                ->icon('heroicon-o-flag')
                                ->schema([OnboardingFormPresenter::mission()]),

                            Tab::make(__('resources/onboarding/strings.fields.vision'))
                                ->icon('heroicon-o-eye')
                                ->schema([OnboardingFormPresenter::vision()]),

                            Tab::make(__('resources/onboarding/strings.fields.schedule'))
                                ->icon('heroicon-o-calendar-days')
                                ->schema([OnboardingFormPresenter::schedule()]),

                            Tab::make(__('resources/onboarding/strings.fields.videos'))
                                ->icon('heroicon-o-video-camera')
                                ->schema([OnboardingFormPresenter::videos()]),

                            Tab::make(__('resources/onboarding/strings.fields.guides'))
                                ->icon('heroicon-o-document-text')
                                ->schema([OnboardingFormPresenter::guides()]),

                            Tab::make(__('resources/onboarding/strings.fields.extras'))
                                ->icon('heroicon-o-squares-2x2')
                                ->schema([...OnboardingFormPresenter::extras()]),
                        ])
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 3,
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('user');
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->user?->name ?? __('resources/onboarding/strings.fields.global_audience');
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['user.name'];
    }

    public static function getModelLabel(): string
    {
        return __('resources/onboarding/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/onboarding/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOnboardings::route('/'),
            'create' => CreateOnboarding::route('/create'),
            'edit' => EditOnboarding::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/onboarding/strings.plural_label');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()
                ->tabs([
                    Tab::make(__('resources/onboarding/strings.infolist.section_meta'))
                        ->icon('heroicon-o-identification')
                        ->schema([
                            Section::make()
                                ->hiddenLabel()
                                ->schema([
                                    OnboardingInfolistPresenter::user(),
                                    OnboardingInfolistPresenter::isActive(),
                                    OnboardingInfolistPresenter::createdAt(),
                                    OnboardingInfolistPresenter::updatedAt(),
                                ])
                                ->columns(4),
                        ]),

                    Tab::make(__('resources/onboarding/strings.infolist.tab_content'))
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Section::make(__('resources/onboarding/strings.fields.welcome'))
                                ->icon('heroicon-o-hand-raised')
                                ->schema([OnboardingInfolistPresenter::welcome()])
                                ->collapsed(),

                            Section::make(__('resources/onboarding/strings.fields.mission'))
                                ->icon('heroicon-o-flag')
                                ->schema([OnboardingInfolistPresenter::mission()])
                                ->collapsed(),

                            Section::make(__('resources/onboarding/strings.fields.vision'))
                                ->icon('heroicon-o-eye')
                                ->schema([OnboardingInfolistPresenter::vision()])
                                ->collapsed(),

                            Section::make(__('resources/onboarding/strings.fields.schedule'))
                                ->icon('heroicon-o-calendar-days')
                                ->schema([OnboardingInfolistPresenter::schedule()])
                                ->collapsed(),

                            Section::make(__('resources/onboarding/strings.fields.videos'))
                                ->icon('heroicon-o-video-camera')
                                ->schema([OnboardingInfolistPresenter::videos()])
                                ->collapsed(),

                            Section::make(__('resources/onboarding/strings.fields.guides'))
                                ->icon('heroicon-o-document-text')
                                ->schema([OnboardingInfolistPresenter::guides()])
                                ->collapsed(),

                            Section::make(__('resources/onboarding/strings.fields.extras'))
                                ->icon('heroicon-o-squares-2x2')
                                ->schema([OnboardingInfolistPresenter::extras()])
                                ->collapsed(),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                OnboardingTablePresenter::id(),
                OnboardingTablePresenter::user(),
                OnboardingTablePresenter::isActive(),
                OnboardingTablePresenter::sections(),
                OnboardingTablePresenter::videosCount(),
                OnboardingTablePresenter::guidesCount(),
                OnboardingTablePresenter::extrasCount(),
                OnboardingTablePresenter::createdAt(),
            ])
            ->groups([
                OnboardingTablePresenter::audienceGroup(),
            ])
            ->filters([
                OnboardingTablePresenter::activeFilter(),
                OnboardingTablePresenter::audienceFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions(self::bulkActions(OnboardingExporter::class))
            ->emptyStateIcon('heroicon-o-academic-cap')
            ->defaultSort('is_active', 'desc')
            ->striped();
    }
}
