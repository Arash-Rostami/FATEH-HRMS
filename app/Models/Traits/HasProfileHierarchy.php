<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait HasProfileHierarchy
{
    public const RANKS = [
        'chairman'   => 1,
        'ceo'        => 1,
        'c-manager'  => 2,
        'manager'    => 3,
        'supervisor' => 4,
        'senior'     => 5,
        'expert'     => 6,
        'employee'   => 7,
    ];


    public function isInDepartment(string $code): bool
    {
        return $this->profile?->department?->code === $code;
    }

    public function rank(): int
    {
        return static::RANKS[$this->profile?->position] ?? PHP_INT_MAX;
    }

    public function isCeo(): bool
    {
        return $this->isInDepartment('MA');
    }

    public function isTopExecutive(): bool
    {
        return in_array($this->profile?->position, ['chairman', 'ceo'], true);
    }

    public function isSeniorDecisionMaker(): bool
    {
        if ($this->isTopExecutive()) return true;

        $anyTopExecutiveExists = static::active()
            ->whereHas('profile', fn(Builder $q) => $q->whereIn('position', ['chairman', 'ceo']))
            ->exists();

        if ($anyTopExecutiveExists) return false;

        $anyActiveMaUserExists = static::active()
            ->whereHas('profile', fn(Builder $q) => $q->where('department_id', 'MA'))
            ->exists();

        return $this->isInDepartment($anyActiveMaUserExists ? 'MA' : 'MG');
    }

    public function isDeptHead(): bool
    {
        $deptCode = $this->profile?->department_id;
        $myRank = $this->rank();


        if (!$deptCode || $myRank === PHP_INT_MAX) return false;

        // Find all positions that are strictly HIGHER rank (lower number) than this user
        $higherPositions = array_keys(array_filter(
            static::RANKS,
            fn($rank) => $rank < $myRank
        ));

        // If no higher positions exist in the system, user is automatically the head
        if (empty($higherPositions)) return true;


        // Check if ANYONE else in this department holds one of those higher positions
        $hasSuperior = static::active()
            ->whereKeyNot($this->id)
            ->whereHas('profile', fn(Builder $q) => $q
                ->where('department_id', $deptCode)
                ->whereIn('position', $higherPositions)
            )
            ->exists();

        // If no superior exists, the current user is the de facto department head
        return !$hasSuperior;
    }

    public static function highestRankingInDepartments(array $departmentCodes): Collection
    {
        if (empty($departmentCodes)) {
            return new Collection();
        }

        return static::active()->with('profile')
            ->whereHas('profile', fn(Builder $q) => $q
                ->whereIn('department_id', $departmentCodes)
                ->whereIn('position', array_keys(static::RANKS))
            )
            ->get()
            ->groupBy(fn(self $u) => $u->profile->department_id)
            ->map(fn($users) => $users->sortBy(fn(self $u) => $u->rank())->first());
    }

    public static function highestRankingInDepartment(string $departmentCode): ?self
    {
        return static::highestRankingInDepartments([$departmentCode])->get($departmentCode);
    }
}
