<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feed extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'category',
        'content',
        'media_paths',
        'poll_options',
    ];

    protected $casts = [
        'media_paths' => 'array',
        'poll_options' => 'array',
    ];

    public static function boot()
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

    public static function getTodayCount(): int
    {
        return self::whereDate('created_at', today())->count();
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
