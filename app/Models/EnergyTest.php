<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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

    public static function getAverageScoresForUser(int $userId): array
    {
        return Cache::remember(
            "energy_tests.user_averages.{$userId}",
            now()->addHour(),
            function () use ($userId): array {
                $row = static::query()
                    ->where('user_id', $userId)
                    ->selectRaw('
                    COUNT(*) AS records_count,
                    COALESCE(AVG(mind_score),0) AS mind_avg,
                    COALESCE(AVG(emotion_score),0) AS emotion_avg,
                    COALESCE(AVG(physique_score), 0) AS physique_avg,
                    COALESCE(AVG(soul_score),     0) AS soul_avg,
                    COALESCE(AVG(overall_score),  0) AS overall_avg
                ')
                    ->first();

                return [
                    'records_count' => (int)$row->records_count,
                    'overall' => round((float)$row->overall_avg, 1),
                    'categories' => [
                        'physique' => round((float)$row->physique_avg, 1),
                        'emotion' => round((float)$row->emotion_avg, 1),
                        'mind' => round((float)$row->mind_avg, 1),
                        'soul' => round((float)$row->soul_avg, 1),
                    ],
                ];
            }
        );
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


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    protected static function booted(): void
    {
        $forgetCache = fn(self $test) => Cache::forget(
            "energy_tests.user_averages.{$test->user_id}"
        );

        static::saved($forgetCache);
        static::deleted($forgetCache);
    }


    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'completed_at' => 'datetime',
        ];
    }
}
