<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Morilog\Jalali\Jalalian;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'deadline',
        'user_id',
        'assigned_to',
    ];

    protected $casts = [
        'deadline' => 'datetime',
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

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getAssigneeNameAttribute()
    {
        return $this->assignee?->full_name ?? null;
    }

    public function getCanChangeStatusAttribute()
    {
        $userId = auth()->id();
        return $this->assigned_to === $userId || ($this->user_id === $userId && !$this->assigned_to);
    }

    public function getCanDeleteAttribute()
    {
        return $this->user_id === auth()->id();
    }

    public function getCreatedFormattedAttribute()
    {
        return Jalalian::fromCarbon($this->created_at)->format('Y/m/d');
    }

    public function getDeadlineFormattedAttribute()
    {
        return $this->deadline ? Jalalian::fromCarbon($this->deadline)->format('Y/m/d') : null;
    }

    public function getDelegatorNameAttribute()
    {
        return $this->creator?->full_name ?? null;
    }

    public static function getInProgressCount($userId)
    {
        return self::where('status', 'in-progress')
            ->where(function ($query) use ($userId) {
                $query->where('assigned_to', $userId)
                    ->orWhere(function ($subQuery) use ($userId) {
                        $subQuery->where('user_id', $userId)->whereNull('assigned_to');
                    });
            })
            ->count();
    }

    public function getIsDelegatorAttribute()
    {
        $userId = auth()->id();
        return $this->user_id === $userId && $this->assigned_to && $this->assigned_to !== $userId;
    }

    public static function getTodoCount($userId)
    {
        return self::where('status', 'todo')
            ->where(function ($query) use ($userId) {
                $query->where('assigned_to', $userId)
                    ->orWhere(function ($subQuery) use ($userId) {
                        $subQuery->where('user_id', $userId)->whereNull('assigned_to');
                    });
            })
            ->count();
    }
}
