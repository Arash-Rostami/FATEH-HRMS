<?php

namespace App\Services\Search\Resources;

use App\Models\Project;
use App\Models\Task;
use App\Services\ProjectTask\BoardCollaboratorResolver;
use App\Services\Search\Contracts\SearchContext;
use App\Services\Search\Contracts\SearchResource;
use Illuminate\Database\Eloquent\Builder;

class TaskResource extends SearchResource
{
    protected string $type = 'task';
    protected string $group = 'تسک بورد';
    protected string $icon = 'view_kanban';
    protected string $model = Task::class;
    protected array $columns = ['title', 'description'];
    protected ?string $titleField = 'title';
    protected ?string $subtitleField = 'description';

    protected array $collaboratorLookup = [];

    public function action($row): string
    {
        return $this->route('tasks', $row->getKey());
    }

    protected function scope(Builder $query): void
    {
        $me = $this->me();

        $query->where(fn (Builder $q) => $q
            ->where('user_id', $me)
            ->orWhere('assigned_to', $me)
            ->orWhereIn('project_id', Project::visibleTo(auth()->user())->select('id'))
        )->with('detail');
    }

    protected function rowsFetched($rows): void
    {
        $this->collaboratorLookup = app(BoardCollaboratorResolver::class)->resolve($rows);
    }

    protected function shapeRow($row): array
    {
        return parent::shapeRow($row) + [
            'urgency_state' => $row->urgency_state,
            'progress_percent' => $row->progress_percent,
            'collaborator_avatars' => collect($row->detail?->collaborators ?? [])
                ->map(fn ($id) => $this->collaboratorLookup[$id] ?? null)
                ->filter()
                ->values()
                ->all(),
        ];
    }
}
