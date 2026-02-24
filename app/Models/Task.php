<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Morilog\Jalali\Jalalian;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'status',
        'deadline',
        'user_id',
        'assigned_to',
    ];

    protected $appends = [
        'deadline_formatted',
        'created_formatted',
        'delegator_name',
        'assignee_name',
        'can_change_status',
        'is_delegator',
        'can_delete',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'datetime',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected function assigneeName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->assignee?->full_name
        );
    }

    protected function canChangeStatus(): Attribute
    {
        return Attribute::make(
            get: function () {
                $userId = auth()->id();
                return $this->assigned_to === $userId || ($this->user_id === $userId && !$this->assigned_to);
            }
        );
    }

    protected function canDelete(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->user_id === auth()->id()
        );
    }

    protected function createdFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => Jalalian::fromCarbon($this->created_at)->format('Y/m/d')
        );
    }

    protected function deadlineFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->deadline ? Jalalian::fromCarbon($this->deadline)->format('Y/m/d') : null
        );
    }

    protected function delegatorName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->creator?->full_name
        );
    }

    protected function isDelegator(): Attribute
    {
        return Attribute::make(
            get: function () {
                $userId = auth()->id();
                return $this->user_id === $userId && $this->assigned_to && $this->assigned_to !== $userId;
            }
        );
    }

    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where(fn (Builder $q) =>
        $q->where('assigned_to', $userId)
            ->orWhere(fn (Builder $sq) => $sq->where('user_id', $userId)->whereNull('assigned_to'))
        );
    }

    public function scopeStatus(Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    public static function getInProgressCount(int $userId): int
    {
        return self::query()->forUser($userId)->status('in-progress')->count();
    }

    public static function getTodoCount(int $userId): int
    {
        return self::query()->forUser($userId)->status('todo')->count();
    }
}
