<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'description'];
    protected $primaryKey = 'code';
    protected $keyType = 'string';


    public function authorities(): HasMany
    {
        return $this->hasMany(Authority::class, 'department_id', 'code');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(FAQ::class, 'department_id', 'code');
    }

    public static function getCachedOptions(): Collection
    {
        return once(fn() => Cache::remember('department_options',
            now()->addYear(),
            fn() => self::orderBy('name')->pluck('description', 'code'))
        );
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'department_id', 'code');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class, 'department_id', 'code');
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class, 'department_id', 'code');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'department_id', 'code');
    }

    public function user(): HasOneThrough
    {
        return $this->hasOneThrough(User::class, Profile::class, 'department_id', 'id', 'code');
    }

    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, Profile::class, 'department_id', 'id', 'code', 'user_id');
    }

    protected static function booted(): void
    {
        $forgetCache = fn() => Cache::forget('department_options');

        static::saved($forgetCache);
        static::deleted($forgetCache);
    }
}
