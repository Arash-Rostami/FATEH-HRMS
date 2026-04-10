<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes, Prunable;

    protected $fillable = [
        'sender_id',
        'recipient_id',
        'body',
        'reply_to_id',
        'is_edited',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_edited' => 'boolean',
            'read_at'   => 'datetime',
        ];
    }


    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'reply_to_id');
    }

    // ── Helpers ──

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if (! $this->isRead()) {
            $this->update(['read_at' => now()]);
        }
    }

    public function isOwnedBy(int $userId): bool
    {
        return $this->sender_id === $userId;
    }

    public function isEditable(int $userId, int $seconds = 300): bool
    {
        if (! $this->isOwnedBy($userId)) return false;
        if ($this->trashed()) return false;
        return $this->created_at->diffInSeconds(now()) <= $seconds;
    }


    public function prunable()
    {
        return static::where('deleted_at', '<=', now()->subDays(30));
    }
}
