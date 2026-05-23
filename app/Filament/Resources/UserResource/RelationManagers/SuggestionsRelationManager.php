<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\SuggestionResource\Schemas\SuggestionFormPresenter;
use App\Filament\Resources\SuggestionResource\Schemas\SuggestionInfolistPresenter;
use App\Filament\Resources\SuggestionResource\Schemas\SuggestionTablePresenter;
use App\Traits\FilamentActions;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SuggestionsRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'suggestions';

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

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/suggestion/strings.label'))
                ->schema([
                    SuggestionFormPresenter::title(),
                    SuggestionFormPresenter::description(),
                    SuggestionFormPresenter::departments(),
                    SuggestionFormPresenter::purpose(),
                    SuggestionFormPresenter::rule(),
                    SuggestionFormPresenter::priority(),
                    SuggestionFormPresenter::attachment(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/suggestion/strings.label'))
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

                    SuggestionInfolistPresenter::description(),
                    SuggestionInfolistPresenter::attachment(),

                    SuggestionInfolistPresenter::deadline(),
                    SuggestionInfolistPresenter::createdAt(),
                    SuggestionInfolistPresenter::updatedAt(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
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
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-sparkles')
                    ->label('افزودن پیشنهاد'),
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
