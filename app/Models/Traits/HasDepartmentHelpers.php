<?php

namespace App\Models\Traits;

use App\Models\Department;

trait HasDepartmentHelpers
{
    protected static array $departmentsCache = [];

    public function getDepartmentDescriptions()
    {
        return $this->getDepartmentsData('description', 'توضیحات برای همه واحد ها');
    }

    public function getDepartmentNames()
    {
        return $this->getDepartmentsData('name', 'همه واحد ها');
    }

    private function getDepartmentsData(string $attribute, string $message)
    {
        if (collect($this->owners)->contains('ALL')) {
            return $message;
        }

        if (empty(static::$departmentsCache)) {
            static::$departmentsCache = Department::all()->keyBy('code')->toArray();
        }

        return collect($this->owners)
            ->map(fn($ownerCode) => static::$departmentsCache[$ownerCode][$attribute] ?? '')
            ->implode(' ┆ ');
    }
}
