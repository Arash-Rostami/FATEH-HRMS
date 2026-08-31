<?php

namespace App\Models;

use App\Enums\TaskActivityType;
use App\Jobs\SyncProjectChannelMembershipJob;
use App\Livewire\Dashboard\Project\Actions\ForceDeleteProjectAction;
use App\Models\Concerns\HasPrunableStatus;
use App\Models\Concerns\HasReplies;
use App\Services\ProjectTask\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Morilog\Jalali\CalendarUtils;

class Project extends Model
{
    use HasFactory,
        HasReplies,
        SoftDeletes,
        Prunable,
        HasPrunableStatus;

    protected $fillable = [
        'name',
        'slug',
        'owner_id',
        'member_ids',
        'departments',
        'channel_id',
        'settings',
        'archived_at',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public static function generateSlug(string $name): string
    {
        $base = preg_replace('/[^\p{L}\p{N}]+/u', '-', mb_strtolower(trim($name)));
        $base = trim(preg_replace('/-{2,}/u', '-', (string)$base), '-');

        return ($base !== '' ? $base : 'project') . '-' . Str::random(6);
    }

    public function isVisibleTo(User $user): bool
    {
        if ($this->owner_id === $user->id || in_array($user->id, $this->member_ids ?? [], true)) {
            return true;
        }

        $departmentCode = $user->profile?->department_id;

        return filled($departmentCode) && in_array($departmentCode, $this->departments ?? [], true);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function prunable(): Builder
    {
        return static::where('deleted_at', '<=', now()->subDays($this->getPruneDays()));
    }

    public function prune(): void
    {
        app(ForceDeleteProjectAction::class)->execute($this);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->where('owner_id', $user->id)
                ->orWhereJsonContains('member_ids', $user->id);

            $departmentCode = $user->profile?->department_id;
            if (filled($departmentCode)) {
                $q->orWhere(fn(Builder $x) => $x->whereNotNull('departments')
                    ->whereRaw('JSON_LENGTH(departments) > 0')
                    ->whereJsonContains('departments', $departmentCode));
            }
        });
    }

    public const KNOWN_SETTING_KEYS = ['requires_approval', 'sla', 'deadline', 'custom_schema'];

    public function setting(string $key, mixed $default = null): mixed
    {
        return ($this->settings ?? [])[$key] ?? $default;
    }

    public function deadlineCapExceeded(?Carbon $deadline): bool
    {
        $cap = $this->setting('deadline');

        return $cap !== null && $deadline !== null && $deadline->gt(Carbon::parse($cap)->endOfDay());
    }

    public function settingsSummary(): string
    {
        $parts = [];

        if ($this->setting('requires_approval') === true) {
            $parts[] = 'تأیید مدیر فعال';
        }

        $sla = $this->setting('sla');
        if ($sla) {
            $parts[] = 'SLA: ' . $sla . ' ساعت';
        }

        $deadline = $this->setting('deadline');
        if ($deadline && ($timestamp = strtotime($deadline))) {
            $parts[] = 'مهلت: ' . CalendarUtils::strftime('Y/m/d', $timestamp);
        }

        $schema = $this->setting('custom_schema');
        if (is_array($schema) && $schema !== []) {
            $parts[] = count($schema) . ' فیلد سفارشی';
        }

        return $parts !== [] ? implode('، ', $parts) : '—';
    }

    public function otherSettings(): array
    {
        return collect($this->settings ?? [])
            ->reject(fn($value, $key) => in_array($key, self::KNOWN_SETTING_KEYS, true) || $value === null || $value === '')
            ->map(fn($value) => is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE))
            ->all();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    protected static function booted(): void
    {
        static::saved(function (self $project) {
            $project->syncChannelMembershipIfNeeded();
            $project->logMembershipChangesIfNeeded();
            $project->logSettingsChangesIfNeeded();
        });
    }

    protected function casts(): array
    {
        return [
            'member_ids' => 'array',
            'departments' => 'array',
            'settings' => 'array',
            'archived_at' => 'datetime',
        ];
    }

    protected function logSettingsChangesIfNeeded(): void
    {
        if ($this->wasRecentlyCreated || !$this->wasChanged('settings')) {
            return;
        }

        $before = $this->getOriginal('settings') ?? [];
        $after = $this->settings ?? [];

        $changed = [];

        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $key) {
            if (($before[$key] ?? null) !== ($after[$key] ?? null)) {
                $changed[] = $key;
            }
        }

        if ($changed === []) {
            return;
        }

        app(ActivityLogger::class)->system($this, auth()->user(), TaskActivityType::SettingsChange, ['changed' => $changed]);
    }

    protected function logMembershipChangesIfNeeded(): void
    {
        if ($this->wasRecentlyCreated || !$this->wasChanged(['member_ids', 'departments'])) {
            return;
        }

        $payload = [];

        if ($this->wasChanged('member_ids')) {
            $before = $this->getOriginal('member_ids') ?? [];
            $after = $this->member_ids ?? [];
            $payload['added'] = array_values(array_diff($after, $before));
            $payload['removed'] = array_values(array_diff($before, $after));
        }

        if ($this->wasChanged('departments')) {
            $before = $this->getOriginal('departments') ?? [];
            $after = $this->departments ?? [];
            $payload['added_departments'] = array_values(array_diff($after, $before));
            $payload['removed_departments'] = array_values(array_diff($before, $after));
        }

        app(ActivityLogger::class)->system(
            $this,
            auth()->user(),
            TaskActivityType::Assignment,
            $payload
        );
    }

    protected function syncChannelMembershipIfNeeded(): void
    {
        $membershipRelevant = $this->wasRecentlyCreated
            || $this->wasChanged(['member_ids', 'departments']);

        if ($membershipRelevant) {
            SyncProjectChannelMembershipJob::dispatch($this->id);
        }
    }
}
