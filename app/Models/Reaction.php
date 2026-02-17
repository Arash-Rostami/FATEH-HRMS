<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reaction extends Model
{
    protected $fillable = [
        'user_id',
        'feed_id',
        'emoji',
    ];

    public function feed(): BelongsTo
    {
        return $this->belongsTo(Feed::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
