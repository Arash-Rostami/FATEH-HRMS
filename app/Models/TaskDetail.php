<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class TaskDetail extends Model
{
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

    protected function casts(): array
    {
        return [
            'collaborators' => 'array',
            'attachments' => 'array',
        ];
    }
}
