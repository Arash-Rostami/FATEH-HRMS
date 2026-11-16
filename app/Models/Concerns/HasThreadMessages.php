<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Services\ContentSanitizerService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
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

    public function toggleReaction(int $userId, string $emoji): void
    {
        $reactions = collect($this->reactions ?? []);
        $same = $reactions->contains(
            fn(array $r) => (int) ($r['user_id'] ?? 0) === $userId && ($r['emoji'] ?? null) === $emoji
        );

        $reactions = $reactions->reject(fn(array $r) => (int) ($r['user_id'] ?? 0) === $userId);

        if (!$same) {
            $reactions->push(['user_id' => $userId, 'emoji' => $emoji]);
        }

        DB::table($this->getTable())->where('id', $this->getKey())->update([
            'reactions' => json_encode($reactions->values()->all(), JSON_UNESCAPED_UNICODE),
        ]);
    }

    protected function body(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value): ?string => ContentSanitizerService::clean($value),
        );
    }
}