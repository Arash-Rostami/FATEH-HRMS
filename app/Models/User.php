<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;


class User extends Authenticatable
{
    use HasFactory, Notifiable;

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

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
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

    public function getExtraValue(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->extra ?? [], $key, $default);
    }

    public function getSmsNumberAttribute(): ?string
    {
        return $this->profile?->cellphone;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOnline(int $minutes = 5): bool
    {
        return $this->last_seen && $this->last_seen->gte(now()->subMinutes($minutes));
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function scopeActive($query)
    {
        return $query->where('statusSwitcher', 'active');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOnline($query, int $minutes = 5)
    {
        return $query->where('last_seen', '>=', now()->subMinutes($minutes));
    }

    public function scopeWithRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function setExtraValue(string $key, mixed $value): void
    {
        $extra = $this->extra ?? [];
        Arr::set($extra, $key, $value);
        $this->extra = $extra;
    }

    public function touchLastSeen(): void
    {
        $this->update(['last_seen' => now()]);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_seen' => 'datetime',
            'extra' => 'array',
        ];
    }
}
