<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    protected $fillable = [
        'path',
        'title',
        'department',
        'description',
        'event_date',
    ];

    protected $casts = [
        'event_date' => 'date',
        'path' => 'array',
    ];
}
