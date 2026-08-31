<?php

namespace App\Services\Search\Resources;

use App\Models\Project;
use App\Services\Search\Contracts\SearchResource;
use Illuminate\Database\Eloquent\Builder;

class ProjectResource extends SearchResource
{
    protected string $type = 'project';
    protected string $group = 'پروژه‌ها';
    protected string $icon = 'workspaces';
    protected string $model = Project::class;
    protected array $columns = ['name'];
    protected ?string $titleField = 'name';
    protected ?string $subtitleField = null;

    public function action($row): string
    {
        return $this->route('projects', $row->getKey());
    }

    protected function scope(Builder $query): void
    {
        $query->visibleTo(auth()->user());
    }
}
