<?php

namespace App\Models;

use App\Services\ContentSanitizerService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feed extends Model
{
    use HasFactory;

    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    public const VIDEO_EXTENSIONS = ['mp4', 'mov', 'webm', 'avi', 'mkv'];

    protected $fillable = [
        'user_id',
        'category',
        'content',
        'media_paths',
        'poll_options',
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

    public function commentsRel(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public static function getTodayCount(): int
    {
        return self::whereDate('created_at', today())->count();
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'media_paths' => 'array',
            'poll_options' => 'array',
        ];
    }

    protected function content(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value): ?string => ContentSanitizerService::clean($value),
        );
    }

    protected function images(): Attribute
    {
        return Attribute::make(
            get: fn() => array_values(array_filter(
                $this->media_paths ?? [],
                fn($p) => in_array(strtolower(pathinfo($p, PATHINFO_EXTENSION)), self::IMAGE_EXTENSIONS)
            ))
        );
    }

    protected function videos(): Attribute
    {
        return Attribute::make(
            get: fn() => array_values(array_filter(
                $this->media_paths ?? [],
                fn($p) => in_array(strtolower(pathinfo($p, PATHINFO_EXTENSION)), self::VIDEO_EXTENSIONS)
            ))
        );
    }
}
