<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\PresenceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;


class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected  = [
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


    protected  = [
        'password',
        'remember_token',
    ];

    public function comments(): HasMany
    {
        return ->hasMany(Comment::class);
    }

    public function credentials(): HasMany
    {
        return ->hasMany(Credential::class);
    }

    public function events(): HasMany
    {
        return ->hasMany(Event::class);
    }

    public function faqs(): HasMany
    {
        return ->hasMany(FAQ::class);
    }

    public function feeds(): HasMany
    {
        return ->hasMany(Feed::class);
    }

    public function getExtraValue(string , mixed  = null): mixed
    {
        return Arr::get(->extra ?? [], , );
    }

    public function getSmsNumberAttribute(): ?string
    {
        return ->profile?->cellphone;
    }

    public function getTodaysDeskExtension(): ?string
    {
        return null;
    }

    public function isActive(): bool
    {
        return ->status === 'active';
    }

    public function isAdmin(): bool
    {
        return ->role === 'admin';
    }

    public function isOnline(int  = 5): bool
    {
        return ->last_seen && ->last_seen->gte(now()->subMinutes());
    }

    public function posts(): HasMany
    {
        return ->hasMany(Post::class);
    }

    public function profile(): HasOne
    {
        return ->hasOne(Profile::class);
    }

    public function reactions(): HasMany
    {
        return ->hasMany(Reaction::class);
    }

    public function reports(): HasMany
    {
        return ->hasMany(Report::class);
    }

    public function scopeActive()
    {
        return ->where('status', 'active');
    }

    public function scopeOfType(, string )
    {
        return ->where('type', );
    }

    public function scopeOnline(, int  = 5)
    {
        return ->where('last_seen', '>=', now()->subMinutes());
    }

    public function scopeSearch(Builder , string ): void
    {
        ->where(fn(Builder ) =>
            ->where('name', 'like', '%' .  . '%')
            ->orWhereHas('profile', fn(Builder ) =>
                ->where('position', 'like', '%' .  . '%')
                ->orWhereHas('department', fn(Builder ) =>
                    ->where('name', 'like', '%' .  . '%')
                )
            )
        );
    }

    public function scopeWithRole(, string )
    {
        return ->where('role', );
    }

    public function setExtraValue(string , mixed ): void
    {
         = ->extra ?? [];
        Arr::set(, , );
        ->extra = ;
    }

    public function touchLastSeen(): void
    {
        ->update(['last_seen' => now()]);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_seen' => 'datetime',
            'extra' => 'array',
            'presence' => PresenceStatus::class,
        ];
    }
}
