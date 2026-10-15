<?php

namespace App\Models;

use App\Enums\PresenceStatus;
use App\Filament\Resources\UserResource\Enums\UserType;
use App\Jobs\ReconcileNudge;
use App\Models\Concerns\HasAvatar as HasImage;
use App\Models\Concerns\HasProfileHierarchy;
use App\Services\Cache\ModelCacheVersion;
use App\Services\Cache\SkipsAutomaticCacheVersioning;
use App\Services\User\UserKeyGrouper;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable implements HasAvatar, FilamentUser, SkipsAutomaticCacheVersioning
{
    use HasFactory,
        Notifiable,
        HasProfileHierarchy,
        HasImage;

    public const ONLINE_MINUTES = 10;

    public const TOUCH_MINUTES = 3;

    public const INDEFINITE_FOCUS_MINUTES = 525600;

    protected static ?array $todaysDeskCache = null;
    protected static ?array $todaysReservationsCache = null;
    protected static ?array $todaysStatusSummaryCache = null;

    protected $fillable = [
        'name',
        'email',
        'password',
        'maximum',
        'type',
        'role',
        'status',
        'presence',
        'focus_until',
        'booking',
        'last_seen',
        'extra',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $attributes = [
        'maximum' => 12,
        'type' => 'employee',
        'role' => 'user',
        'status' => 'active',
        'presence' => PresenceStatus::Onsite->value,
    ];

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function authorities(): HasMany
    {
        return $this->hasMany(Authority::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($this->role) {
            'developer' => true,
            'admin' => $this->hasActiveAdminPermissions(),
            default => false,
        };
    }

    private function hasActiveAdminPermissions(): bool
    {
        $permission = Permission::forUser($this->id);

        return $permission && ($permission->is_super_admin || !empty($permission->abilities));
    }

    public function cancelledReservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }

    public function channels(): BelongsToMany
    {
        return $this->belongsToMany(Channel::class, 'channel_members')
            ->withPivot(['joined_at', 'last_read_message_id', 'created_at', 'updated_at']);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class);
    }

    public function energyTests(): HasMany
    {
        return $this->hasMany(EnergyTest::class, 'user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(FAQ::class);
    }

    public function feeds(): HasMany
    {
        return $this->hasMany(Feed::class);
    }

    public static function getCachedActiveOptions(): Collection
    {
        return Cache::remember(ModelCacheVersion::key(self::class, 'user_active_options'),
            now()->addHour(),
            fn() => self::visibleOnBoard()->orderBy('name')->pluck('name', 'id')
        );
    }

    public static function getCachedAdminOptions(): Collection
    {
        return Cache::remember(ModelCacheVersion::key(self::class, 'user_admin_options'),
            now()->addHours(6),
            fn() => self::where('role', 'admin')->orderBy('name')->pluck('name', 'id')
        );
    }

    public static function getCachedAllOptions(): Collection
    {
        return Cache::remember(ModelCacheVersion::key(self::class, 'user_all_options'),
            now()->addHours(6),
            fn() => self::orderBy('name')->pluck('name', 'id')
        );
    }

    public static function getCachedDepartmentOptions(string $dept): Collection
    {
        return Cache::remember(ModelCacheVersion::key(self::class, "user_dept_options:{$dept}"),
            now()->addHours(6),
            fn() => self::visibleOnBoard()->whereHas('profile', fn($q) => $q->where('department_id', $dept))->orderBy('name')->pluck('name', 'id')
        );
    }

    public static function getCachedNames(): Collection
    {
        return Cache::remember(ModelCacheVersion::key(self::class, 'user_names_map'),
            now()->addHour(),
            fn() => self::orderBy('name')->pluck('name', 'id')
        );
    }

    public function getExtraValue(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->extra ?? [], $key, $default);
    }

    public static function forgetDynamicGroupCache(): void
    {
        Cache::forget(UserKeyGrouper::CACHE_KEY);
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->getProfileImageUrl();
    }

    public function getInitialsAvatarUrl(): string
    {
        return $this->generateInitialsAvatar($this->name);
    }

    public function getPreference(string $key, mixed $default = null): mixed
    {
        return $this->getExtraValue("preferences.{$key}", $default);
    }

    public function getProfileImageUrl(): ?string
    {
        return $this->profile?->getImageUrl();
    }

    public function getTodaysDeskExtension(): ?string
    {
        return static::$todaysDeskCache[$this->id] ?? null;
    }

    public function getTodaysReservationsLabel(): ?string
    {
        return static::$todaysReservationsCache[$this->id] ?? null;
    }

    public function getTodaysStatusSummary(): ?string
    {
        return static::$todaysStatusSummaryCache[$this->id] ?? null;
    }

    public static function primeTodaysDeskCache(): void
    {
        if (static::$todaysDeskCache !== null) {
            return;
        }

        $reservations = Reservation::forToday()
            ->whereHas('resource', fn(Builder $q) => $q->whereIn('type', ['seat', 'car', 'meeting']))
            ->with('resource:id,type,name,metadata')
            ->get(['id', 'user_id', 'resource_id']);

        static::$todaysDeskCache = $reservations
            ->mapWithKeys(fn(Reservation $r) => [
                $r->user_id => Arr::get((array) $r->resource?->metadata, 'extension'),
            ])
            ->filter()
            ->all();

        static::$todaysStatusSummaryCache = $reservations
            ->mapWithKeys(fn(Reservation $r) => [
                $r->user_id => $r->resource?->status_summary,
            ])
            ->filter()
            ->all();

        static::$todaysReservationsCache = $reservations
            ->groupBy('user_id')
            ->map(fn(Collection $rows) => $rows->pluck('resource.labeled_name')->filter()->implode('، '))
            ->filter()
            ->all();
    }

    public static function resetTodaysDeskCache(): void
    {
        static::$todaysDeskCache = null;
        static::$todaysReservationsCache = null;
        static::$todaysStatusSummaryCache = null;
    }

    public function hasElevatedRole(): bool
    {
        return $this->isAdmin() || $this->isDeveloper();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isDeveloper(): bool
    {
        return $this->role === 'developer';
    }

    public function isFocusing(): bool
    {
        return $this->presence === PresenceStatus::Busy && ($this->focus_until?->isFuture() ?? false);
    }

    public function focusLabel(): ?string
    {
        if (!$this->isFocusing()) {
            return null;
        }

        return $this->focus_until->gt(now()->addDay())
            ? 'حالت تمرکز فعال'
            : 'متمرکز تا ' . toJalali($this->focus_until, 'H:i');
    }

    public function isOnline(): bool
    {
        return $this->last_seen !== null && $this->last_seen->gte(static::onlineSince());
    }

    public function lastSeenLabel(): string
    {
        return match (true) {
            $this->isOnline() => 'آنلاین',
            $this->last_seen === null => 'هرگز',
            default => toJalaliRelative($this->last_seen),
        };
    }

    public static function onlineSince(): Carbon
    {
        return now()->subMinutes(static::ONLINE_MINUTES);
    }

    public function latestEnergyTest(): HasOne
    {
        return $this->hasOne(EnergyTest::class)->latestOfMany('completed_at');
    }

    public function onboardings(): HasMany
    {
        return $this->hasMany(Onboarding::class);
    }

    public function permissions(): HasOne
    {
        return $this->hasOne(Permission::class);
    }

    public function permits(string $module, string $action): bool
    {
        if ($this->isDeveloper()) {
            return true;
        }

        return (bool)Permission::forUser($this->id)?->can($module, $action);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function getCasualNameAttribute(): string
    {
        $displayName = $this->profile?->detailsMap()->get('display_name');

        return filled($displayName) ? (string) $displayName : $this->name;
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(Read::class);
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function skillUsers(): HasMany
    {
        return $this->hasMany(SkillUser::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('users.status', 'active');
    }

    public function scopeVisibleOnBoard(Builder $query): Builder
    {
        return $query->active()->whereNot('type', UserType::Guest->value);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('users.last_seen', '>=', static::onlineSince());
    }

    public function scopeSearch(Builder $query, string $term): void
    {
        $query->when($term !== '', fn($q) => $q->where(fn($q) => $q
            ->where('name', 'like', "%{$term}%")
            ->orWhereHas('profile', fn($p) => $p
                ->where('position', 'like', "%{$term}%")
                ->orWhereHas('department', fn($d) => $d->whereAny(['name', 'code', 'description'], 'like', "%{$term}%"))
            )
        ));
    }

    public function scopeWithRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function setExtraValue(string $key, mixed $value): void
    {
        $extra = $this->extra ?? [];
        Arr::set($extra, $key, $value);
        $this->extra = $extra;
    }

    public function suggestions(): HasMany
    {
        return $this->hasMany(Suggestion::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function workflows(): HasMany
    {
        return $this->hasMany(Workflow::class, 'owner_id');
    }

    public function touchLastSeen(): void
    {
        $was = $this->timestamps;
        $this->timestamps = false;
        try {
            $this->forceFill(['last_seen' => now()])->saveQuietly();
        } finally {
            $this->timestamps = $was;
        }
    }

    public function markOffline(): void
    {
        $was = $this->timestamps;
        $this->timestamps = false;
        try {
            $this->forceFill(['last_seen' => now()->subMinutes(static::ONLINE_MINUTES + 1)])->saveQuietly();
            Cache::forget('last_seen:' . $this->id);
        } finally {
            $this->timestamps = $was;
        }
    }

    protected function booking(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $raw = is_array($value) ? $value : json_decode($value ?? '[]', true);
                if (!is_array($raw)) return [];

                $permissions = [];
                foreach ($raw as $k => $v) {
                    if (is_array($v)) {
                        if (array_key_exists('key', $v) && array_key_exists('value', $v)) {
                            $permissions[$v['key']] = $v['value'];
                        }
                        continue;
                    }
                    $permissions[$k] = $v;
                }

                return $permissions;
            },
            set: fn($value) => is_array($value) ? json_encode($value) : $value
        );
    }

    protected static function booted(): void
    {
        static::created(fn() => static::bumpCacheVersion());

        static::saved(function (self $user) {
            if ($user->wasChanged(['name', 'role', 'status', 'type'])) {
                static::bumpCacheVersion();
            }

            self::forgetDynamicGroupCache();

            if (!$user->wasRecentlyCreated && $user->wasChanged(['status', 'type'])
                && $user->getOriginal('status') === 'active' && $user->getOriginal('type') !== UserType::Guest->value
                && ($user->status !== 'active' || $user->type === UserType::Guest->value)) {
                $user->releaseFromProjectWork();
                $user->reconcileDepartmentTickets();
            }
        });

        static::deleted(function () {
            static::bumpCacheVersion();
            self::forgetDynamicGroupCache();
        });
    }

    protected function releaseFromProjectWork(): void
    {
        try {
            DB::transaction(function () {
                Task::where('assigned_to', $this->id)
                    ->where('status', '!=', 'done')
                    ->whereNull('archived_at')
                    ->whereNull('ticket_id')
                    ->get()
                    ->each->update(['assigned_to' => null]);

                Ticket::where('assigned_to', $this->id)
                    ->where('status', 'in-progress')
                    ->get()
                    ->each->update(['assigned_to' => null]);

                Project::stripTaskPeople(Task::query(), [$this->id]);

                Project::stripWorkflowAssignees(
                    Workflow::where('steps', 'like', '%' . $this->id . '%'),
                    [$this->id]
                );

                Project::where('owner_id', $this->id)->get()->each->ensureActiveOwner();
            });
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function reconcileDepartmentTickets(): void
    {
        $code = $this->profile()->value('department_id');

        if (!$code) {
            return;
        }

        Ticket::where('status', 'open')
            ->targetedAt($code)
            ->pluck('id')
            ->each(fn($id) => ReconcileNudge::dispatch('ths-controller:nudge', Ticket::class, $id));
    }

    protected static function bumpCacheVersion(): void
    {
        ModelCacheVersion::bump(self::class);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'last_seen' => 'datetime',
            'password' => 'hashed',
            'maximum' => 'integer',
            'presence' => PresenceStatus::class,
            'focus_until' => 'datetime',
        ];
    }

    protected function extra(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $this->decodeExtra($value),
            set: fn($value) => $this->mergeExtra($value),
        );
    }

    private function decodeExtra($value): array
    {
        $raw = is_array($value) ? $value : json_decode($value ?? '[]', true);

        return is_array($raw) ? $raw : [];
    }

    private function decodeStoredExtra(): array
    {
        if (!isset($this->attributes['extra'])) {
            return [];
        }

        $decoded = json_decode($this->attributes['extra'], true);

        return is_array($decoded) ? $decoded : [];
    }

    private function mergeExtra($value): string
    {
        $incoming = is_array($value) ? $value : json_decode($value ?? '[]', true);
        if (!is_array($incoming)) {
            $incoming = [];
        }

        $existing = $this->decodeStoredExtra();
        $result = array_filter($existing, fn($v) => is_array($v));

        $reserved = ['preferences', 'admin'];

        if (array_key_exists('preferences', $incoming) && is_array($incoming['preferences'] ?? null)) {
            $result['preferences'] = array_merge($result['preferences'] ?? [], $incoming['preferences']);

            if (array_key_exists('admin', $incoming)) {
                $result['admin'] = is_array($incoming['admin']) ? $incoming['admin'] : [];
            }

            foreach ($incoming as $k => $v) {
                if (!in_array($k, $reserved, true)) {
                    $result['admin'][$k] = $v;
                }
            }
        } else {
            $adminOverride = array_key_exists('admin', $incoming) && is_array($incoming['admin'])
                ? $incoming['admin']
                : [];

            unset($incoming['preferences'], $incoming['admin']);

            $result['admin'] = array_merge($adminOverride, $incoming);
        }

        return json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function smsNumber(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->profile?->cellphone
        )->shouldCache();
    }
}
