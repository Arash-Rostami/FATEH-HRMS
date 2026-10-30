<?php

namespace App\Models;

use App\Models\Concerns\HasModelCache;
use App\Models\Concerns\HasPublicAssetUrl;
use App\Models\Concerns\Hooks\HasLinkHooks;
use App\Traits\CleansAttachedFiles;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Concerns\HasNotifyEvents;

class Link extends Model
{
    use HasModelCache,
        HasFactory,
        HasPublicAssetUrl,
        CleansAttachedFiles,
        HasNotifyEvents,
        HasLinkHooks;

    protected $fillable = [
        'url',
        'url_title',
        'url_description',
        'internal_url',
        'image',
        'image_description',
        'icon',
        'icon_description',
        'link',
        'sequence',
        'extra'
    ];

    public function normalizedExtra(): ?array
    {
        if (! is_array($this->extra)) {
            return null;
        }

        $ips = array_values(array_filter(array_map('trim', $this->extra), fn ($v) => $v !== ''));

        return $ips ?: [];
    }

    public function isExtraEmptyInDatabase(): bool
    {
        return (bool)DB::scalar(
            "SELECT 1 FROM {$this->getTable()} WHERE {$this->getKeyName()} = ? AND (extra IS NULL OR JSON_LENGTH(extra) = 0) LIMIT 1",
            [$this->getKey()]
        );
    }

    public function scopeExternal($query)
    {
        return $query->where('link', 'external');
    }

    public function scopeInternal($query)
    {
        return $query->where('link', 'internal');
    }

    public function scopeSorted($query)
    {
        return $query->orderBy('sequence');
    }

    public static function cachedExternal(): Collection
    {
        return static::cached('external', fn () => static::external()->orderBy('sequence')->get());
    }

    public static function cachedInternal(): Collection
    {
        return static::cached('internal', fn () => static::internal()->orderBy('sequence')->get());
    }

    public function resolvedIsInternal(?string $ip = null): bool
    {
        if (empty($this->internal_url)) {
            return false;
        }

        $ips = is_array($this->extra) ? array_values(array_filter(array_map('trim', $this->extra), fn($v) => $v !== '')) : [];

        if (empty($ips)) {
            return true;
        }

        return $ip !== null && in_array(trim($ip), $ips, true);
    }

    public function resolvedUrl(?string $ip = null): string
    {
        if ($this->resolvedIsInternal($ip)) {
            return $this->internal_url;
        }

        return $this->url ?: '';
    }

    public static function notifyFields(): array
    {
        return [
            'link' => \App\Filament\Resources\LinkResource\Enums\LinkType::class,
            'sequence' => 'number',
        ];
    }

    protected function casts(): array
    {
        return [
            'extra' => 'array',
        ];
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value, array $attributes) => static::resolvePublicAssetUrl($attributes['image'] ?? null),
        )->shouldCache();
    }

    protected function iconUrl(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value, array $attributes) => static::resolvePublicAssetUrl($attributes['icon'] ?? null),
        )->shouldCache();
    }
}


