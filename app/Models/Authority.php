<?php

namespace App\Models;

use App\Filament\Resources\AuthorityResource\Enums\DelegationLevel;
use App\Filament\Resources\AuthorityResource\Enums\ExecutionProcedure;
use App\Filament\Resources\AuthorityResource\Enums\ImpactScore;
use App\Filament\Resources\AuthorityResource\Enums\RepeatFrequency;
use App\Models\Concerns\HasDepartment;
use App\Models\Concerns\HasUser;
use App\Models\Concerns\HasModelCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use App\Models\Concerns\HasNotifyEvents;

class Authority extends Model
{
    use HasDepartment,
        HasUser,
        HasModelCache,
        HasFactory,
        HasNotifyEvents;

    protected $fillable = [
        'department_id',
        'user_id',
        'sub_duty',
        'details',
    ];

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

    public static function notifyFields(): array
    {
        return [
            'department_id' => 'department',
            'user_id' => 'user',
            'details.duty' => 'text',
            'details.execution_procedure' => ExecutionProcedure::class,
            'details.repeat_frequency' => RepeatFrequency::class,
            'details.impact_score' => ImpactScore::class,
            'details.proposed_delegation' => DelegationLevel::class,
            'details.approved_delegation' => DelegationLevel::class,
            'details.co_delegate' => 'text',
        ];
    }

    protected function casts(): array
    {
        return [
            'sub_duty' => 'boolean',
            'details' => 'array',
        ];
    }
}
