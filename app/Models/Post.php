<?php

namespace App\Models;

use App\Models\Concerns\HasAudience;
use App\Models\Concerns\HasModelCache;
use App\Models\Concerns\HasMenuState;
use App\Models\Concerns\HasNudgeTracking;
use App\Models\Concerns\HasPublicAssetUrl;
use App\Models\Concerns\HasUser;
use App\Services\ContentSanitizerService;
use App\Traits\CleansAttachedFiles;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use App\Models\Concerns\HasNotifyEvents;
use App\Models\Concerns\Hooks\HasPostHooks;

class Post extends Model
{
    use HasModelCache,
        HasMenuState,
        HasFactory,
        HasPublicAssetUrl,
        HasNudgeTracking,
        CleansAttachedFiles,
        HasAudience,
        HasNotifyEvents,
        HasUser,
        HasPostHooks;

    public const NUDGE_KEY = 'posts-controller:nudge';
    protected $fillable = [
        'title',
        'body',
        'image',
        'pinned',
        'user_id'
    ];

    public function scopeNotPinned(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->where('pinned', '<>', 1)->orWhereNull('pinned'));
    }

    public static function cachedPins(?string $department = null): Collection
    {
        return static::cached("pins:{$department}", fn () => static::with('user')->where('pinned', 1)->active()->visibleTo($department)->latest()->take(1)->get());
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

    public static function notifyFields(): array
    {
        return ['pinned' => 'bool', 'user_id' => 'user'];
    }
}
