<?php

namespace App\Models\Traits;

trait HasDepartmentHelpers
{
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
        if (collect($this->owners)->contains('ALL')) return $message;

        $departments = $this->departments()->pluck($attribute, 'code');

        return collect($this->owners)->map(fn($ownerCode) => $departments[$ownerCode] ?? '')->implode(', ');
    }
}
