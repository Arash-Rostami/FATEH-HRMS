<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Photo extends Model
{
    protected $fillable = [
        'path',
        'title',
        'department_id',
        'description',
        'event_date',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'code');
    }

    public function scopeByDepartment($query, string $departmentCode)
    {
        return $query->where('department_id', $departmentCode);
    }

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'path' => 'array',
        ];
    }
}
