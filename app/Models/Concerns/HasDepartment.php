<?php

namespace App\Models\Concerns;

use App\Models\Department;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasDepartment
{
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'code');
    }
}