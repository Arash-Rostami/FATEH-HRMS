<?php

namespace App\Models;

use App\Enums\TaskActivityType;
use App\Filament\Resources\TaskResource\Enums\TaskPriority;
use App\Filament\Resources\TaskResource\Enums\TaskState;
use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Livewire\Dashboard\TaskBoard\Actions\ForceDeleteTaskAction;
use App\Models\Concerns\HasBoardRanking;
use App\Models\Concerns\HasCycleStep;
use App\Models\Concerns\HasJalaliAdminLabels;
use App\Models\Concerns\HasMenuState;
use App\Models\Concerns\HasPrunableStatus;
use App\Models\Concerns\HasNotifyEvents;
use App\Models\Concerns\HasReminders;
use App\Models\Concerns\HasReplies;
use App\Models\Concerns\HasTaskActivityLog;
use App\Models\Concerns\HasTaskApproval;
use App\Models\Concerns\HasUrgencyState;
use App\Models\Concerns\Hooks\HasTaskHooks;
use App\Support\TaskAccessPolicy;
use App\Traits\CleansAttachedFiles;
use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory,
        HasJalaliAdminLabels,
        HasMenuState,
        HasNotifyEvents,
        HasReplies,
        HasReminders,
        SoftDeletes,
        Prunable,
        HasPrunableStatus,
        CleansAttachedFiles,
        HasTaskActivityLog,
        HasBoardRanking,
        HasCycleStep,
        HasTaskApproval,
        HasUrgencyState,
        HasTaskHooks;

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

    public static function notifyFields(): array
    {
        return [
            'status' => TaskStatus::class,
            'user_id' => 'user',
            'assigned_to' => 'user',
            'approved_by' => 'user',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    public function scopeStatus(Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    public function scopeLabelFilter(Builder $query, array $labels): void
    {
        foreach ($labels as $label) {
            $canonical = str_replace(['ي', 'ك'], ['ی', 'ک'], $label);
            $variant = str_replace(['ی', 'ک'], ['ي', 'ك'], $canonical);
            $query->where(fn(Builder $sub) => $sub
                ->whereJsonContains('labels', $canonical)
                ->orWhereJsonContains('labels', $variant));
        }
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

    protected function hasRosterProject(): bool
    {
        return $this->relationLoaded('project')
            && $this->project
            && (int)$this->project->getKey() === (int)$this->project_id
            && array_diff(['settings', 'owner_id', 'member_ids', 'departments'], array_keys($this->project->getAttributes())) === [];
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

    protected function urgencyExemptState(): ?array
    {
        if (in_array($this->status, ['done', 'pending'], true)) {
            return ['score' => 0.0, 'kind' => null, 'label' => null];
        }

        return $this->isSuspended()
            ? ['score' => 0.0, 'kind' => 'suspended', 'label' => 'متوقف']
            : null;
    }

    protected function applyExtraUrgency(array $urgency): array
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

    protected function urgencyDeadline(): ?CarbonInterface
    {
        return $this->deadline;
    }

    protected function urgencyPriorityFloor(): float
    {
        return match ($this->priority) {
            TaskPriority::Urgent => 0.85,
            TaskPriority::High => 0.55,
            default => 0.0,
        };
    }
}
