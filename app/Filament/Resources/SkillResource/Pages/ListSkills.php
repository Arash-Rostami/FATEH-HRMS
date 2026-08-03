<?php

namespace App\Filament\Resources\SkillResource\Pages;

use App\Filament\Resources\SkillResource;
use App\Models\Skill;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSkills extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = SkillResource::class;

    protected function getTableQuery(): Builder
    {
        return Skill::query()->withCount('skillUsers');
    }

    public function getTabs(): array
    {
        return [
            'catalog' => Tab::make(__('resources/skill/strings.table.tab_catalog'))
                ->icon('heroicon-o-bolt')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_ghost', false)->where('is_active', true)),

            'inactive' => Tab::make(__('resources/skill/strings.filters.inactive'))
                ->icon('heroicon-o-eye-slash')
                ->badge(fn() => $this->getStats()->inactive_count ?: null)
                ->badgeColor('gray')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_ghost', false)->where('is_active', false)),

            'ghosts' => Tab::make(__('resources/skill/strings.widget.title'))
                ->icon('heroicon-o-sparkles')
                ->badge(fn() => $this->getStats()->ghost_count ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_ghost', true)),
        ];
    }

    private function getStats(): object
    {
        return once(fn() => Skill::query()
            ->selectRaw("SUM(CASE WHEN is_ghost = 1 THEN 1 ELSE 0 END) AS ghost_count, SUM(CASE WHEN is_ghost = 0 AND is_active = 0 THEN 1 ELSE 0 END) AS inactive_count")
            ->first());
    }
}
