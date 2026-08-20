<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\SuggestionResource\Pages\CreateSuggestion;
use App\Filament\Resources\SuggestionResource\Schemas\SuggestionFormPresenter;
use App\Filament\Resources\SuggestionResource\Schemas\SuggestionInfolistPresenter;
use App\Filament\Resources\SuggestionResource\Schemas\SuggestionTablePresenter;
use App\Models\Suggestion;
use App\Traits\FilamentActions;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SuggestionsRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'suggestions';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)
                ->schema([
                    Section::make(__('resources/suggestion/strings.form.section_targets'))
                        ->icon('heroicon-o-building-office-2')
                        ->schema([
                            SuggestionFormPresenter::departments(),
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

    public static function getModelLabel(): string
    {
        return __('resources/suggestion/strings.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/suggestion/strings.plural_label');
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/suggestion/strings.plural_label');
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([

            Section::make(__('resources/suggestion/strings.infolist.section_workflow'))
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->schema([
                    SuggestionInfolistPresenter::workflow(),
                ])
                ->columnSpanFull()
                ->collapsible(),


            Section::make(__('resources/suggestion/strings.infolist.section_overview'))
                ->icon('heroicon-o-light-bulb')
                ->schema([
                    SuggestionInfolistPresenter::title(),
                    SuggestionInfolistPresenter::serial(),
                    SuggestionInfolistPresenter::stage(),
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
                ->columnSpanFull()
                ->columns(3),

            Section::make(__('resources/suggestion/strings.infolist.section_content'))
                ->icon('heroicon-o-document-text')
                ->schema([
                    SuggestionInfolistPresenter::description(),
                    SuggestionInfolistPresenter::attachment(),
                ])
                ->columnSpanFull()
                ->columns(2),


            Section::make(__('resources/suggestion/strings.infolist.section_reviews'))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->schema([
                    SuggestionInfolistPresenter::agreeCount(),
                    SuggestionInfolistPresenter::neutralCount(),
                    SuggestionInfolistPresenter::disagreeCount(),
                    SuggestionInfolistPresenter::reviews(),
                ])
                ->columnSpanFull()
                ->columns(3),

            Section::make(__('resources/suggestion/strings.infolist.section_decision'))
                ->icon('heroicon-o-scale')
                ->schema([
                    SuggestionInfolistPresenter::comments(),
                    SuggestionInfolistPresenter::referralDepts(),
                    SuggestionInfolistPresenter::referralActions(),
                ])
                ->columnSpanFull()
                ->columns(1)
                ->collapsed(),

        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                SuggestionTablePresenter::serial(),
                SuggestionTablePresenter::title(),
                SuggestionTablePresenter::stage(),
                SuggestionTablePresenter::agreeCount(),
                SuggestionTablePresenter::neutralCount(),
                SuggestionTablePresenter::disagreeCount(),
                SuggestionTablePresenter::attachment(),
                SuggestionTablePresenter::createdAt(),
            ])
            ->groups([
                SuggestionTablePresenter::stageGroup(),
            ])
            ->filters([
                SuggestionTablePresenter::departmentFilter(),
                SuggestionTablePresenter::selfFillFilter(),
                SuggestionTablePresenter::hasFileFilter(),
                SuggestionTablePresenter::hasReferralFilter(),
            ])
            ->filtersFormColumns(2)
            ->modifyQueryUsing(fn(Builder $query) => $query->withReviewCounts())
            ->headerActions([
                CreateAction::make()
                    ->visible(fn(): bool => !in_array($this->getOwnerRecord()->profile?->department_id, ['MA', 'MG'], true))
                    ->using(function (array $data): Suggestion {
                        abort_if(in_array($this->getOwnerRecord()->profile?->department_id, ['MA', 'MG'], true), 403);

                        return CreateSuggestion::createSuggestionRecord([
                            ...$data,
                            'user_id' => $this->getOwnerRecord()->id,
                        ]);
                    }),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-light-bulb')
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
