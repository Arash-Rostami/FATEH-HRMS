<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\ReportResource\Schemas\ReportFormPresenter;
use App\Filament\Resources\ReportResource\Schemas\ReportInfolistPresenter;
use App\Filament\Resources\ReportResource\Schemas\ReportTablePresenter;
use App\Traits\FilamentActions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ReportsRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'reports';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()
                ->tabs([
                    Tab::make(__('resources/report/strings.form.tab_main'))
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Section::make(__('resources/report/strings.form.section_main'))
                                ->description(__('resources/report/strings.form.section_description'))
                                ->icon('heroicon-o-document-text')
                                ->schema([
                                    ReportFormPresenter::title(),
                                    ReportFormPresenter::departmentId(),
                                    ReportFormPresenter::active(),
                                    ReportFormPresenter::description(),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),
                        ]),

                    Tab::make(__('resources/report/strings.form.tab_files'))
                        ->icon('heroicon-o-paper-clip')
                        ->schema([
                            Section::make(__('resources/report/strings.form.section_files'))
                                ->icon('heroicon-o-folder-open')
                                ->schema([
                                    ReportFormPresenter::coverImage(),
                                    ReportFormPresenter::filePath(),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull()
        ]);
    }

    public static function getModelLabel(): string
    {
        return __('resources/report/strings.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/report/strings.plural_label');
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/report/strings.plural_label');
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->hiddenLabel()
                ->schema([
                    ReportInfolistPresenter::thumbnail(),
                    ReportInfolistPresenter::title(),
                    ReportInfolistPresenter::description(),
                    ReportInfolistPresenter::department(),
                    ReportInfolistPresenter::fileType(),
                    ReportInfolistPresenter::active(),
                    ReportInfolistPresenter::createdAt(),
                    ReportInfolistPresenter::updatedAt(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['department']))
            ->columns([
                ReportTablePresenter::id(),
                ReportTablePresenter::title(),
                ReportTablePresenter::department(),
                ReportTablePresenter::fileType(),
                ReportTablePresenter::active(),
                ReportTablePresenter::createdAt(),
            ])
            ->groups([
                ReportTablePresenter::activeGroup(),
                ReportTablePresenter::departmentGroup(),
            ])
            ->filters([
                ReportTablePresenter::activeFilter(),
                ReportTablePresenter::departmentFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
