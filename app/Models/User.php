<?php

namespace App\Models;

use App\Enums\PresenceStatus;
use App\Models\Traits\HasAvatar as HasImage;
use App\Models\Traits\HasProfileHierarchy;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable implements HasAvatar, FilamentUser
{
    use HasFactory,
        Notifiable,
        HasProfileHierarchy,
        HasImage;

    protected $fillable = [
        'name',
        'email',
        'password',
        'maximum',
        'type',
        'role',
        'status',
        'presence',
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
        'presence' => 'remote',
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
            'admin' => ($p = Permission::forUser($this->id)) && ($p->is_super_admin || !empty($p->abilities)),
            default => false,
        };
    }

    public function cancelledReservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
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
        return once(fn() => Cache::remember('user_active_options',
            now()->addHour(),
            fn() => self::active()->orderBy('name')->pluck('name', 'id')
        ));
    }

    public static function getCachedAllOptions(): Collection
    {
        return once(fn() => Cache::remember('user_all_options',
            now()->addHours(6),
            fn() => self::orderBy('name')->pluck('name', 'id')
        ));
    }

    /** Admin-role users only — permission rows are an admin-only concept (developers are super by role, users can't reach the panel). */
    public static function getCachedAdminOptions(): Collection
    {
        return once(fn() => Cache::remember('user_admin_options',
            now()->addHours(6),
            fn() => self::where('role', 'admin')->orderBy('name')->pluck('name', 'id')
        ));
    }

    public static function getCachedNames(): Collection
    {
        return once(fn() => Cache::remember('user_names_map',
            now()->addHour(),
            fn() => self::orderBy('name')->pluck('name', 'id')
        ));
    }

    public function getExtraValue(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->extra ?? [], $key, $default);
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
        return null;
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

    public function isOnline(int $minutes = 5): bool
    {
        return $this->last_seen && $this->last_seen->gte(now()->subMinutes($minutes));
    }

    public function latestEnergyTest()
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
        // Developers are super-admin by role — every module, every action, no exclusions.
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

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOnline($query, int $minutes = 5)
    {
        return $query->where('last_seen', '>=', now()->subMinutes($minutes));
    }

    public function scopeSearch(Builder $query, string $term): void
    {
        $query->when($term, fn($q) => $q->where(fn($q) => $q
            ->where('name', 'like', "%{$term}%")
            ->orWhereHas('profile', fn($p) => $p
                ->where('position', 'like', "%{$term}%")
                ->orWhereHas('department', fn($d) => $d->whereAny(['name', 'code', 'description'], 'like', "%{$term}%"))
            )
        ));
    }

    public function scopeWithRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function sentMessages()
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

    public function touchLastSeen(): void
    {
        $this->timestamps = false;
        $this->update(['last_seen' => now()]);
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

    protected function extra(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $raw = is_array($value) ? $value : json_decode($value ?? '[]', true);
                return is_array($raw) ? $raw : [];
            },
            set: function ($value) {
                $incoming = is_array($value) ? $value : json_decode($value ?? '[]', true);
                if (!is_array($incoming)) {
                    $incoming = [];
                }

                $existing = [];
                if (isset($this->attributes['extra'])) {
                    $decoded = json_decode($this->attributes['extra'], true);
                    $existing = is_array($decoded) ? $decoded : [];
                }

                $result = [];
                foreach ($existing as $k => $v) {
                    if (is_array($v)) {
                        $result[$k] = $v;
                    }
                }

                $reserved = ['preferences', 'admin'];

                if (array_key_exists('preferences', $incoming) && is_array($incoming['preferences'] ?? null)) {
                    $result['preferences'] = array_merge(
                        $result['preferences'] ?? [],
                        $incoming['preferences'],
                    );

                    if (array_key_exists('admin', $incoming)) {
                        $result['admin'] = is_array($incoming['admin']) ? $incoming['admin'] : [];
                    }

                    foreach ($incoming as $k => $v) {
                        if (in_array($k, $reserved, true)) {
                            continue;
                        }
                        $result['admin'][$k] = $v;
                    }
                } else {
                    foreach ($reserved as $k) {
                        unset($incoming[$k]);
                    }
                    $result['admin'] = $incoming;
                }

                return json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            },
        );
    }

    protected static function booted(): void
    {
        $flushCache = fn() => collect([
            'user_active_options',
            'user_all_options',
            'user_names_map',
            'user_admin_options',
        ])->each(Cache::forget(...));

        static::saved($flushCache);
        static::deleted($flushCache);
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
        ];
    }


    protected function smsNumber(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->profile?->cellphone
        );
    }
}
