<?php

namespace App\Models;

use App\Models\Traits\HasPublicAssetUrl;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Link extends Model
{
    use HasFactory, HasPublicAssetUrl;

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
}


