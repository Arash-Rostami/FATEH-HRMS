<?php

namespace App\Models;

use App\Casts\IntegerList;
use App\Enums\NotifyEvent;
use App\Models\Concerns\Hooks\HasNotifyRuleHooks;
use App\Services\Notify\NotifyConditions;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotifyRule extends Model
{
    use MassPrunable,
        HasNotifyRuleHooks;

    public const ONE_OFF = 'manual';

    protected $fillable = [
        'name',
        'model',
        'events',
        'channels',
        'user_ids',
        'extra',
        'subject',
        'lines',
        'conditions',
        'is_active',
        'active_from',
        'active_until',
    ];

    protected $casts = [
        'events' => 'array',
        'channels' => 'array',
        'extra' => 'array',
        'lines' => 'array',
        'conditions' => 'array',
        'user_ids' => IntegerList::class,
        'is_active' => 'boolean',
        'active_from' => 'datetime',
        'active_until' => 'datetime',
    ];

    public function fires(NotifyEvent $event): bool
    {
        return in_array($event->value, $this->events ?? [], true);
    }

    public function watches(array $changed): bool
    {
        $watch = array_filter($this->extra['watch'] ?? []);

        return $watch === [] || array_intersect($watch, $changed) !== [];
    }

    public function matches(Model $model): bool
    {
        return blank($this->conditions['rules'] ?? null) || NotifyConditions::matches($model, $this->conditions);
    }

    public function isLive(?Carbon $at = null): bool
    {
        $at ??= now();

        return $this->is_active
            && ($this->active_from === null || $this->active_from <= $at)
            && ($this->active_until === null || $this->active_until >= $at);
    }

    public function scopeLive(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('active_from')->orWhere('active_from', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('active_until')->orWhere('active_until', '>=', $now));
    }

    public function isOneOff(): bool
    {
        return $this->model === self::ONE_OFF;
    }

    public function scopeOneOff(Builder $query): Builder
    {
        return $query->where('model', self::ONE_OFF);
    }

    public function prunable(): Builder
    {
        return static::oneOff()->where('created_at', '<', now()->subDays(90));
    }

    public function logs(): HasMany
    {
        return $this->hasMany(NotifyLog::class);
    }
}
