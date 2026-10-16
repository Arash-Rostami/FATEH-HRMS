<?php

namespace App\Models;

use App\Enums\TicketError;
use App\Livewire\Dashboard\Ths\Actions\AssignTicketAction;
use App\Models\Concerns\HasMenuState;
use App\Models\Concerns\HasPublicAssetUrl;
use App\Models\Concerns\HasReminders;
use App\Models\Concerns\HasReplies;
use App\Models\Concerns\HasTicketCountHelpers;
use App\Models\Concerns\HasTicketOptions;
use App\Traits\CleansAttachedFiles;
use BackedEnum;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class Ticket extends Model
{
    use HasFactory,
        HasMenuState,
        HasPublicAssetUrl,
        HasReplies,
        HasTicketCountHelpers,
        HasTicketOptions,
        CleansAttachedFiles,
        HasReminders;

    public const MENU_STATE_EVENTS = ['created', 'updated', 'deleted'];

    public const WITHDRAWN_RESULT = 'لغو توسط درخواست‌کننده';

    protected $fillable = [
        'requester_id',
        'request_type',
        'request_area',
        'request_subject',
        'description',
        'priority',
        'attachment',
        'additional_notes',
        'assigned_to',
        'completion_deadline',
        'completion_date',
        'action_result',
        'status',
        'effectiveness',
        'satisfaction_score',
        'requester_files',
        'assignee_files',
        'extra',
    ];

    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }

    public function effectiveTargetDepartment(): ?string
    {
        return $this->targetDepartmentId ?: static::defaultTargetDepartment();
    }

    public function assignableUsers(): Builder
    {
        $target = $this->effectiveTargetDepartment();

        return User::visibleOnBoard()->when(
            $target,
            fn(Builder $q) => $q->whereHas('profile', fn(Builder $pq) => $pq->where('department_id', $target)),
            fn(Builder $q) => $q->whereRaw('1 = 0'),
        );
    }

    public function currentActionRecipient(): ?User
    {
        return $this->currentActionRecipients()->first();
    }

    public function currentActionRecipients(): Collection
    {
        $target = $this->effectiveTargetDepartment();

        return match (true) {
            $this->status === 'open' => $target ? User::headsOfDepartment($target) : collect(),
            $this->status === 'in-progress' => collect([$this->assignee])->filter(),
            $this->isAwaitingRating() => collect([$this->requester])->filter(),
            default => collect(),
        };
    }

    public function isActionRecipient(User $user): bool
    {
        return $this->currentActionRecipients()->contains('id', $user->id);
    }

    public function isAwaitingRating(): bool
    {
        return $this->status === 'closed'
            && $this->satisfaction_score === null
            && $this->action_result !== static::WITHDRAWN_RESULT;
    }

    public static function defaultTargetDepartment(): ?string
    {
        return once(fn() => collect(['PS', 'BS'])
            ->first(fn($dept) => User::headsOfDepartment($dept)->isNotEmpty()));
    }

    public function composeActionResultFromReplies(): string
    {
        return $this->replies()
            ->get()
            ->map(fn(Reply $reply) => sprintf(
                '%s (%s): %s',
                $reply->user?->name ?? '—',
                toJalali($reply->created_at, 'Y/m/d H:i'),
                $reply->body,
            ))
            ->implode("\n");
    }

    protected function department(): Attribute
    {
        return Attribute::make(get: fn(): ?Department => $this->departmentId ? Department::getCachedModels()->get($this->departmentId) : null);
    }

    public static function hasUnclosedActionFor(int $userId): bool
    {
        $user = User::find($userId);

        return $user ? static::query()->actionableBy($user)->exists() : false;
    }

    public static function isClosingStatus(mixed $status): bool
    {
        return ($status instanceof BackedEnum ? $status->value : $status) === 'closed';
    }

    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requester_id'); }

    public function scopeActionableBy(Builder $query, User $user): Builder
    {
        $deptCode = $user->profile?->department_id;
        $isHead = $deptCode && $user->isHeadOfDepartment($deptCode);

        return $query->where(function (Builder $q) use ($user, $isHead, $deptCode) {
            $q->where(fn(Builder $sq) => $sq->where('assigned_to', $user->id)->where('status', 'in-progress'))
                ->orWhere(fn(Builder $sq) => $sq->awaitingRatingBy($user->id));

            if ($isHead) {
                $q->orWhere(fn(Builder $sq) => $sq->where('status', 'open')->targetedAt($deptCode));
            }
        });
    }

    public function scopeAwaitingRatingBy(Builder $query, int $userId): Builder
    {
        return $query->where('requester_id', $userId)
            ->where('status', 'closed')
            ->whereNull('satisfaction_score')
            ->where(fn(Builder $q) => $q->whereNull('action_result')->orWhere('action_result', '!=', static::WITHDRAWN_RESULT));
    }

    public function scopeSearchTerm(Builder $query, ?string $term): Builder
    {
        return $query->when(filled($term), fn(Builder $q) => $q->where(function (Builder $sub) use ($term) {
            $like = '%' . addcslashes($term, '\\%_') . '%';
            $sub->where('request_subject', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhereRaw("CONCAT(UPPER(COALESCE(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(extra, '$.target_department')), ''), 'null'), 'T')), '-', DATE_FORMAT(created_at, '%y%m'), '-', LPAD(id, GREATEST(4, LENGTH(id)), '0')) LIKE ?", [$like]);
        }));
    }

    public function scopeTargetedAt(Builder $query, string $deptCode): Builder
    {
        return $query->whereRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(extra, '$.target_department')), ?) = ?", [
            static::defaultTargetDepartment(),
            $deptCode,
        ]);
    }

    protected function targetDepartment(): Attribute
    {
        return Attribute::make(get: fn(): ?Department => $this->targetDepartmentId ? Department::getCachedModels()->get($this->targetDepartmentId) : null);
    }

    protected static function booted(): void
    {
        static::deleting(function (Ticket $ticket) {
            Task::syncingFromTicket(fn() => Task::where('ticket_id', $ticket->id)->get()->each->delete());
            $ticket->replies->each->delete();
            static::deleteStoredFiles($ticket->attachment);
            static::deleteStoredFiles($ticket->requester_files, ['file']);
            static::deleteStoredFiles($ticket->assignee_files, ['file']);
        });

        static::creating(function (Ticket $ticket) {
            $extra = $ticket->extra ?? [];

            if (empty($ticket->targetDepartmentId)) {
                $extra['target_department'] = static::defaultTargetDepartment();
            }

            if (empty($extra['department']) && filled($ticket->requester_id)) {
                $dept = Profile::where('user_id', $ticket->requester_id)->value('department_id');
                if (filled($dept)) {
                    $extra['department'] = $dept;
                }
            }

            $ticket->extra = $extra;
        });

        static::updated(function (Ticket $ticket) {
            if ($ticket->wasChanged('completion_deadline')) {
                Task::syncingFromTicket(fn() => Task::where('ticket_id', $ticket->id)->first()?->update(['deadline' => $ticket->completion_deadline]));
            }
        });

        static::saved(function (Ticket $ticket) {
            if ($ticket->wasRecentlyCreated) return;

            $syncs = $ticket->wasChanged(['assigned_to', 'priority']);
            $closes = $ticket->wasChanged('status') && static::isClosingStatus($ticket->status);

            if (!$syncs && !$closes) return;

            $assign = app(AssignTicketAction::class);

            if ($syncs) {
                $assign->syncForAdmin($ticket, $ticket->assigned_to);
            }

            if ($closes) {
                $assign->markLinkedTaskDone($ticket);
            }
        });

        static::saving(function (Ticket $ticket) {
            if ($ticket->exists && $ticket->isDirty('extra')) {
                $ticket->extra = [...($ticket->getOriginal('extra') ?? []), ...($ticket->extra ?? [])];
            }

            if ($ticket->exists && $ticket->isDirty('status') && $ticket->getOriginal('status') === 'closed' && !static::isClosingStatus($ticket->status)) {
                throw ValidationException::withMessages(['status' => 'تیکت بسته‌شده دوباره باز نمی‌شود.']);
            }

            if ($ticket->isDirty('assigned_to') && filled($ticket->assigned_to)) {
                if (static::isClosingStatus($ticket->status)) {
                    throw ValidationException::withMessages(['assigned_to' => 'امکان تغییر مسئول تیکت بسته‌شده وجود ندارد.']);
                }

                if (!$ticket->assignableUsers()->whereKey($ticket->assigned_to)->exists()) {
                    throw ValidationException::withMessages(['assigned_to' => 'کاربر انتخاب‌شده متعلق به این واحد سازمانی نیست.']);
                }
            }

            if ($ticket->exists && $ticket->isDirty('status') && $ticket->status === 'open') {
                $ticket->assigned_to = null;
            }

            if ($ticket->isDirty('assigned_to') && !$ticket->isDirty('status')) {
                if (blank($ticket->assigned_to) && $ticket->getOriginal('status') === 'in-progress') {
                    $ticket->status = 'open';
                } elseif (filled($ticket->assigned_to) && in_array($ticket->getOriginal('status'), ['open', null], true)) {
                    $ticket->status = 'in-progress';
                }
            }

            if (!$ticket->isDirty('status') || !static::isClosingStatus($ticket->status)) {
                return;
            }

            $ticket->completion_date ??= now();

            if ($ticket->action_result === static::WITHDRAWN_RESULT) {
                return;
            }

            if (blank($ticket->effectiveness)) {
                TicketError::EffectivenessRequired->throw();
            }

            if ($ticket->exists) {
                $ticket->action_result = $ticket->composeActionResultFromReplies();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'extra' => 'array',
            'completion_deadline' => 'datetime',
            'completion_date' => 'datetime',
            'satisfaction_score' => 'float',
            'requester_files' => 'array',
            'assignee_files' => 'array',
        ];
    }

    protected function departmentId(): Attribute
    {
        return Attribute::make(get: fn(): ?string => $this->extra['department'] ?? null)->shouldCache();
    }

    protected function priority(): Attribute
    {
        return Attribute::make(
            set: function (mixed $value): string {
                $v = strtolower($value instanceof BackedEnum ? $value->value : (is_scalar($value) ? (string)$value : ''));
                return in_array($v, ['low', 'medium', 'high'], true) ? $v : 'low';
            }
        );
    }

    protected function requestType(): Attribute
    {
        return Attribute::make(
            set: fn(mixed $value): string => $value instanceof BackedEnum ? (string)$value->value : (is_scalar($value) ? (string)$value : '')
        );
    }

    protected function satisfactionScore(): Attribute
    {
        return Attribute::make(
            set: fn(mixed $value): ?float => is_numeric($value) && $value >= 0 && $value <= 5 ? (float)$value : null
        );
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            set: function (mixed $value): string {
                $v = strtolower($value instanceof BackedEnum ? $value->value : (is_scalar($value) ? (string)$value : ''));
                return in_array($v, ['open', 'closed', 'in-progress'], true) ? $v : 'open';
            }
        );
    }

    protected function targetDepartmentId(): Attribute
    {
        return Attribute::make(get: fn(): ?string => $this->extra['target_department'] ?? null);
    }

    protected function typeLabel(): Attribute
    {
        return Attribute::make(get: fn(): string => static::getCustomRequestTypeLabel($this->request_type, $this->effectiveTargetDepartment()));
    }

    protected function areaLabel(): Attribute
    {
        return Attribute::make(get: fn(): string => static::getCustomRequestAreaLabel($this->request_type, $this->request_area, $this->effectiveTargetDepartment()));
    }

    protected function urgencyState(): Attribute
    {
        return Attribute::make(get: fn() => $this->calculateUrgencyState());
    }

    protected function calculateUrgencyState(): array
    {
        if ($this->status === 'closed') {
            return ['score' => 0.0, 'kind' => null, 'label' => null];
        }

        $today = now()->startOfDay();
        $urgency = ['score' => 0.0, 'kind' => null, 'label' => null];

        $urgency = $this->applyDeadlineUrgency($urgency, $today);
        $urgency = $this->applyPriorityFloor($urgency);
        $urgency = $this->applyIdleUrgency($urgency, $today);

        $urgency['score'] = round($urgency['score'], 2);

        return $urgency;
    }

    protected function applyDeadlineUrgency(array $urgency, $today): array
    {
        if (!$this->completion_deadline) {
            return $urgency;
        }

        $due = clone $this->completion_deadline;
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

    protected function applyPriorityFloor(array $urgency): array
    {
        $floor = match ($this->priority) {
            'high' => 0.55,
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
