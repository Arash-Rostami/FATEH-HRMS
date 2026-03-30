<?php

namespace App\Models;

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

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by_id');
    }

    public function parent()
    {
        return $this->belongsTo(Reservation::class, 'parent_id');
    }

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    public function user()
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
}
