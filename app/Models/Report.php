<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'department_id',
        'file_path',
        'active',
        'cover_image',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'code');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('active', false);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByDepartment($query, string $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function getThumbnailAttribute()
    {
        if ($this->cover_image && Storage::exists($this->cover_image)) {
            return Storage::url($this->cover_image);
        }

        // Determine file type from extension
        if (!$this->file_path) {
            return asset('assets/images/default-report.png');
        }

        $extension = strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION));

        // Return a default placeholder based on file type
        return match ($extension) {
            'pdf' => asset('assets/images/pdf-placeholder.png'),
            'docx', 'doc' => asset('assets/images/doc-placeholder.png'),
            default => asset('assets/images/default-report.png'),
        };
    }

    public function getFileTypeAttribute()
    {
         if (!$this->file_path) {
             return 'unknown';
         }
         return strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION));
    }
}
