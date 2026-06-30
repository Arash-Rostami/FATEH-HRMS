<?php

namespace App\Models\Traits;

use App\Models\Department;

trait HasDepartmentHelpers
{
    public function getDepartmentDisplayLabels(): string
    {
        return $this->getDepartmentsData(fn($d) => $d->displayLabel(), 'توضیحات برای همه واحد ها');
    }

    public function getDepartmentTooltipLabels(): string
    {
        return $this->getDepartmentsData(fn($d) => $d->tooltipLabel(), 'همه واحد ها');
    }

    private function getDepartmentsData(callable $label, string $allMessage): string
    {
        if (collect($this->owners)->contains('ALL')) {
            return $allMessage;
        }

        return collect($this->owners)
            ->map(fn($code) => Department::getCachedModels()->get($code))
            ->filter()
            ->map($label)
            ->implode(' ┆ ');
    }
}