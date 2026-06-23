<?php

namespace App\Models;

use App\Models\Traits\HasPrunableStatus;
use App\Services\ContentSanitizerService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    use HasFactory, SoftDeletes, Prunable, HasPrunableStatus;

    public const PRUNE_DAYS = 30;

    protected $fillable = [
        'sender_id',
        'recipient_id',
        'body',
        'attachments',
        'reply_to_id',
        'is_edited',
        'read_at',
    ];

    protected function body(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value): ?string => ContentSanitizerService::clean($value),
        );
    }

    public function attachmentUrls(): array
    {
        return collect($this->attachments ?? [])
            ->map(fn($item) => [...$item, 'url' => Storage::disk('public')->url($item['path'])])
            ->all();
    }

    public function getPruneDays(): int
    {
        return self::PRUNE_DAYS;
    }

    public function isEditable(int $userId, int $seconds = 300): bool
    {
        if (!$this->isOwnedBy($userId)) return false;
        if ($this->trashed()) return false;
        return $this->created_at->diffInSeconds(now()) <= $seconds;
    }

    public function isOwnedBy(int $userId): bool
    {
        return $this->sender_id === $userId;
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if (!$this->isRead()) {
            $this->update(['read_at' => now()]);
        }
    }

    public function prunable()
    {
        return static::where('deleted_at', '<=', now()->subDays(self::PRUNE_DAYS));
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'reply_to_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'is_edited' => 'boolean',
            'read_at' => 'datetime',
        ];
    }
}
