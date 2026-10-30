<?php

namespace App\Models;

use App\Livewire\Dashboard\Contact\Actions\ForceDeleteMessageAction;
use App\Models\Concerns\HasMenuState;
use App\Models\Concerns\HasPrunableStatus;
use App\Models\Concerns\HasThreadMessages;
use App\Models\Concerns\Hooks\HasMessageHooks;
use App\Traits\CleansAttachedFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory,
        HasMenuState,
        SoftDeletes,
        Prunable,
        HasPrunableStatus,
        CleansAttachedFiles,
        HasThreadMessages,
        HasMessageHooks;

    public const MENU_STATE_EVENTS = ['created', 'updated', 'deleted', 'restored', 'forceDeleted'];

    protected $fillable = [
        'sender_id',
        'recipient_id',
        'body',
        'attachments',
        'reply_to_id',
        'is_edited',
        'read_at',
    ];


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

    public static function hasUnreadFor(int $userId): bool
    {
        return static::where('recipient_id', $userId)
            ->whereNull('read_at')
            ->whereHas('sender', fn($q) => $q->visibleOnBoard())
            ->exists();
    }

    public static function unreadCountsFrom(int $senderId): array
    {
        return static::where('sender_id', $senderId)
            ->whereNull('read_at')
            ->selectRaw('recipient_id, COUNT(*) AS c')
            ->groupBy('recipient_id')
            ->pluck('c', 'recipient_id')
            ->toArray();
    }

    public static function totalUnreadFor(int $userId): int
    {
        return static::where('recipient_id', $userId)
            ->whereNull('read_at')
            ->whereHas('sender', fn($q) => $q->visibleOnBoard())
            ->count();
    }

    public function prunable()
    {
        return static::where('deleted_at', '<=', now()->subDays($this->getPruneDays()));
    }

    public function prune()
    {
        app(ForceDeleteMessageAction::class)->execute($this);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
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
