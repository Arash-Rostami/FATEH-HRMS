<?php

namespace App\Models;

use App\Models\Traits\HasMenuState;
use App\Models\Traits\HasNudgeTracking;
use App\Models\Traits\HasPublicAssetUrl;
use App\Services\ContentSanitizerService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    use HasMenuState,
        HasFactory,
        HasPublicAssetUrl,
        HasNudgeTracking;

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
}
