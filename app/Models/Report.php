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
        'departments',
        'file_path',
        'active',
        'pinned',
        'report_date',
        'expires_at',
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

    public function scopePinned($query)
    {
        return $query->where('pinned', true);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function scopeVisibleTo($query, ?string $departmentCode)
    {
        return $query->where(function ($q) use ($departmentCode) {
            $q->where(fn($x) => $x->whereNull('departments')->orWhereRaw('JSON_LENGTH(departments) = 0'));
            if (filled($departmentCode)) {
                $q->orWhereJsonContains('departments', $departmentCode);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'pinned' => 'boolean',
            'departments' => 'array',
            'report_date' => 'date',
            'expires_at' => 'date',
        ];
    }

    protected function isPublic(): Attribute
    {
        return Attribute::make(
            get: fn(): bool => empty($this->departments),
        );
    }

    protected function audienceDepartments(): Attribute
    {
        return Attribute::make(
            get: function () {
                $codes = $this->departments ?? [];
                if (empty($codes)) {
                    return collect();
                }

                return Department::getCachedModels()
                    ->filter(fn($model, $code) => in_array($code, $codes, true))
                    ->values();
            }
        );
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
