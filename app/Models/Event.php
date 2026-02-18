<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    protected  = [
        'user_id',
        'title',
        'description',
        'date',
        'private'
    ];

    protected  = [
        'date' => 'datetime',
        'private' => 'boolean',
    ];

    public function scopePrivate($query)
    {
        return $query->where('private', true);
    }

    public function scopePublic($query)
    {
        return $query->where('private', false);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
