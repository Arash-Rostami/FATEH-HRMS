<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    protected $fillable = [
        'code',
        'name',
        'description',
        'ticket_options',
        'units',
        'sections',
    ];
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

    public static function getCachedModels(): Collection
    {
        return once(fn() => Cache::remember('department_models',
            now()->addYear(),
            fn() => self::all()->keyBy('code'))
        );
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class, 'department_id', 'code');
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class, 'department_id', 'code');
    }

    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class, 'department_id', 'code');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'department_id', 'code');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'department_id', 'code');
    }

    public function scopeExcludingEmptyTicketOptions(Builder $query): Builder
    {
        return $query->whereNotNull('ticket_options')
            ->whereJsonLength('ticket_options', '>', 0);
    }

    public static function getCachedOptionsExcludingEmptyTickets(): Collection
    {
        return once(fn() => Cache::remember(
            'department_options_with_tickets',
            now()->addYear(),
            fn() => self::excludingEmptyTicketOptions()->orderBy('name')->pluck('description', 'code')
        ));
    }

    public function sectionsOptions(): array
    {
        return array_combine($this->sections ?? [], $this->sections ?? []);
    }

    public function unitsOptions(): array
    {
        return array_combine($this->units ?? [], $this->units ?? []);
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
        $forgetCache = function ($model) {
            Cache::forget('department_options');
            Cache::forget('department_options_with_tickets');
            Cache::forget('department_models');
            if ($model && $model->code) {
                Cache::forget("department_ticket_options_{$model->code}");
            }
        };

        static::saved($forgetCache);
        static::deleted($forgetCache);
    }

    protected function casts(): array
    {
        return [
            'ticket_options' => 'array',
        ];
    }

    protected function sections(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => $value ? array_values(json_decode($value, true)) : [],
            set: fn(?array $value) => ['sections' => json_encode(array_values(array_unique(array_filter($value ?? []))))],
        );
    }

    protected function units(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => $value ? array_values(json_decode($value, true)) : [],
            set: fn(?array $value) => ['units' => json_encode(array_values(array_unique(array_filter($value ?? []))))],
        );
    }
}
