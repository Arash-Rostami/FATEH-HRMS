<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'resource_type',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];
}
