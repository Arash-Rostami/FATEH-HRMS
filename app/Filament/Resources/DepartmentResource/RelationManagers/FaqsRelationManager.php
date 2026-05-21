<?php
namespace App\Filament\Resources\DepartmentResource\RelationManagers;
use Filament\Resources\RelationManagers\RelationManager;
use App\Filament\Resources\FAQResource\Schemas\FAQFormPresenter;
use App\Filament\Resources\FAQResource\Schemas\FAQInfolistPresenter;
use App\Filament\Resources\FAQResource\Schemas\FAQTablePresenter;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Tables\Enums\RecordActionsPosition;
use App\Traits\FilamentActions;
class FaqsRelationManager extends RelationManager
{
    use FilamentActions;
    protected static string $relationship = 'faqs';
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/FAQ/strings.form.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    FAQFormPresenter::answer(),
                    FAQFormPresenter::category(),
                    FAQFormPresenter::departmentId(),
                    FAQFormPresenter::question(),
                    FAQFormPresenter::userId(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public static function getModelLabel(): string
    {
        return __('resources/FAQ/strings.label');
    }
    public static function getPluralModelLabel(): string
    {
        return __('resources/FAQ/strings.plural_label');
    }
    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('resources/FAQ/strings.plural_label');
    }
    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/FAQ/strings.infolist.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    FAQInfolistPresenter::answer(),
                    FAQInfolistPresenter::category(),
                    FAQInfolistPresenter::createdAt(),
                    FAQInfolistPresenter::department(),
                    FAQInfolistPresenter::id(),
                    FAQInfolistPresenter::question(),
                    FAQInfolistPresenter::updatedAt(),
                    FAQInfolistPresenter::user(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                FAQTablePresenter::answer(),
                FAQTablePresenter::category(),
                FAQTablePresenter::createdAt(),
                FAQTablePresenter::department(),
                FAQTablePresenter::id(),
                FAQTablePresenter::question(),
                FAQTablePresenter::user(),
            ])
            ->searchable(false)
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-sparkles')->label(__('resources/FAQ/strings.navigation.singular')),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark');
    }
}
