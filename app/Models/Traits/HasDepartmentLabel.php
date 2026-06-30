<?php

namespace App\Models\Traits;

trait HasDepartmentLabel
{
    public function displayLabel(): string
    {
        return $this->description ?: $this->name ?: $this->code;
    }

    public function tooltipLabel(): string
    {
        return $this->name ?: $this->code ?: $this->description;
    }
}