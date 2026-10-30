<?php

namespace App\Models;

use App\Models\Concerns\HasAudience;
use App\Models\Concerns\HasDepartment;
use App\Models\Concerns\HasPublicAssetUrl;
use App\Models\Concerns\Hooks\HasPhotoHooks;
use App\Traits\CleansAttachedFiles;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasNotifyEvents;

class Photo extends Model
{
    use HasFactory,
        HasPublicAssetUrl,
        CleansAttachedFiles,
        HasAudience,
        HasNotifyEvents,
        HasDepartment,
        HasPhotoHooks;

    public const AUDIENCE_INCLUDES_DEPARTMENT_ID = true;

    protected $fillable = [
        'path',
        'title',
        'department_id',
        'description',
        'event_date',
    ];

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
        )->shouldCache();
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

    public static function notifyFields(): array
    {
        return ['department_id' => 'department'];
    }

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'path' => 'array',
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
}
