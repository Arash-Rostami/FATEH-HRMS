<?php

namespace App\Models;

use App\Casts\IntegerList;
use App\Enums\TaskActivityType;
use App\Models\Concerns\HasDepartment;
use App\Models\Concerns\HasTaskActivityLog;
use App\Models\Concerns\Hooks\HasTaskDetailHooks;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class TaskDetail extends Model
{
    use HasFactory,
        HasDepartment,
        HasTaskActivityLog,
        HasTaskDetailHooks;

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

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
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
