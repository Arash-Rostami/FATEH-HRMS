<?php

namespace App\Filament\Widgets\Concerns;

use App\Filament\Resources\UserResource\Enums\UserType;
use Illuminate\Support\Facades\DB;

trait DepartmentAxis
{
    private array $topDepartmentsCache = [];

    private function topDepartments(int $limit = 10): array
    {
        if (isset($this->topDepartmentsCache[$limit])) {
            return $this->topDepartmentsCache[$limit];
        }

        $codes = DB::table('profiles')
            ->join('users', 'profiles.user_id', '=', 'users.id')
            ->where('users.type', '!=', UserType::Guest->value)
            ->select('department_id', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('department_id')
            ->groupBy('department_id')
            ->orderByDesc('cnt')
            ->orderBy('department_id')
            ->limit($limit)
            ->pluck('department_id')
            ->all();

        if (empty($codes)) {
            return $this->topDepartmentsCache[$limit] = [[], [], []];
        }

        $names = DB::table('departments')
            ->whereIn('code', $codes)
            ->get()
            ->mapWithKeys(fn($d) => [$d->code => ($d->description ?: ($d->name ?: $d->code))]);

        $labels = array_map(fn($c) => $names[$c] ?? $c, $codes);

        return $this->topDepartmentsCache[$limit] = [$codes, $labels, array_flip($codes)];
    }
}