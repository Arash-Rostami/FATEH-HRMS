<?php

namespace App\Models;

use App\Models\Concerns\HasDepartmentLabel;
use App\Services\Cache\ModelCacheVersion;
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
use RuntimeException;

class Department extends Model
{
    use HasFactory, HasDepartmentLabel;

    protected $fillable = [
        'code',
        'name',
        'description',
        'ticket_options',
        'units',
        'sections',
        'level',
        'subordinate_to',
    ];
    protected $primaryKey = 'code';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function booted(): void
    {
        static::saving(function (self $department) {
            if ($department->subordinate_to !== null && (int) $department->level === 0) {
                $department->level = 1;
            }

            if (static::wouldCreateCycle($department->code, $department->subordinate_to)) {
                throw new RuntimeException(__('resources/department/strings.errors.cyclic_hierarchy'));
            }
        });
    }

    public static function wouldCreateCycle(?string $code, ?string $subordinateTo): bool
    {
        if ($code === null || $subordinateTo === null) {
            return false;
        }

        if ($subordinateTo === $code) {
            return true;
        }

        $models = static::getCachedModels();
        $visited = [];
        $current = $subordinateTo;

        while ($current !== null) {
            if ($current === $code || isset($visited[$current])) {
                return true;
            }

            $visited[$current] = true;
            $current = $models->get($current)?->subordinate_to;
        }

        return false;
    }

    public static function anyHasCustomTicketOptions(): bool
    {
        return Cache::remember(
            ModelCacheVersion::key(self::class, 'department_any_has_ticket_options'),
            now()->addYear(),
            fn() => self::excludingEmptyTicketOptions()->exists()
        );
    }

    public function authorities(): HasMany
    {
        return $this->hasMany(Authority::class, 'department_id', 'code');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(FAQ::class, 'department_id', 'code');
    }

    public static function getCachedModels(): Collection
    {
        return Cache::remember(ModelCacheVersion::key(self::class, 'department_models'),
            now()->addYear(),
            fn() => self::all()->keyBy('code')
        );
    }

    public static function getCachedOptions(): Collection
    {
        return Cache::remember(ModelCacheVersion::key(self::class, 'department_options_v2'),
            now()->addYear(),
            fn() => self::orderBy('name')->get()->mapWithKeys(fn($d) => [$d->code => $d->displayLabel()])
        );
    }

    public static function getCachedOptionsExcludingEmptyTickets(): Collection
    {
        return Cache::remember(
            ModelCacheVersion::key(self::class, 'department_options_with_tickets_v2'),
            now()->addYear(),
            fn() => self::excludingEmptyTicketOptions()->orderBy('name')->get()->mapWithKeys(fn($d) => [$d->code => $d->displayLabel()])
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

    public function scopeIncludingEmptyTicketOptions(Builder $query): Builder
    {
        return $query->where(fn(Builder $q) => $q
            ->whereNull('ticket_options')
            ->orWhereJsonLength('ticket_options', 0));
    }

    public function sectionsOptions(): array
    {
        $sections = $this->sections ?? [];
        return array_combine($sections, $sections);
    }

    public function unitsOptions(): array
    {
        $units = $this->units ?? [];
        return array_combine($units, $units);
    }

    public function user(): HasOneThrough
    {
        return $this->hasOneThrough(User::class, Profile::class, 'department_id', 'id', 'code');
    }

    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, Profile::class, 'department_id', 'id', 'code', 'user_id');
    }

    protected function casts(): array
    {
        return [
            'ticket_options' => 'array',
            'level' => 'integer',
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
