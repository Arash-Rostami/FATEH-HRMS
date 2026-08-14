<?php

namespace App\Models;

use App\Models\Traits\HasExtraCatalog;
use App\Traits\CleansAttachedFiles;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Onboarding extends Model
{
    use HasFactory, HasExtraCatalog, CleansAttachedFiles;

    protected $fillable = [
        'welcome',
        'videos',
        'mission',
        'vision',
        'guides',
        'schedule',
        'extras',
        'is_active',
        'user_id'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (self $onboarding) {
            static::deleteStoredFiles($onboarding->videos, ['url', 'thumbnail']);
            static::deleteStoredFiles($onboarding->guides, ['url']);
        });
    }

    protected function casts(): array
    {
        return [
            'videos' => AsCollection::class,
            'guides' => AsCollection::class,
            'extras' => AsArrayObject::class,
            'is_active' => 'boolean',
        ];
    }
}
