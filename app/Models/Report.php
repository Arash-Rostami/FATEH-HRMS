<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'cover_image',
        'department_id',
        'file_path',
        'active'
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'code');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function fileType(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->file_path ? strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION)) : 'unknown'
        );
    }

    protected function thumbnail(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->cover_image && Storage::exists($this->cover_image)) {
                    return Storage::url($this->cover_image);
                }

                $extension = $this->file_path ? strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION)) : null;

                return match ($extension) {
                    'pdf' => asset('assets/images/pdf.png'),
                    'docx', 'doc' => asset('assets/images/doc.png'),
                    default => asset('assets/images/report.png'),
                };
            }
        );
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
}
