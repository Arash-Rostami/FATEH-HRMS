<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class PersolTeamService
{
    public function applyRules(Builder $query, User $viewer): void
    {
        if (!$viewer->profile) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->where('status', 'active');


        $viewerProfile = $viewer->profile;
        $viewerDepartment = $viewerProfile->department_id;
        $isManager = $viewer->isDeptHead();

        $rulesApplied = match ($viewerDepartment) {
            'MA' => $this->applyMARules($query, $viewerProfile),
            'MK' => $this->applyMKRules($query, $viewerDepartment, $isManager),
            'HC', 'HR' => $this->applyHCRules($query, $viewerDepartment, $isManager),
            'CP' => $this->applyCPRules($query, $viewer),
            default => $this->applyDefaultRules($query, $viewerDepartment, $isManager),
        };

        if (!$rulesApplied) {
            $query->whereRaw('1 = 0');
        }
    }

    private function applyMARules(Builder $query, $viewerProfile): bool
    {
        if ($viewerProfile->gender == 'female') {
            $query->where(function (Builder $q) {
                $q->whereHas('profile', fn(Builder $pq) => $pq->where('position', 'manager')->where('department_id', '!=', 'MA'))
                    ->orWhereHas('profile', fn(Builder $pq) => $pq->where('department_id', 'CX'));
            });
        } elseif ($viewerProfile->gender == 'male') {
            $query->where(function (Builder $q) {
                $q->whereHas('profile', fn(Builder $pq) => $pq->where('position', 'manager')->whereNotIn('department_id', ['CP', 'WP', 'CH', 'MA']))
                    ->orWhereHas('profile', fn(Builder $pq) => $pq->where('department_id', 'SO')->where('position', 'senior'));
            });
        }

        return true;
    }

    private function applyMKRules(Builder $query, ?string $viewerDepartment, bool $isManager): bool
    {
        if ($isManager) {
            $query->where(function (Builder $q) use ($viewerDepartment) {
                $q->whereHas('profile', fn(Builder $pq) => $pq->where('department_id', $viewerDepartment))
                    ->orWhereHas('profile', fn(Builder $pq) => $pq->where('position', 'manager')->whereIn('department_id', ['CP', 'WP', 'CH']))
                    ->orWhereHas('profile', fn(Builder $pq) => $pq->where('position', 'expert')->whereIn('department_id', ['CH', 'SO']));
            });
            return true;
        }

        return false;
    }

    private function applyHCRules(Builder $query, ?string $viewerDepartment, bool $isManager): bool
    {
        if ($isManager) {
            $query->whereHas('profile', fn(Builder $pq) => $pq->whereIn('department_id', [$viewerDepartment, 'AS', 'HC', 'HR']));
            return true;
        }

        return false;
    }

    private function applyCPRules(Builder $query, User $viewer): bool
    {
        $name = (string) $viewer->name;
        $lower = mb_strtolower($name);

        if (str_contains($lower, 'rashidbeygi') || str_contains($name, 'رشیدبیگی')) {
            $query->where(fn(Builder $q) => $q->where('name', 'like', '%adami%')->orWhere('name', 'like', '%آدمی%'));
        } elseif (str_contains($lower, 'shirzadeh') || str_contains($name, 'شیرزاده')) {
            $query->where(fn(Builder $q) => $q->where('name', 'like', '%nafar%')->orWhere('name', 'like', '%نفر%'));
        } else {
            $query->whereRaw('1 = 0');
        }

        return true;
    }

    private function applyDefaultRules(Builder $query, ?string $viewerDepartment, bool $isManager): bool
    {
        if ($isManager) {
            $query->whereHas('profile', fn(Builder $q) => $q->where('department_id', $viewerDepartment));
            return true;
        }

        return false;
    }
}
