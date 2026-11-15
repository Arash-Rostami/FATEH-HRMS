<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AiReportResource\Pages\{ListAiReports, ViewAiReport};
use App\Filament\Resources\AiReportResource\Schemas\{AiReportInfolistPresenter, AiReportTablePresenter};
use App\Models\AiReport;
use App\Traits\Filament\{AuthorizesByPermission, FilamentActions, FilamentAdminGuide, FilamentFilters, FilamentVisibility};
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AiReportResource extends Resource
{
    use AuthorizesByPermission, FilamentActions, FilamentAdminGuide, FilamentFilters, FilamentVisibility;

    protected static ?string $model = AiReport::class;
    protected static ?string $recordTitleAttribute = 'title';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static array $guide = [
        ['label' => 'بررسی', 'icon' => 'menu_book', 'view' => 'filament.resources.ai-report.guide.overview'],
        ['label' => 'تجربهٔ کاربر', 'icon' => 'visibility', 'view' => 'filament.resources.ai-report.guide.user'],
    ];

    public static function shouldRegisterNavigation(): bool
    {
        return ! ReportResource::canViewAny() && ! tenantOffName(static::getSlug()) && static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('user:id,name');
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->select(AiReport::LIST_COLUMNS);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'user.name'];
    }

    public static function getModelLabel(): string
    {
        return __('resources/ai_report/strings.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/ai_report/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiReports::route('/'),
            'view' => ViewAiReport::route('/{record}'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/ai_report/strings.plural_label');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->extraAttributes(['class' => 'fi-infolist-panel'])
                ->hiddenLabel()
                ->schema([
                    AiReportInfolistPresenter::owner(),
                    AiReportInfolistPresenter::recipients(),
                    AiReportInfolistPresenter::model(),
                    AiReportInfolistPresenter::tokensTotal(),
                    AiReportInfolistPresenter::status(),
                    AiReportInfolistPresenter::failReason(),
                    AiReportInfolistPresenter::savedAt(),
                    AiReportInfolistPresenter::createdAt(),
                    AiReportInfolistPresenter::versionsCount(),
                    AiReportInfolistPresenter::lastPrompt(),
                    AiReportInfolistPresenter::skippedFiles(),
                ])
                ->columnSpanFull()
                ->columns(4),
            Section::make()
                ->hiddenLabel()
                ->schema([
                    AiReportInfolistPresenter::preview(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->select([...AiReport::LIST_COLUMNS, 'active', 'model', 'tokens_total', 'created_at']))
            ->columns([
                AiReportTablePresenter::id(),
                AiReportTablePresenter::title(),
                AiReportTablePresenter::owner(),
                AiReportTablePresenter::recipients(),
                AiReportTablePresenter::status(),
                self::activeColumn(),
                AiReportTablePresenter::model(),
                AiReportTablePresenter::tokensTotal(),
                AiReportTablePresenter::savedAt(),
                AiReportTablePresenter::createdAt(),
            ])
            ->groups([
                AiReportTablePresenter::statusGroup(),
                AiReportTablePresenter::ownerGroup(),
            ])
            ->filters([
                AiReportTablePresenter::statusFilter(),
                AiReportTablePresenter::ownerFilter(),
                AiReportTablePresenter::draftFilter(),
                AiReportTablePresenter::staleDraftFilter(),
                self::createdAtFilter(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                self::viewAction(),
                self::deleteAction()->hidden(fn (AiReport $record) => $record->status === AiReport::STATUS_GENERATING && !$record->isStale())->before(function ($action, AiReport $record) {
                    if (AiReport::query()->whereKey($record->getKey())->where('status', AiReport::STATUS_GENERATING)->where('updated_at', '>=', now()->subMinutes(AiReport::staleMinutes()))->exists()) {
                        Notification::make()->warning()->title('گزارش در حال تولید قابل حذف نیست.')->send();
                        $action->cancel();
                    }
                }),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions([
                static::bulkDeleteAction()->before(function ($action, $records) {
                    if (AiReport::query()->whereKey($records->modelKeys())->where('status', AiReport::STATUS_GENERATING)->where('updated_at', '>=', now()->subMinutes(AiReport::staleMinutes()))->exists()) {
                        Notification::make()->warning()->title('گزارش در حال تولید قابل حذف نیست.')->send();
                        $action->cancel();
                    }
                }),
                ...self::bulkVisibilityActions(),
            ])
            ->emptyStateIcon('heroicon-o-document-chart-bar')
            ->defaultSort('updated_at', 'desc')
            ->striped();
    }
}
