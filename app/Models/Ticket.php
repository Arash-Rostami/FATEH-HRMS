<?php

namespace App\Models;

use App\Enums\TicketError;
use App\Models\Traits\HasMenuState;
use App\Models\Traits\HasPublicAssetUrl;
use App\Models\Traits\HasReplies;
use App\Models\Traits\HasTicketCountHelpers;
use App\Models\Traits\HasTicketOptions;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasFactory,
        HasMenuState,
        HasPublicAssetUrl,
        HasReplies,
        HasTicketCountHelpers,
        HasTicketOptions;

    public const MENU_STATE_EVENTS = ['created', 'updated', 'deleted'];

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

    public function currentActionRecipient(): ?User
    {
        $target = $this->targetDepartmentId ?: static::defaultTargetDepartment();

        return match ($this->status) {
            'open' => $target ? User::highestRankingInDepartment($target) : null,
            'in-progress' => $this->assignee,
            'closed' => $this->requester,
            default => null,
        };
    }

    public static function defaultTargetDepartment(): ?string
    {
        return once(fn() => collect(['PS', 'BS'])
            ->first(fn($dept) => User::highestRankingInDepartment($dept) !== null));
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
        $isHead = $deptCode && User::highestRankingInDepartment($deptCode)?->is($user);

        return $query->where(function (Builder $q) use ($user, $isHead, $deptCode) {
            $q->where('assigned_to', $user->id)->where('status', 'in-progress');

            if ($isHead) {
                $q->orWhere(function (Builder $sq) use ($deptCode) {
                    $sq->where('status', 'open')
                        ->whereRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(extra, '$.target_department')), ?) = ?", [
                            static::defaultTargetDepartment(),
                            $deptCode,
                        ]);
                });
            }
        });
    }

    protected function targetDepartment(): Attribute
    {
        return Attribute::make(get: fn(): ?Department => $this->targetDepartmentId ? Department::getCachedModels()->get($this->targetDepartmentId) : null);
    }

    protected static function booted(): void
    {
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

        static::saving(function (Ticket $ticket) {
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
}
