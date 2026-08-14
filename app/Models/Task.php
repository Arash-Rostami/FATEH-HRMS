<?php

namespace App\Models;

use App\Livewire\Dashboard\TaskBoard\Actions\ForceDeleteTaskAction;
use App\Models\Traits\HasJalaliAdminLabels;
use App\Models\Traits\HasMenuState;
use App\Models\Traits\HasPrunableStatus;
use App\Models\Traits\HasReplies;
use App\Traits\CleansAttachedFiles;
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
        HasReplies,
        SoftDeletes,
        Prunable,
        HasPrunableStatus,
        CleansAttachedFiles;

    public const MENU_STATE_EVENTS = ['created', 'updated', 'deleted', 'restored', 'forceDeleted'];

    protected $fillable = [
        'title',
        'description',
        'status',
        'deadline',
        'user_id',
        'assigned_to',
        'ticket_id',
        'archived_at',
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

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
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
        $counts = self::query()->forUser($userId)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'in-progress' => (int)($counts['in-progress'] ?? 0),
            'todo' => (int)($counts['todo'] ?? 0),
            'pending' => (int)($counts['pending'] ?? 0),
        ];
    }

    public static function getTodoCount(int $userId): int
    {
        return self::getStatusCounts($userId)['todo'];
    }

    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where(fn(Builder $q) => $q->where('assigned_to', $userId)
            ->orWhere(fn(Builder $sq) => $sq->where('user_id', $userId)->whereNull('assigned_to'))
        );
    }

    public function scopeStatus(Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    protected function assigneeName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->assignee?->name
        )->shouldCache();
    }

    protected function canChangeStatus(): Attribute
    {
        return Attribute::make(
            get: function () {
                $userId = auth()->id();
                return $this->assigned_to === $userId || ($this->user_id === $userId && !$this->assigned_to);
            }
        );
    }

    protected function canDelete(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->user_id === auth()->id()
        );
    }

    protected function isArchived(): Attribute
    {
        return Attribute::make(
            get: fn() => (bool) $this->archived_at
        )->shouldCache();
    }

    protected function casts(): array
    {
        return [
            'deadline' => 'datetime',
            'archived_at' => 'datetime',
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
            get: fn() => $this->deadline ? toJalali($this->deadline, 'j F Y') : null
        )->shouldCache();
    }

    protected function delegatorName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->creator?->name
        )->shouldCache();
    }

    protected function isDelegator(): Attribute
    {
        return Attribute::make(
            get: function () {
                $userId = auth()->id();
                return $this->user_id === $userId && $this->assigned_to && $this->assigned_to !== $userId;
            }
        );
    }

    protected function urgencyState(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (in_array($this->status, ['done', 'pending'], true)) {
                    return ['score' => 0.0, 'kind' => null, 'label' => null];
                }

                $score = 0.0;
                $kind = null;
                $label = null;
                $today = now()->startOfDay();

                if ($this->deadline) {
                    $due = $this->deadline->startOfDay();
                    $days = (int) $due->diffInDays($today, true);

                    if ($due->isBefore($today)) {
                        $score = 1.0;
                        $kind = 'overdue';
                        $label = $days . ' روز تأخیر';
                    } elseif ($days <= 3) {
                        $score = 0.7;
                        $kind = 'due';
                        $label = match ($days) {
                            0 => 'امروز',
                            1 => 'فردا',
                            default => $days . ' روز دیگر',
                        };
                    }
                }

                $idle = $this->updated_at ? (int) $this->updated_at->startOfDay()->diffInDays($today, true) : 0;

                if ($idle >= 7) {
                    if ($score < 0.45) {
                        $score = 0.45;
                    }
                    $kind ??= 'idle';
                    $label ??= 'چند روز بی‌تغیر';
                } elseif ($idle >= 3) {
                    if ($score < 0.22) {
                        $score = 0.22;
                    }
                    $kind ??= 'idle';
                    $label ??= 'چند روز بی‌تغیر';
                }

                return ['score' => round($score, 2), 'kind' => $kind, 'label' => $label];
            }
        );
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            set: fn(mixed $value) => $value instanceof \BackedEnum ? $value->value : $value,
        );
    }

    public function prunable()
    {
        return static::with('detail')->where('deleted_at', '<=', now()->subDays($this->getPruneDays()));
    }

    public function prune()
    {
        app(ForceDeleteTaskAction::class)->execute($this);
    }

    protected static function booted(): void
    {
        static::forceDeleting(function (self $task) {
            if ($task->detail) {
                static::deleteStoredFiles($task->detail->attachments);
            }
        });

        static::updating(function (self $task) {
            if ($task->isDirty('status') && $task->status !== 'done' && $task->archived_at !== null) {
                $task->archived_at = null;
            }
        });
    }
}
