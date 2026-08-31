<?php

namespace App\Models;

use App\Enums\CancelReason;
use App\Enums\ReservationError;
use App\Enums\ReservationStatus;
use App\Enums\ResourceType;
use App\Services\Reservation\EventSyncService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    protected $appends = [
        'display_time',
    ];

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by_id');
    }

    public function cancelReasonLabel(): ?string
    {
        return $this->cancel_reason
            ? (CancelReason::tryFrom($this->cancel_reason)?->getLabel() ?? $this->cancel_reason)
            : null;
    }

    public function occurrences()
    {
        return $this->hasMany(Reservation::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Reservation::class, 'parent_id');
    }

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    public function scopeCancelled(Builder $q): Builder
    {
        return $q->whereIn('status', [ReservationStatus::CancelledUser->value, ReservationStatus::CancelledAdmin->value]);
    }

    public function scopeForUser(Builder $q, int $userId): Builder
    {
        return $q->where('user_id', $userId);
    }

    public function scopePrevious(Builder $q): Builder
    {
        return $q->where('status', ReservationStatus::Active->value)
            ->where('end_time', '<', now());
    }

    public function scopeRoots(Builder $q): Builder
    {
        return $q->whereNull('parent_id');
    }

    public function scopeForToday(Builder $q): Builder
    {
        return $q->where('status', ReservationStatus::Active->value)
            ->where('start_time', '<=', now()->endOfDay())
            ->where('end_time', '>=', now()->startOfDay());
    }

    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->where('status', ReservationStatus::Active->value)
            ->where('end_time', '>=', now());
    }

    public function scopeReleased(Builder $q): Builder
    {
        return $q->where('status', ReservationStatus::Released->value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRange(): bool
    {
        return !$this->is_full_day
            && $this->start_time
            && $this->end_time
            && $this->start_time->diffInDays($this->end_time) >= 1;
    }

    protected static function booted(): void
    {
        static::saving(function (self $reservation) {
            if ($reservation->parent_id && $reservation->id && (int)$reservation->parent_id === (int)$reservation->id) {
                $reservation->parent_id = null;
                ReservationError::DataCorruption->throw();
            }
        });

        static::saved(function (self $reservation) {
            app(EventSyncService::class)->sync($reservation->loadMissing(['user', 'resource']));
        });

        static::deleted(function (self $reservation) {
            app(EventSyncService::class)->purge($reservation);
        });
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
                $start = $this->start_time;
                $end = $this->end_time;

                $date = convertToPersian(toJalali($start, 'Y/m/d'));

                if ($this->is_full_day) {
                    return $date . ' (تمام روز)';
                }

                $startTime = convertToPersian(toJalali($start, 'H:i'));

                if ($start->isSameDay($end)) {
                    return $date . ' • ' . $startTime . ' تا ' . convertToPersian(toJalali($end, 'H:i'));
                }

                $endDate = convertToPersian(toJalali($end, 'Y/m/d'));
                $endTime = convertToPersian(toJalali($end, 'H:i'));

                return $date . ' • ' . $startTime . ' تا ' . $endDate . ' ' . $endTime;
            }
        );
    }

    protected function resourceDropdownLabel(): Attribute
    {
        return Attribute::make(get: function () {
            $type = $this->resource?->type;
            $resolved = $type instanceof ResourceType ? $type : ResourceType::tryFrom($type);
            $typeLabel = $resolved ? "{$resolved->getEmoji()} {$resolved->getLabel()}" : $type;
            $resourceName = $this->resource ? "{$typeLabel} ⇄ {$this->resource->name} " : 'منبع نامشخص';
            $reserver = $this->user?->name ?? 'کاربر نامشخص';
            $date = $this->start_time ? toJalali($this->start_time, 'Y/m/d') : null;

            return implode(' ┆ ', array_filter([
                "{$this->id}#",
                $resourceName,
                " رزروکننده: {$reserver} 📆 {$date} ",
            ]));
        })->shouldCache();
    }
}
