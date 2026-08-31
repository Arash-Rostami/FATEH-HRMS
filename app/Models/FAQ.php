<?php

namespace App\Models;

use App\Models\Concerns\HasModelCache;
use App\Services\ContentSanitizerService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class FAQ extends Model
{
    use HasModelCache,
        HasFactory;

    protected $table = 'faqs';

    protected $fillable = [
        'department_id',
        'user_id',
        'category',
        'question',
        'answer'
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'code');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function cachedCategories(): Collection
    {
        return static::cached('categories', fn () => static::query()->whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category'));
    }

    public static function cachedCategoryFilter(): array
    {
        return static::cached('category_filter', fn () => static::distinct()->orderBy('category')->pluck('category', 'category')->toArray());
    }

    protected function answer(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value): ?string => ContentSanitizerService::clean($value),
        );
    }

    protected function question(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value): ?string => ContentSanitizerService::clean($value),
        );
    }
}
