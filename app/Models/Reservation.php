<?php

namespace App\Models;

use App\Casts\JalaliTimestamp;
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
        'status',
        'cancelled_by_id',
        'cancelled_at',
        'cancel_reason',
        'parent_id',
    ];

    protected $casts = [
        'start_time' => JalaliTimestamp::class,
        'end_time' => JalaliTimestamp::class,
        'cancelled_at' => JalaliTimestamp::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by_id');
    }

    public function parent()
    {
        return $this->belongsTo(Reservation::class, 'parent_id');
    }
}
