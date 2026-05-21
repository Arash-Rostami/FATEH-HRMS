<?php
namespace App\Filament\Resources\UserResource\RelationManagers;
use Filament\Resources\RelationManagers\RelationManager;
use App\Filament\Resources\SuggestionResource\Schemas\SuggestionFormPresenter;
use App\Filament\Resources\SuggestionResource\Schemas\SuggestionInfolistPresenter;
use App\Filament\Resources\SuggestionResource\Schemas\SuggestionTablePresenter;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Tables\Enums\RecordActionsPosition;
use App\Traits\FilamentActions;
class SuggestionsRelationManager extends RelationManager
{
    use FilamentActions;
    protected static string $relationship = 'suggestions';
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Suggestion/strings.form.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    SuggestionFormPresenter::attachment(),
                    SuggestionFormPresenter::departments(),
                    SuggestionFormPresenter::description(),
                    SuggestionFormPresenter::divider(),
                    SuggestionFormPresenter::priority(),
                    SuggestionFormPresenter::purpose(),
                    SuggestionFormPresenter::rule(),
                    SuggestionFormPresenter::selfFill(),
                    SuggestionFormPresenter::title(),
                    SuggestionFormPresenter::userId(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public static function getModelLabel(): string
    {
        return __('resources/Suggestion/strings.label');
    }
    public static function getPluralModelLabel(): string
    {
        return __('resources/Suggestion/strings.plural_label');
    }
    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('resources/Suggestion/strings.plural_label');
    }
    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Suggestion/strings.infolist.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    SuggestionInfolistPresenter::agreeCount(),
                    SuggestionInfolistPresenter::attachment(),
                    SuggestionInfolistPresenter::comments(),
                    SuggestionInfolistPresenter::createdAt(),
                    SuggestionInfolistPresenter::deadline(),
                    SuggestionInfolistPresenter::departments(),
                    SuggestionInfolistPresenter::description(),
                    SuggestionInfolistPresenter::disagreeCount(),
                    SuggestionInfolistPresenter::neutralCount(),
                    SuggestionInfolistPresenter::purpose(),
                    SuggestionInfolistPresenter::referralActions(),
                    SuggestionInfolistPresenter::referralDepts(),
                    SuggestionInfolistPresenter::reviews(),
                    SuggestionInfolistPresenter::rule(),
                    SuggestionInfolistPresenter::selfFill(),
                    SuggestionInfolistPresenter::sentToCeo(),
                    SuggestionInfolistPresenter::serial(),
                    SuggestionInfolistPresenter::stage(),
                    SuggestionInfolistPresenter::submitter(),
                    SuggestionInfolistPresenter::submitterDept(),
                    SuggestionInfolistPresenter::title(),
                    SuggestionInfolistPresenter::updatedAt(),
                    SuggestionInfolistPresenter::workflow(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                SuggestionTablePresenter::agreeCount(),
                SuggestionTablePresenter::attachment(),
                SuggestionTablePresenter::createdAt(),
                SuggestionTablePresenter::disagreeCount(),
                SuggestionTablePresenter::neutralCount(),
                SuggestionTablePresenter::serial(),
                SuggestionTablePresenter::stage(),
                SuggestionTablePresenter::submitter(),
                SuggestionTablePresenter::title(),
            ])
            ->searchable(false)
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-sparkles')->label(__('resources/Suggestion/strings.navigation.singular')),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark');
    }
}
