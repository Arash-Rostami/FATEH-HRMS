<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'resource_id',
        'start_time',
        'end_time',
        'is_full_day',
        'status',
        'cancelled_by_id',
        'cancelled_at',
        'cancel_reason',
        'parent_id',
    ];

    public function cancelledBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_id');
    }

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'parent_id');
    }

    public function resource(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function scopeCancelled(Builder $q): Builder
    {
        return $q->whereIn('status', ['cancelled_user', 'cancelled_admin']);
    }

    public function scopeForUser(Builder $q, int $userId): Builder
    {
        return $q->where('user_id', $userId);
    }

    public function scopePrevious(Builder $q): Builder
    {
        return $q->where('status', 'active')->where('start_time', '<', now());
    }

    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->where('status', 'active')->where('start_time', '>=', now());
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'cancelled_at' => 'datetime',
            'is_full_day' => 'boolean',
        ];
    }

    protected function displayTime(): Attribute
    {
        return Attribute::make(
            get: function () {
                $bookingDate = convertToPersian(toJalali($this->start_time, 'Y/m/d'));

                if ($this->is_full_day) {
                    return $bookingDate . ' (تمام روز)';
                }

                $startTime = convertToPersian(toJalali($this->start_time, 'H:i'));
                $endTime = convertToPersian(toJalali($this->end_time, 'H:i'));

                return $bookingDate . ' • ' . $startTime . ' تا ' . $endTime;
            }
        );
    }

    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(Reservation::class, 'parent_id'); }
}
