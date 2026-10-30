<?php

namespace App\Models;

use App\Models\Concerns\HasAudience;
use App\Models\Concerns\HasDepartment;
use App\Models\Concerns\HasPublicAssetUrl;
use App\Models\Concerns\HasUser;
use App\Models\Concerns\Hooks\HasReportHooks;
use App\Services\ContentSanitizerService;
use App\Traits\CleansAttachedFiles;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasNotifyEvents;

class Report extends Model
{
    use HasFactory,
        HasPublicAssetUrl,
        CleansAttachedFiles,
        HasAudience,
        HasNotifyEvents,
        HasDepartment,
        HasUser,
        HasReportHooks;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'cover_image',
        'department_id',
        'file_path',
        'pinned',
        'report_date',
        'expires_at',
    ];

    public function scopeByDepartment($query, string $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePinned($query)
    {
        return $query->where('pinned', true);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public static function notifyFields(): array
    {
        return ['user_id' => 'user', 'department_id' => 'department'];
    }

    protected function casts(): array
    {
        return [
            'pinned' => 'boolean',
            'report_date' => 'date',
            'expires_at' => 'date',
        ];
    }

    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value): ?string => $value,
            set: fn(?string $value): ?string => ContentSanitizerService::clean($value),
        );
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
