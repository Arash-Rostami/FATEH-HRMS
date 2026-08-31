<?php

namespace App\Models;

use App\Models\Concerns\HasPublicAssetUrl;
use App\Traits\CleansAttachedFiles;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Photo extends Model
{
    use HasFactory, HasPublicAssetUrl, CleansAttachedFiles;

    protected $fillable = [
        'path',
        'title',
        'department_id',
        'departments',
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

    protected function allDepartmentModels(): Attribute
    {
        return Attribute::make(
            get: function () {
                $codes = $this->all_departments;
                if (empty($codes)) {
                    return collect();
                }

                return Department::getCachedModels()->filter(fn($model, $code) => in_array($code, $codes, true))->values();
            }
        );
    }

    protected function allDepartments(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $deps = $this->departments ?? [];
                if ($this->department_id) {
                    array_unshift($deps, $this->department_id);
                }

                return array_unique($deps);
            }
        );
    }

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'path' => 'array',
            'departments' => 'array',
        ];
    }

    protected function imageUrls(): Attribute
    {
        return Attribute::make(
            get: fn() => array_map(
                fn($p) => static::resolvePublicAssetUrl($p),
                array_values(array_filter($this->path ?? [], fn($p) => !empty($p))),
            ),
        )->shouldCache();
    }

    protected static function booted(): void
    {
        static::deleting(fn(self $photo) => static::deleteStoredFiles($photo->path));
    }
}
