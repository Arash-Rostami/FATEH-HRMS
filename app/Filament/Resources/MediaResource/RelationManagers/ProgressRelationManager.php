<?php

namespace App\Filament\Resources\MediaResource\RelationManagers;

use App\Filament\RelationManagers\RelationManager;
use App\Filament\Resources\KnowledgeProgressResource\Schemas\KnowledgeProgressTablePresenter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProgressRelationManager extends RelationManager
{
    protected static string $relationship = 'progress';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return parent::canViewForRecord($ownerRecord, $pageClass) && $ownerRecord->progress()->exists();
    }

    public static function deniedByPermission(Model $ownerRecord): ?string
    {
        return ($module = parent::deniedByPermission($ownerRecord)) && $ownerRecord->progress()->exists() ? $module : null;
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/media/strings.progress.relation');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['user:id,name']))
            ->columns([
                KnowledgeProgressTablePresenter::id(),
                KnowledgeProgressTablePresenter::user(),
                KnowledgeProgressTablePresenter::position(),
                KnowledgeProgressTablePresenter::completed(),
                KnowledgeProgressTablePresenter::lastSeenAt(),
            ])
            ->defaultSort('last_seen_at', 'desc')
            ->emptyStateIcon('heroicon-o-bookmark')
            ->striped();
    }
}
