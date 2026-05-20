<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;


class ReservationPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'resource_type',
        'key',
        'value',
    ];

    public function scopeGroupedByResourceType(Builder $query): Builder
    {
        $sub = self::selectRaw('
            MIN(id) as id,
            resource_type,
            COUNT(*) as rules_count,
            MAX(updated_at) as last_updated
        ')->groupBy('resource_type');

        return $query->fromSub($sub, 'reservation_policies');
    }

    protected static function booted(): void
    {
        $flush = fn(self $policy) => Cache::forget("reservation_policies_{$policy->resource_type}");

        static::saved($flush);
        static::deleted($flush);
    }

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }
}
