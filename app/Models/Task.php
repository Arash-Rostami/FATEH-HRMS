<?php

namespace App\Models;

use App\Enums\TaskActivityType;
use App\Filament\Resources\TaskResource\Enums\TaskPriority;
use App\Filament\Resources\TaskResource\Enums\TaskState;
use App\Livewire\Dashboard\Project\Actions\AdvanceWorkflowStepAction;
use App\Livewire\Dashboard\TaskBoard\Actions\ForceDeleteTaskAction;
use App\Models\Concerns\HasJalaliAdminLabels;
use App\Models\Concerns\HasMenuState;
use App\Models\Concerns\HasPrunableStatus;
use App\Models\Concerns\HasReminders;
use App\Models\Concerns\HasReplies;
use App\Models\Concerns\HasTaskActivityLog;
use App\Services\ProjectTask\ActivityLogger;
use App\Services\ProjectTask\CycleLinkResolver;
use App\Services\ProjectTask\EventSyncService;
use App\Services\ProjectTask\RankGenerator;
use App\Support\ProjectAccessPolicy;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
    protected static bool $ticketSyncing = false;
    protected static array $movedProjects = [];

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

    public bool $systemApproval = false;

    public array $calendarTriggers = ['deadline', 'assigned_to', 'archived_at', 'status', 'title', 'user_id'];

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
        'is_frozen',
        'can_reopen',
        'is_delegator',
        'can_delete',
        'urgency_state',
        'is_archived',
        'progress_percent',
        'version',
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

        $counts = self::query()->ownedBy($userId)
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
            && $this->approvalProject()?->setting('requires_approval') === true;
    }

    public function isFrozenFor(?User $user): bool
    {
        return $this->status === 'done'
            && $this->approved_at !== null
            && $this->approvalProject()?->setting('requires_approval') === true
            && !($this->project && ProjectAccessPolicy::canManageTasks($this->project, $user));
    }

    protected function approvalProject(): ?Project
    {
        if (!$this->project_id) return null;

        $loaded = $this->relationLoaded('project') ? $this->project : null;

        if ($loaded && (int)$loaded->getKey() === (int)$this->project_id
            && array_diff(['settings', 'owner_id'], array_keys($loaded->getAttributes())) === []) {
            return $loaded;
        }

        if ($this->relationLoaded('project') && $this->project === null && !$this->isDirty('project_id')) return null;

        $project = Project::find($this->project_id, ['id', 'settings', 'owner_id', 'member_ids', 'departments']);
        $this->setRelation('project', $project);

        return $project;
    }

    public function isGenuinelyDone(): bool
    {
        return $this->status === 'done' && $this->approved_at !== null;
    }

    public function prunable(): Builder
    {
        return static::with('detail')
            ->whereNull('project_id')
            ->where('deleted_at', '<=', now()->subDays($this->getPruneDays()));
    }

    public function prune(): void
    {
        static::syncingFromTicket(fn() => app(ForceDeleteTaskAction::class)->execute($this));
    }

    public static function syncingFromTicket(callable $callback): mixed
    {
        $previous = static::$ticketSyncing;
        static::$ticketSyncing = true;

        try {
            return $callback();
        } finally {
            static::$ticketSyncing = $previous;
        }
    }

    public static function pullProjectMove(?int $taskId): bool
    {
        $moved = isset(static::$movedProjects[$taskId]);
        unset(static::$movedProjects[$taskId]);

        return $moved;
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

    public function scopeOwnedBy(Builder $query, int $userId): void
    {
        $query->where(fn(Builder $q) => $q->where('assigned_to', $userId)
            ->orWhere(fn(Builder $sq) => $sq->where('user_id', $userId)->whereNull('assigned_to'))
            ->orWhereHas('detail', fn(Builder $d) => $d->whereJsonContains('collaborators', $userId))
        );
    }

    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $query->where(fn(Builder $q) => $q->ownedBy($user->id)
            ->orWhere('user_id', $user->id)
            ->orWhereHas('detail', fn(Builder $d) => $d->where('responsible_user_id', $user->id))
            ->orWhereIn('project_id', Project::query()->visibleTo($user)->select('id'))
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

    protected function version(): Attribute
    {
        return Attribute::make(
            get: fn() => md5($this->updated_at?->timestamp . '|' . $this->status . '|' . $this->assigned_to . '|' . $this->rank)
        );
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
            $task->guardTicketLock(true);
            $task->guardApprovedFreeze(true);

            if (!$task->isForceDeleting()) {
                app(EventSyncService::class)->purge($task);
            }
        });

        static::restoring(fn(self $task) => $task->guardTicketLock(true));

        static::restored(function (self $task) {
            app(EventSyncService::class)->sync($task);
        });

        static::creating(function (self $task) {
            if ($task->status !== 'done') return;

            if ($task->approved_at === null) {
                $task->handleApprovalOnCompletion();
            } else {
                $task->completed_at ??= now();
            }
        });

        static::created(function (self $task) {
            app(ActivityLogger::class)->taskCreated($task, $task->ticket_id ? $task->creator : (auth()->user() ?? $task->creator));
        });

        static::saving(function (self $task) {
            if (static::$ticketSyncing) return;

            $assigning = $task->isDirty('assigned_to') && filled($task->assigned_to);
            $defaulting = blank($task->deadline) && $task->project_id && (!$task->exists || $task->isDirty(['deadline', 'project_id']));
            $project = ($assigning || $defaulting) && $task->project_id
                ? ($task->hasRosterProject() ? $task->project : Project::find($task->project_id, ['id', 'settings', 'owner_id', 'member_ids', 'departments']))
                : null;

            if ($assigning) {
                TaskAccessPolicy::assertAssignable([$task->assigned_to], $project);
            }

            if ($defaulting) {
                $task->deadline = $project?->charter()->defaultDeadline();
            }
        });

        static::updating(function (self $task) {
            $task->guardTicketLock();
            $task->guardApprovedFreeze();

            if ($task->isDirty('status')) {
                $task->clearArchiveOnStatusChange();
                $task->handleApprovalOnCompletion();
            }

            if ($task->isDirty('project_id')) {
                $task->reevaluateApprovalOnMove();
            }

            $task->guardPastDeadline();
            $task->rerankIfMoved();
        });

        static::saved(function (self $task) {
            if ($task->wasRecentlyCreated || !$task->wasChanged('project_id')) return;

            static::$movedProjects[$task->id] = true;

            if ($task->project_id === null) {
                try {
                    $task->detail?->update(['project' => null]);
                } finally {
                    unset(static::$movedProjects[$task->id]);
                }
            }

            if ($oldProjectId = $task->getOriginal('project_id')) {
                app(AdvanceWorkflowStepAction::class)->detachStep($task, $oldProjectId);
            }
        });

        static::deleted(fn (self $task) => app(AdvanceWorkflowStepAction::class)->detachStep($task));

        static::updated(function (self $task) {
            if ($task->project_id && $task->wasChanged('assigned_to')) {
                app(CycleLinkResolver::class)->syncAssignees($task, $task->relationLoaded('detail') ? ($task->detail?->collaborators ?? []) : null);
            }
        });

        static::updated(function (self $task) {
            if (!$task->project_id || $task->status !== 'done' || $task->approved_at === null) return;
            if (!$task->wasChanged('status') && !$task->wasChanged('approved_at')) return;
            app(AdvanceWorkflowStepAction::class)->completeForTask($task);
        });
    }

    protected function hasRosterProject(): bool
    {
        return $this->relationLoaded('project')
            && $this->project
            && (int)$this->project->getKey() === (int)$this->project_id
            && array_diff(['settings', 'owner_id', 'member_ids', 'departments'], array_keys($this->project->getAttributes())) === [];
    }

    protected function guardTicketLock(bool $deleting = false): void
    {
        if (!$this->ticket_id || static::$ticketSyncing) return;

        if (!$deleting && array_diff(array_keys($this->getDirty()), ['archived_at', 'updated_at']) === []) return;

        throw ValidationException::withMessages(['form' => 'این وظیفه از تیکت ساخته شده و فقط از طریق تیکت تغییر می‌کند.']);
    }

    protected function guardApprovedFreeze(bool $deleting = false): void
    {
        if (static::$ticketSyncing || !auth()->check()) return;

        if ($this->getOriginal('status') !== 'done' || $this->getOriginal('approved_at') === null) return;

        if (!$deleting && array_diff(array_keys($this->getDirty()), ['archived_at', 'rank', 'updated_at']) === []) return;

        $project = $this->isDirty('project_id')
            ? Project::find($this->getOriginal('project_id'), ['id', 'settings', 'owner_id'])
            : $this->approvalProject();

        if ($project?->setting('requires_approval') !== true || ProjectAccessPolicy::canManageTasks($project, auth()->user())) return;

        throw ValidationException::withMessages(['form' => 'این وظیفه تأیید شده و قفل است؛ فقط مالک پروژه می‌تواند آن را بازگشایی کند.']);
    }

    protected function guardPastDeadline(): void
    {
        if (static::$ticketSyncing || !$this->deadline || !$this->isDirty('deadline') || !auth()->check()) return;

        $original = $this->getOriginal('deadline');

        if ((!$original || !$this->deadline->isSameDay($original)) && $this->deadline->isBefore(today())) {
            throw ValidationException::withMessages(['deadline' => __('resources/task/strings.validation.deadline_past')]);
        }
    }

    protected function rerankIfMoved(): void
    {
        $ownerMoved = !$this->project_id
            && ($this->assigned_to ?? $this->user_id) !== ($this->getOriginal('assigned_to') ?? $this->getOriginal('user_id'));

        if ($this->isDirty('rank') || !($ownerMoved || $this->isDirty(['status', 'project_id', 'priority']))) return;

        $this->rank = static::rankForPriority($this->project_id, $this->assigned_to ?? $this->user_id, $this->status, $this->priority?->value, $this->id);
    }

    protected function clearArchiveOnStatusChange(): void
    {
        $reopened = $this->getOriginal('status') === 'done';

        if ($this->status !== 'done' && ($reopened || $this->archived_at !== null)) {
            $this->archived_at = null;
            $this->completed_at = null;

            if ($reopened) {
                $this->approved_at = null;
                $this->approved_by = null;
            }
        }
    }

    protected function reevaluateApprovalOnMove(): void
    {
        if ($this->status !== 'done' || $this->approved_at !== null || $this->isDirty('status')) {
            return;
        }

        $target = $this->project_id ? Project::find($this->project_id, ['id', 'settings']) : null;

        if (!$target?->charter()->requiresApproval()) {
            $this->approved_at = now();
            $this->approved_by = auth()->id();
        }
    }

    protected function handleApprovalOnCompletion(): void
    {
        if ($this->status !== 'done' || $this->getOriginal('status') === 'done') {
            return;
        }

        $this->completed_at ??= now();
        $project = $this->approvalProject();
        $actor = auth()->id();

        if ($project?->setting('requires_approval') !== true) {
            if ($this->approved_at === null) {
                $this->approved_at = now();
                $this->approved_by = $actor;
            }

            return;
        }

        if (ProjectAccessPolicy::canManageTasks($project, auth()->user())) {
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

    protected function isFrozen(): Attribute
    {
        return Attribute::make(get: fn() => $this->isFrozenFor(auth()->user()));
    }

    protected function canReopen(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === 'done'
                && $this->approved_at !== null
                && $this->archived_at === null
                && $this->approvalProject()?->setting('requires_approval') === true
                && ProjectAccessPolicy::canManageTasks($this->project, auth()->user())
        );
    }

    private ?array $cycleLink = null;

    private bool $cycleLinkLoaded = false;

    public function setCycleLink(?array $link): void
    {
        $this->cycleLink = $link;
        $this->cycleLinkLoaded = true;
    }

    public function cycleStep(): ?array
    {
        if (!$this->cycleLinkLoaded) {
            $this->setCycleLink($this->project_id && $this->exists ? app(CycleLinkResolver::class)->resolve(new Collection([$this]))[$this->id] ?? null : null);
        }

        return $this->cycleLink;
    }

    public function isCycleLocked(): bool
    {
        return $this->cycleStep()['is_current'] ?? false;
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

    public function isSuspended(): bool
    {
        return $this->relationLoaded('detail') && $this->detail?->state === TaskState::Suspension->value;
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

        if ($this->isSuspended()) {
            return ['score' => 0.0, 'kind' => 'suspended', 'label' => 'متوقف'];
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
        if ($sla !== null && $this->deadline === null && $this->created_at->diffInHours(now()) > (int)$sla) {
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
