<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Services\ContentSanitizerService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

trait HasThreadMessages
{
    public function attachmentUrls(): array
    {
        return array_map(
            fn($item) => [...$item, 'url' => Storage::disk('public')->url($item['path'])],
            $this->attachments ?? []
        );
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(static::class, 'reply_to_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(static::class, 'reply_to_id');
    }

    protected function body(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value): ?string => ContentSanitizerService::clean($value),
        );
    }
}