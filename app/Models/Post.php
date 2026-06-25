<?php

namespace App\Models;

use App\Services\ContentSanitizerService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'image',
        'pinned',
        'user_id'
    ];

    public function scopePinned($query)
    {
        return $query->where('pinned', true);
    }

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
            get: function (?string $value, array $attributes) {
                $path = $attributes['image'] ?? null;

                if ($path === null || $path === '') {
                    return '';
                }

                if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
                    return $path;
                }

                $disk = Storage::disk('public');

                if ($disk->exists($path)) {
                    return $disk->url($path);
                }

                return asset($path);
            },
        )->shouldCache();
    }
}
