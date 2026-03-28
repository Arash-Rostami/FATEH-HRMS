<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'metadata',
        'status',
        'image',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
