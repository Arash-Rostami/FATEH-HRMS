<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KnowledgeProgressResource\Pages\ListKnowledgeProgress;
use App\Filament\Resources\KnowledgeProgressResource\Schemas\KnowledgeProgressTablePresenter;
use App\Filament\Resources\MediaResource\Exports\ProgressExporter;
use App\Models\KnowledgeProgress;
use App\Traits\Filament\AuthorizesByPermission;
use App\Traits\Filament\FilamentActions;
use App\Traits\Filament\FilamentFilters;
use App\Traits\Filament\FilamentResourceSwitch;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class KnowledgeProgressResource extends Resource
{
    use FilamentActions, FilamentFilters, AuthorizesByPermission, FilamentResourceSwitch;

    public static function shouldRegisterNavigation(): bool
    {
        return ! ReportResource::canViewAny() && ! tenantOffName(static::getSlug());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    protected static ?string $model = KnowledgeProgress::class;
    protected static ?string $slug = 'knowledge-progress';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'media.parent', 'report']);
    }

    public static function getModelLabel(): string
    {
        return __('resources/media/strings.progress.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/report/strings.nav_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKnowledgeProgress::route('/'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/media/strings.progress.plural_label');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                KnowledgeProgressTablePresenter::id(),
                KnowledgeProgressTablePresenter::user(),
                KnowledgeProgressTablePresenter::item(),
                KnowledgeProgressTablePresenter::series(),
                KnowledgeProgressTablePresenter::position(),
                KnowledgeProgressTablePresenter::completed(),
                KnowledgeProgressTablePresenter::lastSeenAt(),
                KnowledgeProgressTablePresenter::createdAt(),
            ])
            ->groups([
                KnowledgeProgressTablePresenter::userGroup(),
                KnowledgeProgressTablePresenter::completedGroup(),
            ])
            ->filters([
                KnowledgeProgressTablePresenter::completedFilter(),
                KnowledgeProgressTablePresenter::userFilter(),
                self::createdAtFilter(),
            ])
            ->filtersFormColumns(2)
            ->groupedBulkActions([static::bulkExportAction(ProgressExporter::class)])
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('last_seen_at', 'desc')
            ->striped();
    }
}
