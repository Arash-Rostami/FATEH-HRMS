<?php

namespace App\Models;

use App\Livewire\Dashboard\Contact\Actions\ForceDeleteMessageAction;
use App\Models\Traits\HasMenuState;
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
    use HasFactory,
        HasMenuState,
        SoftDeletes,
        Prunable,
        HasPrunableStatus;

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


    public function attachmentUrls(): array
    {
        return array_map(
            fn($item) => [...$item, 'url' => Storage::disk('public')->url($item['path'])],
            $this->attachments ?? []
        );
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

    public static function hasUnreadFor(int $userId): bool
    {
        return static::where('recipient_id', $userId)->whereNull('read_at')->exists();
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
        return static::where('recipient_id', $userId)->whereNull('read_at')->count();
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

    protected function body(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value): ?string => ContentSanitizerService::clean($value),
        );
    }

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'is_edited' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::forceDeleted(function (self $message) {
            foreach ($message->attachments ?? [] as $attachment) {
                if (!empty($attachment['path'])) {
                    Storage::disk('public')->delete($attachment['path']);
                }
            }
        });
    }
}
