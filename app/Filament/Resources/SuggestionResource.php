<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SuggestionResource\Exports\SuggestionExporter;
use App\Filament\Resources\SuggestionResource\RelationManagers\ReviewsRelationManager;
use App\Filament\Resources\SuggestionResource\Pages\{CreateSuggestion, EditSuggestion, ListSuggestions, ViewSuggestion};
use App\Filament\Resources\SuggestionResource\Schemas\{SuggestionFormPresenter,
    SuggestionInfolistPresenter,
    SuggestionTablePresenter};
use App\Models\Suggestion;
use App\Traits\AuthorizesByPermission;
use App\Traits\FilamentActions;
use App\Traits\FilamentFilters;
use BackedEnum;
use Filament\Actions\Action;
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

class SuggestionResource extends Resource
{
    use FilamentActions, FilamentFilters, AuthorizesByPermission;

    protected static ?string $model = Suggestion::class;
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-light-bulb';
    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)
                ->schema([
                    Section::make(__('resources/suggestion/strings.form.section_targets'))
                        ->icon('heroicon-o-building-office-2')
                        ->schema([
                            SuggestionFormPresenter::departments(),
                            SuggestionFormPresenter::userId(),
                            SuggestionFormPresenter::selfFill(),
                            SuggestionFormPresenter::attachment(),

                        ])
                        ->columnSpan(1)
                        ->columns(2),

                    Section::make(__('resources/suggestion/strings.form.section_meta'))
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->schema([
                            SuggestionFormPresenter::purpose(),
                            SuggestionFormPresenter::divider(),
                            SuggestionFormPresenter::rule(),
                            SuggestionFormPresenter::divider(),
                            SuggestionFormPresenter::priority(),
                        ])
                        ->columnSpan(1)
                        ->columns(3),
                ])
                ->columnSpanFull(),

            Section::make(__('resources/suggestion/strings.form.section_main'))
                ->icon('heroicon-o-document-text')
                ->schema([
                    SuggestionFormPresenter::title(),
                    SuggestionFormPresenter::description(),
                ])
                ->columnSpanFull()
                ->columns(1),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['department',
                'user:id,name,email',
                'user.profile.department',
                'reviews.department',
                'reviews.user:id,name',
            ])
            ->withCount([
                'reviews as agree_count' => fn($q) => $q->whereRaw('LOWER(feedback) = ?', ['agree']),
                'reviews as neutral_count' => fn($q) => $q->whereRaw('LOWER(feedback) = ?', ['neutral']),
                'reviews as disagree_count' => fn($q) => $q->whereRaw('LOWER(feedback) = ?', ['disagree']),
            ]);
    }

    public static function getGlobalSearchResultActions(Model $record): array
    {
        return [
            Action::make('view')
                ->icon('heroicon-m-eye')
                ->url(static::getUrl('view', ['record' => $record])),
        ];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('resources/suggestion/strings.fields.submitter') => $record->user?->name ?? '—',
            __('resources/suggestion/strings.fields.stage') => Suggestion::STAGES[$record->stage] ?? $record->stage,
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->title;
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('view', ['record' => $record]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'description', 'user.name'];
    }

    public static function getModelLabel(): string
    {
        return __('resources/suggestion/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/suggestion/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSuggestions::route('/'),
            'create' => CreateSuggestion::route('/create'),
            'view' => ViewSuggestion::route('/{record}'),
            'edit' => EditSuggestion::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/suggestion/strings.plural_label');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()
                ->tabs([
                    Tab::make(__('resources/suggestion/strings.infolist.tab_overview'))
                        ->icon('heroicon-o-light-bulb')
                        ->schema([
                            Section::make(__('resources/suggestion/strings.infolist.section_workflow'))
                                ->icon('heroicon-o-arrow-path-rounded-square')
                                ->schema([SuggestionInfolistPresenter::workflow()])
                                ->collapsible(),

                            Section::make(__('resources/suggestion/strings.infolist.section_overview'))
                                ->icon('heroicon-o-light-bulb')
                                ->schema([
                                    SuggestionInfolistPresenter::title(),
                                    SuggestionInfolistPresenter::serial(),
                                    SuggestionInfolistPresenter::stage(),
                                    SuggestionInfolistPresenter::submitter(),
                                    SuggestionInfolistPresenter::submitterDept(),
                                    SuggestionInfolistPresenter::selfFill(),
                                    SuggestionInfolistPresenter::sentToCeo(),
                                    SuggestionInfolistPresenter::purpose(),
                                    SuggestionInfolistPresenter::rule(),
                                    SuggestionInfolistPresenter::departments(),
                                    SuggestionInfolistPresenter::deadline(),
                                    SuggestionInfolistPresenter::createdAt(),
                                    SuggestionInfolistPresenter::updatedAt(),
                                ])
                                ->columns(3),

                            Section::make(__('resources/suggestion/strings.infolist.section_content'))
                                ->icon('heroicon-o-document-text')
                                ->schema([
                                    SuggestionInfolistPresenter::description(),
                                    SuggestionInfolistPresenter::attachment(),
                                ])
                                ->columns(2),
                        ]),

                    Tab::make(__('resources/suggestion/strings.infolist.tab_reviews'))
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->schema([
                            Section::make(__('resources/suggestion/strings.infolist.section_reviews'))
                                ->icon('heroicon-o-chat-bubble-left-right')
                                ->schema([
                                    SuggestionInfolistPresenter::agreeCount(),
                                    SuggestionInfolistPresenter::neutralCount(),
                                    SuggestionInfolistPresenter::disagreeCount(),
                                    SuggestionInfolistPresenter::reviews(),
                                ])
                                ->columns(3),

                            Section::make(__('resources/suggestion/strings.infolist.section_decision'))
                                ->icon('heroicon-o-scale')
                                ->schema([
                                    SuggestionInfolistPresenter::comments(),
                                    SuggestionInfolistPresenter::referralDepts(),
                                    SuggestionInfolistPresenter::referralActions(),
                                ])
                                ->columns(1)
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
                SuggestionTablePresenter::serial(),
                SuggestionTablePresenter::title(),
                SuggestionTablePresenter::submitter(),
                SuggestionTablePresenter::stage(),
                SuggestionTablePresenter::agreeCount(),
                SuggestionTablePresenter::neutralCount(),
                SuggestionTablePresenter::disagreeCount(),
                SuggestionTablePresenter::attachment(),
                SuggestionTablePresenter::createdAt(),
            ])
            ->groups([
                SuggestionTablePresenter::stageGroup(),
                SuggestionTablePresenter::submitterGroup(),
            ])
            ->filters([
                SuggestionTablePresenter::submitterFilter(),
                SuggestionTablePresenter::departmentFilter(),
                SuggestionTablePresenter::selfFillFilter(),
                SuggestionTablePresenter::hasFileFilter(),
                self::createdAtFilter(),
                SuggestionTablePresenter::hasReferralFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions(self::bulkActions(SuggestionExporter::class))
            ->emptyStateIcon('heroicon-o-light-bulb')
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
            ReviewsRelationManager::class,
        ];
    }
}
