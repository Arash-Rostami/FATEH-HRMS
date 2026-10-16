<?php

namespace App\Models\Concerns;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;

trait HasAudience
{
    use HasActive;

    public function initializeHasAudience(): void
    {
        $this->mergeCasts(['departments' => 'array']);
        $this->mergeFillable(['departments']);
    }

    public function scopeVisibleTo(Builder $query, ?string $departmentCode): Builder
    {
        $withDepartmentId = static::audienceIncludesDepartmentId();

        return $query->where(function ($q) use ($departmentCode, $withDepartmentId) {
            if ($withDepartmentId && filled($departmentCode)) {
                $q->where('department_id', $departmentCode);
            }

            if (filled($departmentCode)) {
                $q->orWhereJsonContains('departments', $departmentCode);
            }

            $q->orWhere(function ($p) use ($withDepartmentId) {
                if ($withDepartmentId) {
                    $p->where(fn($x) => $x->whereNull('department_id')->orWhere('department_id', ''));
                }

                $p->where(fn($y) => $y->whereNull('departments')->orWhereRaw('JSON_LENGTH(departments) = 0'));
            });
        });
    }

    public function scopeForViewer(Builder $query, ?User $user = null): Builder
    {
        return $query->active()->visibleTo(($user ?? auth()->user())?->profile?->department_id);
    }

    public function audienceCodes(): array
    {
        $codes = $this->departments ?? [];

        if (static::audienceIncludesDepartmentId() && filled($this->department_id)) {
            $codes[] = $this->department_id;
        }

        return array_values(array_unique($codes));
    }

    public function isVisibleTo(?string $departmentCode): bool
    {
        if (!$this->active) {
            return false;
        }

        $codes = $this->audienceCodes();

        return empty($codes) || (filled($departmentCode) && in_array($departmentCode, $codes, true));
    }

    public function audienceUsers(): Collection
    {
        $codes = $this->audienceCodes();

        return User::active()
            ->when($codes, fn($q) => $q->whereHas('profile', fn($p) => $p->whereIn('department_id', $codes)))
            ->get();
    }

    protected static function audienceIncludesDepartmentId(): bool
    {
        return defined('static::AUDIENCE_INCLUDES_DEPARTMENT_ID') && static::AUDIENCE_INCLUDES_DEPARTMENT_ID;
    }

    protected function isPublic(): Attribute
    {
        return Attribute::make(
            get: fn(): bool => empty($this->audienceCodes()),
        );
    }

    protected function audienceDepartments(): Attribute
    {
        return Attribute::make(
            get: function () {
                $codes = $this->audienceCodes();

                if (empty($codes)) {
                    return collect();
                }

                return Department::getCachedModels()
                    ->filter(fn($model, $code) => in_array($code, $codes, true))
                    ->values();
            }
        )->shouldCache();
    }
}
