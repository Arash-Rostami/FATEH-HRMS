<?php

namespace App\Models;

use App\Enums\ReminderRecurrence;
use App\Jobs\ReconcileEdge;
use App\Jobs\ReconcileNudge;
use App\Jobs\ReconcileReminderDue;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Reminder extends Model
{
    use HasFactory;

    protected $attributes = [
        'channels' => '["inapp:edge,badge,nudge"]',
    ];

    protected $fillable = [
        'user_id',
        'remindable_type',
        'remindable_id',
        'title',
        'notes',
        'due_at',
        'recurs',
        'snoozed_until',
        'completed_at',
        'notified_at',
        'channels',
    ];

    private static array $openByUser = [];

    protected static function booted(): void
    {
        $forget = fn () => static::$openByUser = [];

        static::saved(function (self $reminder) use ($forget) {
            $forget();
            $reminder->queueDueReconcile();
        });
        static::deleted($forget);
    }

    public function queueDueReconcile(): void
    {
        if ($this->completed_at !== null) {
            return;
        }

        $at = $this->snoozed_until !== null && $this->snoozed_until->gt($this->due_at) ? $this->snoozed_until : $this->due_at;

        if ($at->isFuture() && $at->lte(now()->addDay()) && Cache::add("reminder-due-queued:{$this->getKey()}:{$at->timestamp}", true, $at)) {
            dispatch(new ReconcileReminderDue($this->getKey()))->delay($at)->afterCommit();
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function remindable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('completed_at')
            ->where(fn (Builder $q) => $q->whereNull('snoozed_until')->orWhere('snoozed_until', '<=', now()));
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->active()->where('due_at', '<', now()->startOfDay());
    }

    public function scopeDueToday(Builder $query): Builder
    {
        return $query->active()->whereBetween('due_at', [
            now()->startOfDay(),
            now()->endOfDay(),
        ]);
    }

    public function scopeThisWeek(Builder $query): Builder
    {
        $weekStart = now()->startOfWeek(Carbon::SATURDAY);

        return $query->active()->whereBetween('due_at', [
            $weekStart,
            $weekStart->copy()->addDays(6)->endOfDay(),
        ]);
    }

    public static function suggestedTitles(int $userId, int $limit = 8): array
    {
        return static::forUser($userId)
            ->select('title')
            ->selectRaw('COUNT(*) as uses')
            ->groupBy('title')
            ->orderByDesc('uses')
            ->limit($limit)
            ->pluck('title')
            ->all();
    }

    public static function channelEnabled(array $channels, string $signal): bool
    {
        foreach ($channels as $channel) {
            if (str_starts_with($channel, 'inapp:')) {
                return in_array($signal, explode(',', substr($channel, 6)), true);
            }
        }

        return false;
    }

    public static function withoutChannel(array $channels, string $signal): array
    {
        $result = [];

        foreach ($channels as $channel) {
            if (! str_starts_with($channel, 'inapp:')) {
                $result[] = $channel;

                continue;
            }

            $kept = array_values(array_filter(explode(',', substr($channel, 6)), fn (string $s) => $s !== $signal));

            if ($kept !== []) {
                $result[] = 'inapp:' . implode(',', $kept);
            }
        }

        return $result;
    }

    public static function openForUser(int $userId): Collection
    {
        return static::$openByUser[$userId] ??= static::forUser($userId)
            ->whereNull('completed_at')
            ->orderBy('due_at')
            ->get(['id', 'user_id', 'channels', 'remindable_type', 'remindable_id', 'due_at', 'snoozed_until']);
    }

    public static function dueBadgeCount(int $userId, ?string $remindableType = null, mixed $remindableId = null): int
    {
        return static::openForUser($userId)
            ->filter(fn (Reminder $reminder) => ($remindableType === null || ($reminder->remindable_type === $remindableType && $reminder->remindable_id == $remindableId))
                && ($reminder->snoozed_until === null || $reminder->snoozed_until->lte(now()))
                && $reminder->due_at->lte(now())
                && static::channelEnabled($reminder->channels, 'badge')
                && !$reminder->hostTrashed())
            ->count();
    }

    public static function urlFor(?string $remindableType, mixed $remindableId): ?string
    {
        if ($remindableType === null || $remindableId === null) {
            return null;
        }

        return match ($remindableType) {
            Task::class => route('tasks', ['open' => $remindableId]),
            Ticket::class => route('ths', ['open' => $remindableId]),
            Project::class => route('projects', ['open' => $remindableId]),
            DMS::class => route('dms', ['open' => $remindableId]),
            Reservation::class => route('reservation', ['open' => $remindableId]),
            default => null,
        };
    }

    public function hostUrl(): ?string
    {
        return static::urlFor($this->remindable_type, $this->remindable_id);
    }

    public function hostTrashed(): bool
    {
        if ($this->remindable_type === null || $this->remindable_id === null) {
            return false;
        }

        if (!method_exists($this->remindable_type, 'trashed')) {
            return false;
        }

        return $this->remindable_type::onlyTrashed()->whereKey($this->remindable_id)->exists();
    }

    public function snooze(Carbon $until): void
    {
        $this->update([
            'snoozed_until' => $until,
            'notified_at' => null,
        ]);
    }

    public function complete(): bool
    {
        return DB::transaction(function () {
            $now = now();

            $affected = static::whereKey($this->getKey())
                ->whereNull('completed_at')
                ->update(['completed_at' => $now]);

            if ($affected === 0) {
                return false;
            }

            static::$openByUser = [];

            $this->completed_at = $now;

            dispatch(new ReconcileNudge('reminders-controller:due-nudge', static::class, $this->getKey()))->afterCommit();
            dispatch(new ReconcileEdge('reminders-controller:due-today-edge', static::class, $this->getKey()))->afterCommit();

            if ($this->recurs !== ReminderRecurrence::None) {
                static::create([
                    'user_id' => $this->user_id,
                    'remindable_type' => $this->remindable_type,
                    'remindable_id' => $this->remindable_id,
                    'title' => $this->title,
                    'notes' => $this->notes,
                    'recurs' => $this->recurs,
                    'channels' => $this->remindable_type === null
                        ? static::withoutChannel($this->channels, 'badge')
                        : $this->channels,
                    'due_at' => $this->recurs->advance($this->due_at),
                    'completed_at' => null,
                    'snoozed_until' => null,
                    'notified_at' => null,
                ]);
            }

            return true;
        });
    }

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'snoozed_until' => 'datetime',
            'completed_at' => 'datetime',
            'notified_at' => 'datetime',
            'recurs' => ReminderRecurrence::class,
            'channels' => 'array',
        ];
    }
}
