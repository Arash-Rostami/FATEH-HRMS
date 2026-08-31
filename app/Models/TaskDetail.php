<?php

namespace App\Models;

use App\Enums\TaskActivityType;
use App\Models\Concerns\HasTaskActivityLog;
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
            if (!empty($detail->project)) return;

            $task = $detail->task ?? Task::find($detail->task_id);

            if ($task?->project_id) {
                $detail->project = Project::find($task->project_id)?->name;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'collaborators' => 'array',
            'attachments' => 'array',
            'checklist' => 'array',
            'meta' => 'array',
        ];
    }
}
