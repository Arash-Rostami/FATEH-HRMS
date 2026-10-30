<?php

namespace App\Filament\Resources\NotifyRuleResource\RelationManagers;

use App\Filament\RelationManagers\RelationManager;
use App\Filament\Resources\NotifyRuleResource;
use App\Filament\Resources\NotifyRuleResource\Schemas\NotifyLogTablePresenter;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return NotifyRuleResource::canViewAny();
    }

    public static function getModelLabel(): string
    {
        return __('resources/notify_rule/strings.logs.title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/notify_rule/strings.logs.title');
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/notify_rule/strings.logs.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                NotifyLogTablePresenter::createdAt(),
                NotifyLogTablePresenter::status(),
                NotifyLogTablePresenter::count(),
                NotifyLogTablePresenter::recipient(),
                NotifyLogTablePresenter::user()->toggleable(isToggledHiddenByDefault: false),
                NotifyLogTablePresenter::event()->toggleable(isToggledHiddenByDefault: false),
                NotifyLogTablePresenter::recordId(),
                NotifyLogTablePresenter::channel(),
                NotifyLogTablePresenter::error(),
                NotifyLogTablePresenter::run(),
            ])
            ->filters([
                NotifyLogTablePresenter::statusFilter(),
                NotifyLogTablePresenter::problemsFilter(),
                NotifyLogTablePresenter::channelFilter(),
                NotifyLogTablePresenter::eventFilter(),
                NotifyLogTablePresenter::userFilter(),
                NotifyLogTablePresenter::dateRangeFilter(),
            ])
            ->striped()
            ->emptyStateIcon('heroicon-o-paper-airplane')
            ->emptyStateHeading(__('resources/notify_rule/strings.log.empty_heading'))
            ->emptyStateDescription(NotifyLogTablePresenter::emptyDescription())
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->paginationMode(PaginationMode::Simple);
    }
}
