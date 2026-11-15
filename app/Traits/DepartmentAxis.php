<?php

namespace App\Traits;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

trait DepartmentAxis
{
    private array $topDepartmentsCache = [];

    private function topDepartments(int $limit = 10, bool $everyone = false): array
    {
        $cacheKey = $limit . ($everyone ? ':all' : '');

        if (isset($this->topDepartmentsCache[$cacheKey])) {
            return $this->topDepartmentsCache[$cacheKey];
        }

        $codes = DB::table('profiles')
            ->join('users', 'profiles.user_id', '=', 'users.id')
            ->when(
                $everyone,
                fn($query) => $query->where('users.type', '!=', UserType::Guest->value),
                fn($query) => $query->tap(User::currentStaff(...))
            )
            ->select('department_id', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('department_id')
            ->groupBy('department_id')
            ->orderByDesc('cnt')
            ->orderBy('department_id')
            ->limit($limit)
            ->pluck('department_id')
            ->all();

        if (empty($codes)) {
            return $this->topDepartmentsCache[$cacheKey] = [[], [], []];
        }

        $names = DB::table('departments')
            ->whereIn('code', $codes)
            ->get()
            ->mapWithKeys(fn($d) => [$d->code => ($d->description ?: ($d->name ?: $d->code))]);

        $labels = array_map(fn($c) => $names[$c] ?? $c, $codes);

        return $this->topDepartmentsCache[$cacheKey] = [$codes, $labels, array_flip($codes)];
    }
}