<?php

namespace App\Models;

use App\Models\Traits\HasPublicAssetUrl;
use App\Traits\CleansAttachedFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Reply extends Model
{
    use HasFactory, HasPublicAssetUrl, CleansAttachedFiles;

    protected $fillable = [
        'repliable_type',
        'repliable_id',
        'user_id',
        'body',
        'files',
    ];

    public function repliable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'files' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(fn(self $reply) => static::deleteStoredFiles($reply->files));
    }
}
