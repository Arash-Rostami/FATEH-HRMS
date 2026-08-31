<?php

namespace App\Models;

use App\Models\Concerns\HasModelCache;
use App\Models\Concerns\HasMenuState;
use App\Models\Concerns\HasNudgeTracking;
use App\Models\Concerns\HasPublicAssetUrl;
use App\Services\ContentSanitizerService;
use App\Traits\CleansAttachedFiles;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class Post extends Model
{
    use HasModelCache,
        HasMenuState,
        HasFactory,
        HasPublicAssetUrl,
        HasNudgeTracking,
        CleansAttachedFiles;

    public const NUDGE_KEY = 'posts-controller:nudge';
    protected $fillable = [
        'title',
        'body',
        'image',
        'pinned',
        'user_id'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function cachedPins(): Collection
    {
        return static::cached('pins', fn () => static::with('user')->where('pinned', 1)->latest()->take(1)->get());
    }

    public static function cachedItem(int $id): ?Post
    {
        return static::cached("item:{$id}", fn () => static::with('user')->find($id));
    }

    protected function body(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value): ?string => ContentSanitizerService::clean($value),
        );
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value, array $attributes) => static::resolvePublicAssetUrl($attributes['image'] ?? null),
        )->shouldCache();
    }

    protected static function booted(): void
    {
        static::deleting(fn(self $post) => static::deleteStoredFiles($post->image));
    }
}
