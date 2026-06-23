<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Exports\ReportExporter;
use App\Filament\Resources\ReportResource\Pages\CreateReport;
use App\Filament\Resources\ReportResource\Pages\EditReport;
use App\Filament\Resources\ReportResource\Pages\ListReports;
use App\Filament\Resources\ReportResource\Schemas\ReportFormPresenter;
use App\Filament\Resources\ReportResource\Schemas\ReportInfolistPresenter;
use App\Filament\Resources\ReportResource\Schemas\ReportTablePresenter;
use App\Models\Report;
use App\Traits\AuthorizesByPermission;
use App\Traits\FilamentActions;
use App\Traits\FilamentFilters;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ReportResource extends Resource
{
    use FilamentActions, FilamentFilters, AuthorizesByPermission;

    protected static ?string $model = Report::class;
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
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
                                    ReportFormPresenter::userId(),
                                    ReportFormPresenter::active(),
                                    ReportFormPresenter::divider(),
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
                                    ReportFormPresenter::divider(),
                                    ReportFormPresenter::filePath(),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull()
                ->persistTabInQueryString(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['department', 'user']);
    }

    public static function getGlobalSearchResultActions(Model $record): array
    {
        return [
            Action::make('edit')
                ->icon('heroicon-m-pencil')
                ->url(static::getUrl('edit', ['record' => $record])),
        ];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('resources/report/strings.fields.department') => $record->department?->description ?? '-',
            __('resources/report/strings.fields.user') => $record->user?->name ?? '-',
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->title;
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'description'];
    }

    public static function getModelLabel(): string
    {
        return __('resources/report/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/report/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReports::route('/'),
            'create' => CreateReport::route('/create'),
            'edit' => EditReport::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/report/strings.plural_label');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->hiddenLabel()
                ->schema([
                    ReportInfolistPresenter::thumbnail(),
                    ReportInfolistPresenter::title(),
                    ReportInfolistPresenter::description(),
                    ReportInfolistPresenter::department(),
                    ReportInfolistPresenter::user(),
                    ReportInfolistPresenter::fileType(),
                    ReportInfolistPresenter::active(),
                    ReportInfolistPresenter::createdAt(),
                    ReportInfolistPresenter::updatedAt(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ReportTablePresenter::id(),
                ReportTablePresenter::title(),
                ReportTablePresenter::department(),
                ReportTablePresenter::user(),
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
                self::createdAtFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions(self::bulkActions(ReportExporter::class))
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
