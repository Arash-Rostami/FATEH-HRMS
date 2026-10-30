<?php

namespace App\Models;

use App\Filament\Resources\ThsResource\Enums\TicketPriority;
use App\Filament\Resources\ThsResource\Enums\TicketStatus;
use App\Filament\Resources\UserResource\Enums\UserType;
use App\Models\Concerns\HasMenuState;
use App\Models\Concerns\HasPublicAssetUrl;
use App\Models\Concerns\HasReminders;
use App\Models\Concerns\HasReplies;
use App\Models\Concerns\HasTicketCountHelpers;
use App\Models\Concerns\HasTicketOptions;
use App\Models\Concerns\HasUrgencyState;
use App\Traits\CleansAttachedFiles;
use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\HasNotifyEvents;
use App\Models\Concerns\Hooks\HasTicketHooks;

class Ticket extends Model
{
    use HasFactory,
        HasMenuState,
        HasPublicAssetUrl,
        HasReplies,
        HasTicketCountHelpers,
        HasTicketOptions,
        CleansAttachedFiles,
        HasReminders,
        HasNotifyEvents,
        HasUrgencyState,
        HasTicketHooks;

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

    protected array $actionRecipientsMemo = [];

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
        return $this->actionRecipientsMemo[$this->actionRecipientsKey()] ??= match (true) {
            $this->status === 'open' => $this->openActionRecipients(),
            $this->status === 'in-progress' => collect([$this->assignee])->filter(),
            $this->isAwaitingRating() => collect([$this->requester])->filter(),
            default => collect(),
        };
    }

    private function actionRecipientsKey(): string
    {
        return "{$this->status}:" . ($this->assigned_to ?? '-') . ':' . ($this->effectiveTargetDepartment() ?? '-') . ':' . (int) $this->isAwaitingRating();
    }

    private function openActionRecipients(): Collection
    {
        $target = $this->effectiveTargetDepartment();
        $recipients = $target ? User::headsOfDepartment($target) : collect();

        if ($recipients->isEmpty() && $target && Department::freeAssignment($target)) {
            $recipients = User::whereIn('id', User::getCachedDepartmentOptions($target)->keys()->all())
                ->get()
                ->reject(fn($u) => $u->id === $this->requester_id)
                ->values();
        }

        return $recipients;
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

    public static function notifyExcept(): array
    {
        return ['requester_files', 'assignee_files', 'attachment'];
    }

    public static function notifyFields(): array
    {
        return [
            'extra.target_department' => 'department',
            'extra.department' => 'department',
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'requester_id' => 'user',
            'assigned_to' => 'user',
            'effectiveness' => [
                '5' => '★★★★★ — بسیار مؤثر',
                '4' => '★★★★☆ — مؤثر',
                '3' => '★★★☆☆ — خنثی',
                '2' => '★★☆☆☆ — کم‌اثر',
                '1' => '★☆☆☆☆ — بی‌اثر',
            ],
        ];
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
        $isFreeDept = $deptCode && $user->status === 'active' && $user->type !== UserType::Guest->value && Department::freeAssignment($deptCode);

        return $query->where(function (Builder $q) use ($user, $isHead, $isFreeDept, $deptCode) {
            $q->where(fn(Builder $sq) => $sq->where('assigned_to', $user->id)->where('status', 'in-progress'))
                ->orWhere(fn(Builder $sq) => $sq->awaitingRatingBy($user->id));

            if ($isHead) {
                $q->orWhere(fn(Builder $sq) => $sq->where('status', 'open')->targetedAt($deptCode));
            }

            if ($isFreeDept) {
                $q->orWhere(fn(Builder $sq) => $sq->where('status', 'open')->targetedAt($deptCode)->where('requester_id', '!=', $user->id));
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

    protected function urgencyExemptState(): ?array
    {
        return $this->status === 'closed'
            ? ['score' => 0.0, 'kind' => null, 'label' => null]
            : null;
    }

    protected function urgencyDeadline(): ?CarbonInterface
    {
        return $this->completion_deadline;
    }

    protected function urgencyPriorityFloor(): float
    {
        return match ($this->priority) {
            'high' => 0.55,
            default => 0.0,
        };
    }
}
