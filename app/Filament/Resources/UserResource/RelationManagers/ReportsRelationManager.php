<?php
namespace App\Filament\Resources\UserResource\RelationManagers;
use Filament\Resources\RelationManagers\RelationManager;
use App\Filament\Resources\ReportResource\Schemas\ReportFormPresenter;
use App\Filament\Resources\ReportResource\Schemas\ReportInfolistPresenter;
use App\Filament\Resources\ReportResource\Schemas\ReportTablePresenter;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Tables\Enums\RecordActionsPosition;
use App\Traits\FilamentActions;
class ReportsRelationManager extends RelationManager
{
    use FilamentActions;
    protected static string $relationship = 'reports';
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Report/strings.form.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    ReportFormPresenter::active(),
                    ReportFormPresenter::coverImage(),
                    ReportFormPresenter::departmentId(),
                    ReportFormPresenter::description(),
                    ReportFormPresenter::filePath(),
                    ReportFormPresenter::title(),
                    ReportFormPresenter::userId(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public static function getModelLabel(): string
    {
        return __('resources/Report/strings.label');
    }
    public static function getPluralModelLabel(): string
    {
        return __('resources/Report/strings.plural_label');
    }
    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('resources/Report/strings.plural_label');
    }
    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/Report/strings.infolist.section_main'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    ReportInfolistPresenter::active(),
                    ReportInfolistPresenter::createdAt(),
                    ReportInfolistPresenter::department(),
                    ReportInfolistPresenter::description(),
                    ReportInfolistPresenter::fileType(),
                    ReportInfolistPresenter::thumbnail(),
                    ReportInfolistPresenter::title(),
                    ReportInfolistPresenter::updatedAt(),
                    ReportInfolistPresenter::user(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ReportTablePresenter::active(),
                ReportTablePresenter::createdAt(),
                ReportTablePresenter::department(),
                ReportTablePresenter::fileType(),
                ReportTablePresenter::id(),
                ReportTablePresenter::title(),
                ReportTablePresenter::user(),
            ])
            ->searchable(false)
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-sparkles')->label(__('resources/Report/strings.navigation.singular')),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark');
    }
}
