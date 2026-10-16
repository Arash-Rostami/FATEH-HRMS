<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasActive
{
    public function initializeHasActive(): void
    {
        $this->mergeCasts(['active' => 'boolean']);
        $this->mergeFillable(['active']);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('active', false);
    }
}
