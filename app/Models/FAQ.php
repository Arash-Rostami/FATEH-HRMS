<?php

namespace App\Models;

use App\Models\Concerns\HasDepartment;
use App\Models\Concerns\HasModelCache;
use App\Models\Concerns\HasUser;
use App\Services\ContentSanitizerService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use App\Models\Concerns\HasNotifyEvents;

class FAQ extends Model
{
    use HasModelCache,
        HasFactory,
        HasDepartment,
        HasUser,
        HasNotifyEvents;

    protected $table = 'faqs';

    protected $fillable = [
        'department_id',
        'user_id',
        'category',
        'question',
        'answer'
    ];

    public static function cachedCategories(): Collection
    {
        return static::cached('categories', fn () => static::query()->whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category'));
    }

    public static function cachedCategoryFilter(): array
    {
        return static::cached('category_filter', fn () => static::distinct()->orderBy('category')->pluck('category', 'category')->toArray());
    }

    public static function notifyFields(): array
    {
        return ['user_id' => 'user', 'department_id' => 'department'];
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
