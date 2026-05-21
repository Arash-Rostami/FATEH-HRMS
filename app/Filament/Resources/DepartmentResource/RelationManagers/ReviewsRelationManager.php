<?php
namespace App\Filament\Resources\DepartmentResource\RelationManagers;
use Filament\Resources\RelationManagers\RelationManager;
use App\Filament\Resources\SuggestionResource\Schemas\ReviewFormPresenter;
use App\Filament\Resources\SuggestionResource\Schemas\ReviewInfolistPresenter;
use App\Filament\Resources\SuggestionResource\Schemas\ReviewTablePresenter;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Tables\Enums\RecordActionsPosition;
use App\Traits\FilamentActions;
class ReviewsRelationManager extends RelationManager
{
    use FilamentActions;
    protected static string $relationship = 'reviews';
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Review/strings.form.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([

                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public static function getModelLabel(): string
    {
        return __('resources/Review/strings.label');
    }
    public static function getPluralModelLabel(): string
    {
        return __('resources/Review/strings.plural_label');
    }
    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('resources/Review/strings.plural_label');
    }
    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Review/strings.infolist.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([

                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->columns([

            ])
            ->searchable(false)
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-sparkles')->label(__('resources/Review/strings.navigation.singular')),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark');
    }
}
