<?php

namespace App\Filament\Resources\SkillResource\RelationManagers;

use App\Filament\Resources\SkillRequestResource\Schemas\SkillRequestTablePresenter;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'skillUsers';

    public static function getModelLabel(): string
    {
        return __('resources/skill_request/strings.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/skill_request/strings.plural_label');
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/skill_request/strings.plural_label');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                SkillRequestTablePresenter::user(),
                SkillRequestTablePresenter::status(),
                SkillRequestTablePresenter::tier(),
                SkillRequestTablePresenter::endorsementsCount(),
                SkillRequestTablePresenter::lastUsedAt(),
                SkillRequestTablePresenter::createdAt(),
            ])
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderByRaw("status = 'pending' desc")
                ->orderBy('created_at', 'asc'));
    }
}
