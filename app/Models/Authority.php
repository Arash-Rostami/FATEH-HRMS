<?php

namespace App\Models;

use App\Models\Concerns\HasModelCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class Authority extends Model
{
    use HasModelCache,
        HasFactory;

    protected $fillable = [
        'department_id',
        'user_id',
        'sub_duty',
        'details',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'code');
    }

    public function scopeSearch($query, ?string $search)
    {
        if (blank($search)) {
            return $query;
        }

        $term = str_replace(['ك', 'ي'], ['ک', 'ی'], trim($search));
        $keys = [
            'duty',
            'execution_procedure',
            'repeat_frequency',
            'impact_score',
            'proposed_delegation',
            'approved_delegation',
            'co_delegate',
        ];

        $jsonSql = implode(' OR ', array_map(
            fn($k) => "CONVERT(JSON_UNQUOTE(JSON_EXTRACT(details, '$.{$k}')) USING utf8mb4) COLLATE utf8mb4_persian_ci LIKE ?",
            $keys
        ));

        return $query->where(fn($q) => $q
            ->whereRaw($jsonSql, array_fill(0, count($keys), "%{$term}%"))
            ->orWhereHas('department', fn($d) => $d->whereAny(['code', 'name', 'description'], 'like', "%{$term}%"))
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function cachedCount(): int
    {
        return static::cached('count', fn () => static::query()->count());
    }

    public static function cachedDepartments(): Collection
    {
        return static::cached('departments', function () {
            $codes = static::query()->whereNotNull('department_id')->distinct()->pluck('department_id');
            $models = Department::getCachedModels();

            return Department::getCachedOptions()
                ->keys()
                ->filter(fn($code) => $codes->contains($code))
                ->map(fn($code) => $models->get($code))
                ->values();
        });
    }

    protected function casts(): array
    {
        return [
            'sub_duty' => 'boolean',
            'details' => 'array',
        ];
    }
}
