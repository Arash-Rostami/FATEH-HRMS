<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Skill extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_en',
        'category',
        'description',
        'icon',
        'is_active',
        'is_ghost',
        'search_count',
        'last_searched_at',
    ];

    public function activate(): void
    {
        $this->is_active = true;
        $this->is_ghost = false;
        $this->save();
    }

    public static function cachedActiveCatalog(): Collection
    {
        return once(fn() => Cache::remember(
            'skill_active_catalog',
            now()->addDay(),
            fn() => self::activeCatalog()
                ->orderBy('category')
                ->orderBy('name')
                ->get(['id', 'name', 'name_en', 'category'])
        ));
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'skill_user')->using(SkillUser::class);
    }

    public function recordSearch(): void
    {
        $this->increment('search_count', 1, ['last_searched_at' => now()]);
    }

    public function scopeActiveCatalog($query)
    {
        return $query->where('is_active', true)->where('is_ghost', false);
    }

    public function scopeGhost($query)
    {
        return $query->where('is_ghost', true);
    }

    public function scopeMatchingName($query, string $name)
    {
        return $query->where(fn($q) => $q->where('name', $name)->orWhere('name_en', $name));
    }

    public function skillUsers(): HasMany
    {
        return $this->hasMany(SkillUser::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (Skill $skill) {
            if ($skill->skillUsers()->exists()) {
                throw new \RuntimeException('این مهارت توسط کاربران اتخاب شده و قابل حذف نیست.');
            }
        });

        $forgetCache = fn() => Cache::forget('skill_active_catalog');

        static::saved($forgetCache);
        static::deleted($forgetCache);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_ghost' => 'boolean',
            'search_count' => 'integer',
            'last_searched_at' => 'datetime',
        ];
    }
}
