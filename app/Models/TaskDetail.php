<?php

namespace App\Models;

use App\Casts\IntegerList;
use App\Enums\TaskActivityType;
use App\Models\Concerns\HasTaskActivityLog;
use App\Services\ProjectTask\CycleLinkResolver;
use App\Support\TaskAccessPolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use RuntimeException;

class TaskDetail extends Model
{
    use HasFactory, HasTaskActivityLog;

    public array $activityMap = [
        'collaborators' => TaskActivityType::Assignment,
        'responsible_user_id' => TaskActivityType::ResponsibleChange,
        'department_id' => TaskActivityType::DepartmentChange,
        'state' => TaskActivityType::StateChange,
        'meta' => TaskActivityType::MetaChange,
    ];

    public array $calendarTriggers = ['collaborators', 'responsible_user_id'];

    protected $fillable = [
        'task_id',
        'department_id',
        'unit',
        'section',
        'project',
        'scheme',
        'action_source_domain',
        'action_source',
        'collaborators',
        'responsible_user_id',
        'state',
        'attachments',
        'checklist',
        'meta',
    ];

    public function collaboratorNames(): Collection
    {
        return User::whereIn('id', $this->collaborators ?? [])->pluck('name');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'code');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $detail) {
            if (static::where('task_id', $detail->task_id)->exists()) {
                throw new RuntimeException(__('resources/task/strings.validation.duplicate_detail'));
            }
        });

        static::saving(function (self $detail) {
            $moved = Task::pullProjectMove($detail->task_id);

            if (!$moved && !$detail->isDirty(['collaborators', 'responsible_user_id'])) return;

            $people = array_filter([...($detail->collaborators ?? []), $detail->responsible_user_id]);

            if (!$moved && $detail->exists) {
                $people = array_diff($people, $detail->getOriginal('collaborators') ?? [], [$detail->getOriginal('responsible_user_id')]);
            }

            if ($people === []) return;

            $projectId = Task::withTrashed()->whereKey($detail->task_id)->value('project_id');

            TaskAccessPolicy::assertAssignable($people, $projectId ? Project::find($projectId) : null);
        });

        static::saving(function (self $detail) {
            $needsOrg = (!$detail->exists || ($detail->relationLoaded('task') && $detail->task->wasChanged('assigned_to')))
                && (blank($detail->department_id) || blank($detail->unit) || blank($detail->section));

            if (!$needsOrg && !empty($detail->project)) return;

            $task = $detail->task ?? Task::find($detail->task_id);

            if ($needsOrg && $task?->assigned_to) {
                $detail->fillOrgFromProfile($task);
            }

            if (empty($detail->project) && $task?->project_id) {
                $detail->project = Project::find($task->project_id)?->name;
            }
        });

        static::saved(function (self $detail) {
            if ($detail->wasRecentlyCreated || !$detail->wasChanged('collaborators')) return;

            $task = Task::find($detail->task_id, ['id', 'project_id', 'assigned_to']);

            if ($task?->project_id) {
                app(CycleLinkResolver::class)->syncAssignees($task, $detail->collaborators ?? []);
            }
        });
    }

    protected function fillOrgFromProfile(Task $task): void
    {
        $assignee = $task->relationLoaded('assignee') ? $task->assignee : null;
        $profile = $assignee?->relationLoaded('profile')
            ? $assignee->profile
            : Profile::with('details')->where('user_id', $task->assigned_to)->first();

        if (!$profile) return;

        $details = $profile->detailsMap();

        $this->department_id = filled($this->department_id) ? $this->department_id : $profile->department_id;
        $this->unit = filled($this->unit) ? $this->unit : $details->get('unit');
        $this->section = filled($this->section) ? $this->section : $details->get('section');
    }

    protected function casts(): array
    {
        return [
            'collaborators' => IntegerList::class,
            'attachments' => 'array',
            'checklist' => 'array',
            'meta' => 'array',
        ];
    }
}
