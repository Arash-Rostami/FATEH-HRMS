<?php

namespace App\Services\Search\Resources;

use App\Models\Skill;
use App\Services\Search\Contracts\SearchResource;
use Illuminate\Database\Eloquent\Builder;

class SkillSearchResource extends SearchResource
{
    protected string $type = 'skill';
    protected string $group = 'مهارت‌ها';
    protected string $icon = 'bolt';
    protected string $model = Skill::class;
    protected array $columns = ['name', 'name_en'];
    protected ?string $titleField = 'name';
    protected ?string $subtitleField = 'category';
    protected string $orderBy = 'search_count';

    protected function scope(Builder $query): void
    {
        $query->where('is_active', true)->where('is_ghost', false);
    }

    public function action($row): string
    {
        return 'url:' . route('dashboard', ['tab' => 'status', 'skill' => $row->getKey()], false);
    }
}