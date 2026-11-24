<?php

namespace App\Models;

use App\Livewire\Dashboard\Contact\Actions\ForceDeleteMessageAction;
use App\Models\Concerns\HasDailyQuota;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class Message extends Model
{
    use HasFactory,
        HasMenuState,
        SoftDeletes,
        Prunable,
        HasPrunableStatus,
        CleansAttachedFiles,
        HasThreadMessages,
        HasDailyQuota,
        HasMessageHooks;

    public const MENU_STATE_EVENTS = ['created', 'updated', 'deleted', 'restored', 'forceDeleted'];

    protected static function quotaFeature(): string
    {
        return 'contact';
    }

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

    public static function pairVersionKey(int $a, int $b): string
    {
        return 'dm:rv:' . min($a, $b) . ':' . max($a, $b);
    }

    public static function pinPair(int $a, int $b): QueryBuilder
    {
        return DB::table('message_pins')
            ->where('user_low_id', min($a, $b))
            ->where('user_high_id', max($a, $b));
    }

    public static function pinSelects(int $a, int $b): array
    {
        $pin = fn() => static::pinPair($a, $b)->select('message_id');

        return [
            'pinned_message_id' => static::select('id')->whereIn('id', $pin())->limit(1),
            'pinned_body' => static::select('body')->whereIn('id', $pin())->limit(1),
            'pinned_sender_id' => static::select('sender_id')->whereIn('id', $pin())->limit(1),
        ];
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

    public function scopeToActivePeer(Builder $query): Builder
    {
        return $query->whereHas('recipient', fn ($q) => $q->visibleOnBoard());
    }

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'reactions' => 'array',
            'is_edited' => 'boolean',
            'read_at' => 'datetime',
        ];
    }
}
