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


