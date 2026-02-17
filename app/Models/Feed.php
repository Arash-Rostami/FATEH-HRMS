<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Feed extends Model
{
    protected  = [
        'user_id',
        'content',
        'media_paths',
        'poll_options',
        'category',
    ];

    protected  = [
        'media_paths' => 'array',
        'poll_options' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($feed) {
            $feed->comments()->delete();
            $feed->reactions()->delete();
        });
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->latest();
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
