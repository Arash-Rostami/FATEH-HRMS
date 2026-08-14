<?php

namespace App\Models;

use App\Models\Traits\HasPublicAssetUrl;
use App\Services\ContentSanitizerService;
use App\Traits\CleansAttachedFiles;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory, HasPublicAssetUrl, CleansAttachedFiles;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'cover_image',
        'department_id',
        'file_path',
        'active'
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'code');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeByDepartment($query, string $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeInactive($query)
    {
        return $query->where('active', false);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value): ?string => $value,
            set: fn(?string $value): ?string => ContentSanitizerService::clean($value),
        );
    }

    protected static function booted(): void
    {
        static::deleting(function (self $report) {
            static::deleteStoredFiles($report->cover_image);
            static::deleteStoredFiles($report->file_path);
        });
    }

    protected function fileType(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->file_path ? strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION)) : 'unknown'
        );
    }

    protected function thumbnail(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->cover_image) {
                    return self::resolvePublicAssetUrl($this->cover_image);
                }

                return match ($this->file_type) {
                    'pdf' => asset('build/assets/img/pdf.png'),
                    'docx', 'doc' => asset('build/assets/img/doc.png'),
                    default => asset('build/assets/img/report.png'),
                };
            }
        )->shouldCache();
    }
}
