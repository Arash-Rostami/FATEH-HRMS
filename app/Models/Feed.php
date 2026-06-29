<?php

namespace App\Models;

use App\Models\Traits\HasMenuState;
use App\Models\Traits\HasPublicAssetUrl;
use App\Services\ContentSanitizerService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feed extends Model
{
    use HasFactory, HasMenuState, HasPublicAssetUrl;

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
            $feed->polls()->delete();
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

    public function polls(): HasMany
    {
        return $this->hasMany(Poll::class);
    }

    public function pollResults(): array
    {
        $this->loadMissing('polls');

        $votes = $this->polls;
        $counts = [];

        foreach ($votes as $vote) {
            $idx = (int) $vote->option_index;
            $counts[$idx] = ($counts[$idx] ?? 0) + 1;
        }

        return [
            'total'  => $votes->count(),
            'counts' => $counts,
        ];
    }

    public static function extractPollSettings(array $options): array
    {
        if (count($options) >= 3 && in_array($options[0], ['single', 'multiple'], true)) {
            return [
                'mode'      => $options[0] === 'multiple' ? 'multiple' : 'single',
                'comments'  => in_array($options[1], ['1', 'true', true, 1], true),
                'reactions' => in_array($options[2], ['1', 'true', true, 1], true),
                'choices'   => array_values(array_slice($options, 3)),
            ];
        }

        return [
            'mode'      => 'single',
            'comments'  => true,
            'reactions' => true,
            'choices'   => array_values($options),
        ];
    }

    public function pollChoices(): array
    {
        return self::extractPollSettings($this->poll_options ?? [])['choices'];
    }

    public function pollSettings(): array
    {
        $settings = self::extractPollSettings($this->poll_options ?? []);

        unset($settings['choices']);

        return $settings;
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

    protected function mediaUrls(): Attribute
    {
        return Attribute::make(
            get: fn () => array_map(
                fn ($p) => static::resolvePublicAssetUrl($p),
                array_values(array_filter($this->media_paths ?? [], fn ($p) => !empty($p))),
            ),
        )->shouldCache();
    }
}
