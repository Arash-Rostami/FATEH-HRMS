<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EnergyTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mind_score',
        'emotion_score',
        'physique_score',
        'soul_score',
        'overall_score',
        'answers',
        'month_index',
        'completed_at',
    ];

    public static function getAverageScoresByDepartment(bool $lastMonth = false): Collection
    {
        return static::query()
            ->when($lastMonth, fn($q) => $q->where('completed_at', '>=', now()->subDays(30)))
            ->join('users', 'energy_tests.user_id', '=', 'users.id')
            ->leftJoin('profiles', 'users.id', '=', 'profiles.user_id')
            ->leftJoin('departments', 'profiles.department_id', '=', 'departments.code')
            ->select(
                DB::raw('COALESCE(departments.name, "Unknown") as department'),
                DB::raw('ROUND(AVG(energy_tests.overall_score), 2) as average_score')
            )
            ->groupBy('departments.name', 'profiles.department_id')
            ->orderBy('department')
            ->pluck('average_score', 'department');
    }

    public static function getDistribution(array $ranges, bool $lastMonth = false): Builder
    {
        $selects = [];
        foreach ($ranges as $key => $range) {
            $selects[] = "SUM(CASE WHEN overall_score BETWEEN {$range['min']} AND {$range['max']} THEN 1 ELSE 0 END) as $key";
        }

        return static::query()
            ->when($lastMonth, fn($q) => $q->where('completed_at', '>=', now()->subDays(30)))
            ->selectRaw(implode(', ', $selects));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'completed_at' => 'datetime',
        ];
    }
}
