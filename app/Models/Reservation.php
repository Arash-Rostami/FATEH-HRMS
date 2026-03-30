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
        'user_id', 'resource_id', 'start_time', 'end_time',
        'is_full_day', 'status', 'cancelled_by_id', 'cancelled_at',
        'cancel_reason', 'parent_id',
    ];

    protected function casts(): array
    {
        return [
            'start_time'   => 'datetime',
            'end_time'     => 'datetime',
            'cancelled_at' => 'datetime',
            'is_full_day'  => 'boolean',
        ];
    }

    public function cancelledBy() { return $this->belongsTo(User::class, 'cancelled_by_id'); }
    public function parent()      { return $this->belongsTo(Reservation::class, 'parent_id'); }
    public function resource()    { return $this->belongsTo(Resource::class); }
    public function user()        { return $this->belongsTo(User::class); }

    public function scopeForUser(Builder $q, int $userId): Builder   { return $q->where('user_id', $userId); }
    public function scopeUpcoming(Builder $q): Builder               { return $q->where('status', 'active')->where('start_time', '>=', now()); }
    public function scopePrevious(Builder $q): Builder               { return $q->where('status', 'active')->where('start_time', '<', now()); }
    public function scopeCancelled(Builder $q): Builder              { return $q->whereIn('status', ['cancelled_user', 'cancelled_admin']); }

    public static function statsForUser(int $userId): object
    {
        $now = now();
        return static::forUser($userId)
            ->selectRaw("
                SUM(CASE WHEN status = 'active' AND start_time >= ? THEN 1 ELSE 0 END) as upcoming_count,
                SUM(CASE WHEN status = 'active' AND start_time <  ? THEN 1 ELSE 0 END) as previous_count,
                SUM(CASE WHEN status IN ('cancelled_user','cancelled_admin') THEN 1 ELSE 0 END) as cancelled_count
            ", [$now, $now])
            ->first();
    }

    protected function displayTime(): Attribute
    {
        return Attribute::make(get: function () {
            $date = convertToPersian(toJalali($this->start_time, 'Y/m/d'));
            if ($this->is_full_day) return $date . ' (تمام روز)';
            return $date . ' • '
                . convertToPersian(toJalali($this->start_time, 'H:i'))
                . ' تا '
                . convertToPersian(toJalali($this->end_time, 'H:i'));
        });
    }
}
