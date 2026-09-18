<?php

namespace App\Models;

use App\Enums\TaskActivityType;
use App\Filament\Resources\TaskResource\Enums\TaskPriority;
use App\Livewire\Dashboard\TaskBoard\Actions\ForceDeleteTaskAction;
use App\Models\Concerns\HasJalaliAdminLabels;
use App\Models\Concerns\HasMenuState;
use App\Models\Concerns\HasPrunableStatus;
use App\Models\Concerns\HasReminders;
use App\Models\Concerns\HasReplies;
use App\Models\Concerns\HasTaskActivityLog;
use App\Services\ProjectTask\EventSyncService;
use App\Services\ProjectTask\RankGenerator;
use App\Support\TaskAccessPolicy;
use App\Traits\CleansAttachedFiles;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class Task extends Model
{
    use HasFactory,
        HasJalaliAdminLabels,
        HasMenuState,
        HasReplies,
        HasReminders,
        SoftDeletes,
        Prunable,
        HasPrunableStatus,
        CleansAttachedFiles,
        HasTaskActivityLog;

    public const MENU_STATE_EVENTS = ['created', 'updated', 'deleted', 'restored', 'forceDeleted'];
    protected static array $statusCountsCache = [];

    public array $activityMap = [
        'status' => TaskActivityType::StatusChange,
        'assigned_to' => TaskActivityType::Assignment,
        'archived_at' => TaskActivityType::Archive,
        'deadline' => TaskActivityType::DeadlineChange,
        'priority' => TaskActivityType::PriorityChange,
        'labels' => TaskActivityType::LabelChange,
        'project_id' => TaskActivityType::ProjectChange,
        'approved_at' => TaskActivityType::Approval,
    ];

    public array $calendarTriggers = ['deadline', 'assigned_to', 'archived_at'];

    protected $fillable = [
        'title',
        'description',
        'status',
        'deadline',
        'user_id',
        'assigned_to',
        'ticket_id',
        'archived_at',
        'project_id',
        'labels',
        'priority',
        'rank',
        'approved_at',
        'approved_by',
        'completed_at',
    ];

    protected $appends = [
        'deadline_formatted',
        'created_formatted',
        'delegator_name',
        'assignee_name',
        'can_change_status',
        'is_delegator',
        'can_delete',
        'urgency_state',
        'is_archived',
        'progress_percent',
    ];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detail(): HasOne
    {
        return $this->hasOne(TaskDetail::class);
    }

    public static function getInProgressCount(int $userId): int
    {
        return self::getStatusCounts($userId)['in-progress'];
    }

    public static function getPendingCount(int $userId): int
    {
        return self::getStatusCounts($userId)['pending'];
    }

    public static function getStatusCounts(int $userId): array
    {
        if (isset(static::$statusCountsCache[$userId])) {
            return static::$statusCountsCache[$userId];
        }

        $counts = self::query()->forUser($userId)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return static::$statusCountsCache[$userId] = [
            'in-progress' => (int)($counts['in-progress'] ?? 0),
            'todo' => (int)($counts['todo'] ?? 0),
            'pending' => (int)($counts['pending'] ?? 0),
        ];
    }

    public static function getTodoCount(int $userId): int
    {
        return self::getStatusCounts($userId)['todo'];
    }

    public static function nextRank(?int $projectId, int $ownerId, string $status): string
    {
        $firstRank = static::query()
            ->inRankColumn($projectId, $ownerId, $status)
            ->whereNotNull('rank')
            ->orderBy('rank')
            ->lockForUpdate()
            ->value('rank');

        return RankGenerator::between(null, $firstRank);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPendingApproval(): bool
    {
        return $this->status === 'done'
            && $this->approved_at === null
            && $this->project?->setting('requires_approval') === true;
    }

    public function prunable(): Builder
    {
        return static::with('detail')
            ->whereNull('project_id')
            ->where('deleted_at', '<=', now()->subDays($this->getPruneDays()));
    }

    public function prune(): void
    {
        app(ForceDeleteTaskAction::class)->execute($this);
    }

    public static function rankForPriority(?int $projectId, int $ownerId, string $status, ?string $priority, ?int $excludeId = null): string
    {
        $tier = TaskPriority::tryFrom($priority ?? '')?->tier() ?? 0;

        $siblings = static::query()
            ->inRankColumn($projectId, $ownerId, $status)
            ->whereNotNull('rank')
            ->when($excludeId, fn(Builder $q) => $q->whereKeyNot($excludeId))
            ->orderBy('rank')
            ->lockForUpdate()
            ->get(['id', 'rank', 'priority']);

        $before = null;
        $after = null;
        $insertIndex = $siblings->count();

        foreach ($siblings as $index => $sibling) {
            $siblingTier = $sibling->priority?->tier() ?? 0;

            if ($siblingTier < $tier) {
                $after = $sibling->rank;
                $insertIndex = $index;
                break;
            }

            $before = $sibling->rank;
        }

        try {
            return RankGenerator::between($before, $after);
        } catch (InvalidArgumentException|RuntimeException) {
            $result = RankGenerator::rebalanceInsert($siblings->pluck('id')->values()->all(), $insertIndex);

            static::bulkAssignRanks($result['assignments']);

            return $result['insertRank'];
        }
    }

    public static function bulkAssignRanks(array $assignments): void
    {
        if ($assignments === []) {
            return;
        }

        $ids = array_keys($assignments);
        $bindings = [];

        foreach ($assignments as $id => $rank) {
            $bindings[] = $id;
            $bindings[] = $rank;
        }

        $table = (new static)->getTable();
        $cases = str_repeat('WHEN ? THEN ? ', count($assignments));
        $in = implode(',', array_fill(0, count($ids), '?'));

        DB::statement(
            "UPDATE `{$table}` SET `rank` = CASE `id` {$cases}END WHERE `id` IN ({$in})",
            [...$bindings, ...$ids]
        );
    }

    public static function resetStatusCountsCache(): void
    {
        static::$statusCountsCache = [];
    }

    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where(fn(Builder $q) => $q->where('assigned_to', $userId)
            ->orWhere(fn(Builder $sq) => $sq->where('user_id', $userId)->whereNull('assigned_to'))
        );
    }

    public function scopeInRankColumn(Builder $query, ?int $projectId, int $ownerId, string $status): void
    {
        $query->where('status', $status);

        if ($projectId) {
            $query->where('project_id', $projectId);
        } else {
            $query->whereNull('project_id')->forUser($ownerId);
        }
    }

    public function scopeStatus(Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    protected function assigneeName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->assignee?->name
        )->shouldCache();
    }

    protected static function booted(): void
    {
        static::forceDeleting(function (self $task) {
            if ($task->detail) {
                static::deleteStoredFiles($task->detail->attachments);
            }

            $task->replies->each->delete();

            app(EventSyncService::class)->purge($task);
        });

        static::deleting(function (self $task) {
            if (!$task->isForceDeleting()) {
                app(EventSyncService::class)->purge($task);
            }
        });

        static::restored(function (self $task) {
            app(EventSyncService::class)->sync($task);
        });

        static::updating(function (self $task) {
            if ($task->isDirty('status')) {
                $task->clearArchiveOnStatusChange();
                $task->handleApprovalOnCompletion();
            }
        });

        static::saved(function (self $task) {
            if (!$task->wasRecentlyCreated && $task->wasChanged('project_id') && $task->project_id === null) {
                $task->detail?->update(['project' => null]);
            }
        });
    }

    protected function clearArchiveOnStatusChange(): void
    {
        if ($this->status !== 'done' && $this->archived_at !== null) {
            $this->archived_at = null;
            $this->completed_at = null;
        }
    }

    protected function handleApprovalOnCompletion(): void
    {
        if ($this->status !== 'done' || $this->getOriginal('status') === 'done') {
            return;
        }

        $this->completed_at = now();
        $this->loadMissing('project:id,settings,owner_id');
        $actor = auth()->id();

        if ($this->project?->setting('requires_approval') !== true) {
            if ($this->approved_at === null) {
                $this->approved_at = now();
                $this->approved_by = $actor;
            }

            return;
        }

        if ($this->project->owner_id === $actor) {
            $this->approved_at = now();
            $this->approved_by = $actor;
        } else {
            $this->approved_at = null;
            $this->approved_by = null;
        }
    }

    protected function canChangeStatus(): Attribute
    {
        return Attribute::make(
            get: fn() => TaskAccessPolicy::canChangeStatus($this, auth()->user())
        );
    }

    protected function canDelete(): Attribute
    {
        return Attribute::make(
            get: fn() => TaskAccessPolicy::canDelete($this, auth()->user())
        );
    }

    protected function casts(): array
    {
        return [
            'deadline' => 'datetime',
            'archived_at' => 'datetime',
            'labels' => 'array',
            'priority' => TaskPriority::class,
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected function createdFormatted(): Attribute
    {
        return Attribute::make(
            get: fn() => toJalali($this->created_at, 'j F Y')
        )->shouldCache();
    }

    protected function deadlineFormatted(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->deadline) return null;

                $now = now();

                return toJalali(
                    $this->deadline,
                    $this->deadline->between($now, $now->copy()->addDays(7)) ? 'l j F' : 'j F Y'
                );
            }
        )->shouldCache();
    }

    protected function delegatorName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->creator?->name
        )->shouldCache();
    }

    protected function isArchived(): Attribute
    {
        return Attribute::make(
            get: fn() => (bool)$this->archived_at
        )->shouldCache();
    }

    protected function isDelegator(): Attribute
    {
        return Attribute::make(
            get: fn() => TaskAccessPolicy::canUndoAssignment($this, auth()->user())
        );
    }

    protected function progressPercent(): Attribute
    {
        return Attribute::make(get: fn() => $this->calculateProgressPercent());
    }

    protected function calculateProgressPercent(): int
    {
        if ($this->status === 'done') {
            return 100;
        }

        $checklist = $this->detail?->checklist ?? [];

        if ($checklist === []) {
            return match ($this->status) {
                'in-progress' => 50,
                'pending' => 75,
                default => 0,
            };
        }

        $totalWeight = 0;
        $doneWeight = 0;
        $doneCount = 0;

        foreach ($checklist as $item) {
            $weight = (int) ($item['weight'] ?? 0);
            $totalWeight += $weight;

            if ($item['done'] ?? false) {
                $doneWeight += $weight;
                $doneCount++;
            }
        }

        return $totalWeight > 0
            ? $doneWeight
            : (int) round($doneCount / count($checklist) * 100);
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            set: fn(mixed $value) => $value instanceof BackedEnum ? $value->value : $value,
        );
    }

    protected function urgencyState(): Attribute
    {
        return Attribute::make(get: fn() => $this->calculateUrgencyState());
    }

    protected function calculateUrgencyState(): array
    {
        if (in_array($this->status, ['done', 'pending'], true)) {
            return ['score' => 0.0, 'kind' => null, 'label' => null];
        }

        $today = now()->startOfDay();
        $urgency = ['score' => 0.0, 'kind' => null, 'label' => null];

        $urgency = $this->applyDeadlineUrgency($urgency, $today);
        $urgency = $this->applyProjectUrgency($urgency);
        $urgency = $this->applyPriorityFloor($urgency);
        $urgency = $this->applyIdleUrgency($urgency, $today);

        $urgency['score'] = round($urgency['score'], 2);

        return $urgency;
    }

    protected function applyDeadlineUrgency(array $urgency, $today): array
    {
        if (!$this->deadline) {
            return $urgency;
        }

        $due = clone $this->deadline;
        $due = $due->startOfDay();
        $daysDiff = (int)$due->diffInDays($today, true);

        if ($due->isBefore($today)) {
            return ['score' => 1.0, 'kind' => 'overdue', 'label' => $daysDiff . ' روز تأخیر'];
        }

        if ($daysDiff <= 3) {
            $label = match ($daysDiff) {
                0 => 'امروز',
                1 => 'فردا',
                default => $daysDiff . ' روز دیگر',
            };

            return ['score' => 0.7, 'kind' => 'due', 'label' => $label];
        }

        return $urgency;
    }

    protected function applyProjectUrgency(array $urgency): array
    {
        if (!$this->relationLoaded('project') || !$this->project) {
            return $urgency;
        }

        $sla = $this->project->setting('sla');
        if ($sla !== null && $this->created_at->diffInHours(now()) > (int)$sla) {
            $urgency['score'] = max($urgency['score'], 0.9);
            $urgency['kind'] ??= 'sla';
            $urgency['label'] ??= 'نقض SLA';
        }

        if ($this->project->deadlineCapExceeded($this->deadline)) {
            $urgency['score'] = max($urgency['score'], 0.5);
            $urgency['kind'] ??= 'project-deadline-exceeded';
            $urgency['label'] ??= 'فراتر از مهلت پروژه';
        }

        return $urgency;
    }

    protected function applyPriorityFloor(array $urgency): array
    {
        $floor = match ($this->priority) {
            TaskPriority::Urgent => 0.85,
            TaskPriority::High => 0.55,
            default => 0.0,
        };

        $urgency['score'] = max($urgency['score'], $floor);

        return $urgency;
    }

    protected function applyIdleUrgency(array $urgency, $today): array
    {
        if (!$this->updated_at) {
            return $urgency;
        }

        $updatedAt = clone $this->updated_at;
        $idleDays = (int)$updatedAt->startOfDay()->diffInDays($today, true);

        if ($idleDays >= 7) {
            $urgency['score'] = max($urgency['score'], 0.45);
        } elseif ($idleDays >= 3) {
            $urgency['score'] = max($urgency['score'], 0.22);
        } else {
            return $urgency;
        }

        $urgency['kind'] ??= 'idle';
        $urgency['label'] ??= 'چند روز بی‌تغیر';

        return $urgency;
    }
}
