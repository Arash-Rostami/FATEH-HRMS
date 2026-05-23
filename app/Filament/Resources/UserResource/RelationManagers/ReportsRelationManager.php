<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\ReportResource\Schemas\ReportFormPresenter;
use App\Filament\Resources\ReportResource\Schemas\ReportInfolistPresenter;
use App\Filament\Resources\ReportResource\Schemas\ReportTablePresenter;
use App\Traits\FilamentActions;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ReportsRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'reports';

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

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/report/strings.form.section_main'))
                ->schema([
                    ReportFormPresenter::title(),
                    // Note: ReportFormPresenter::userId() excluded — auto-set by RelationManager
                    ReportFormPresenter::departmentId(),
                    ReportFormPresenter::active(),
                ])
                ->columns(2),

            Section::make(__('resources/report/strings.form.section_files'))
                ->schema([
                    ReportFormPresenter::filePath(),
                    ReportFormPresenter::coverImage(),
                ])
                ->columnSpanFull(),

            Section::make(__('resources/report/strings.form.section_main'))
                ->schema([
                    ReportFormPresenter::description(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/report/strings.form.section_main'))
                ->schema([
                    ReportInfolistPresenter::title(),
                    ReportInfolistPresenter::user(),
                    ReportInfolistPresenter::department(),
                    ReportInfolistPresenter::active(),
                    ReportInfolistPresenter::fileType(),
                    ReportInfolistPresenter::createdAt(),
                    ReportInfolistPresenter::updatedAt(),
                ])
                ->columnSpanFull()
                ->columns(2),

            Section::make(__('resources/report/strings.form.section_files'))
                ->schema([
                    ReportInfolistPresenter::thumbnail(),
                    ReportInfolistPresenter::description(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                ReportTablePresenter::id(),
                ReportTablePresenter::title(),
                ReportTablePresenter::department(),
                ReportTablePresenter::active(),
                ReportTablePresenter::fileType(),
                ReportTablePresenter::createdAt(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-sparkles')
                    ->label('افزودن گزارش'),
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
