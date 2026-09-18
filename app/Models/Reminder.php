<?php

namespace App\Models;

use App\Enums\ReminderRecurrence;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
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

    public static function dueBadgeCount(int $userId, ?string $remindableType = null, mixed $remindableId = null): int
    {
        return static::forUser($userId)
            ->when($remindableType !== null, fn (Builder $q) => $q
                ->where('remindable_type', $remindableType)
                ->where('remindable_id', $remindableId))
            ->active()
            ->where('due_at', '<=', now()->endOfDay())
            ->get(['id', 'channels', 'remindable_type', 'remindable_id'])
            ->filter(fn (Reminder $reminder) => static::channelEnabled($reminder->channels, 'badge') && !$reminder->hostTrashed())
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

            $this->completed_at = $now;

            if ($this->recurs !== ReminderRecurrence::None) {
                static::create([
                    'user_id' => $this->user_id,
                    'remindable_type' => $this->remindable_type,
                    'remindable_id' => $this->remindable_id,
                    'title' => $this->title,
                    'notes' => $this->notes,
                    'recurs' => $this->recurs,
                    'channels' => $this->channels,
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
