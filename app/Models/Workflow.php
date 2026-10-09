<?php

namespace App\Models;

use App\Enums\TaskActivityType;
use App\Services\ProjectTask\ActivityLogger;
use App\Services\ProjectTask\HandleWorkflowTransition;
use App\Support\TaskAccessPolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Workflow extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public ?string $transitionReason = null;

    protected $fillable = [
        'name',
        'owner_id',
        'project_id',
        'template_id',
        'steps',
        'current_step',
        'status',
        'step_log',
        'started_at',
        'completed_at',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(self::class, 'template_id');
    }

    public function scopeTemplates(Builder $query): Builder
    {
        return $query->whereNull('project_id');
    }

    public function scopeInstances(Builder $query): Builder
    {
        return $query->whereNotNull('project_id');
    }

    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    public static function averageDays(iterable $templateIds): array
    {
        $ids = collect($templateIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return static::instances()
            ->where('status', self::STATUS_COMPLETED)
            ->whereIn('template_id', $ids)
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->selectRaw('template_id, AVG(TIMESTAMPDIFF(HOUR, started_at, completed_at)) / 24 as avg_days')
            ->groupBy('template_id')
            ->pluck('avg_days', 'template_id')
            ->map(fn($days) => max(1, (int) round($days)))
            ->all();
    }

    public function projectWithTrashed(): ?Project
    {
        return $this->project()->withTrashed()->first();
    }

    public static function resolveUserNames(array $ids): array
    {
        $ids = array_unique($ids);

        return $ids ? User::whereIn('id', $ids)->pluck('name', 'id')->all() : [];
    }

    public function currentStep(): ?array
    {
        return $this->current_step === null ? null : ($this->steps[$this->current_step] ?? null);
    }

    public function isAssignee(int $userId): bool
    {
        return in_array($userId, $this->currentStep()['assignee_user_ids'] ?? [], true);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function recordEvent(string $event, ?int $step, ?int $actorId, bool $log = true, array $extra = []): void
    {
        if ($log) {
            $entries = $this->step_log ?? [];
            $entries[] = ['step' => $step ?? 0, 'completed_by' => $actorId, 'completed_at' => now()->toISOString(), 'event' => $event];
            $this->step_log = $entries;
        }

        $this->saveQuietly();

        if ($project = $this->projectWithTrashed()) {
            app(ActivityLogger::class)->system($project, $actorId ? User::find($actorId) : null, TaskActivityType::WorkflowStep, [
                'event' => $event,
                'step' => $step,
                'label' => $step === null ? null : ($this->steps[$step]['label'] ?? null),
                'cycle' => $this->name,
                'workflow' => $this->id,
                ...$extra,
            ]);
        }
    }

    public static function sanitizeSteps(array $steps): array
    {
        $allIds = array_unique(array_merge([], ...array_map(fn ($s) => $s['assignee_user_ids'] ?? [], $steps)));
        $valid = $allIds ? User::whereIn('id', $allIds)->pluck('id')->all() : [];

        return array_values(array_map(function (array $step) use ($valid) {
            unset($step['task_id']);
            unset($step['activated_at']);
            $step['assignee_user_ids'] = array_values(array_intersect(array_unique($step['assignee_user_ids'] ?? []), $valid));

            return $step;
        }, $steps));
    }

    public static function filterAssignees(array $ids, ?Project $project, int $max = 20): array
    {
        return self::filterAssigneesBatch([$ids], $project, $max)[0];
    }

    public static function filterAssigneesBatch(array $stepsIds, ?Project $project, int $max = 20): array
    {
        $userIds = TaskAccessPolicy::assignableIds(array_merge([], ...$stepsIds), $project);

        return array_map(function (array $ids) use ($userIds, $max) {
            $ordered = array_intersect(array_unique($ids), $userIds);

            return array_values(array_slice($ordered, 0, $max));
        }, $stepsIds);
    }

    protected static function booted(): void
    {
        static::updated(function (self $wf) {
            if (!$wf->wasChanged(['current_step', 'status'])) return;
            app(HandleWorkflowTransition::class)->execute($wf);
        });
    }

    protected function casts(): array
    {
        return [
            'steps' => 'array',
            'step_log' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
