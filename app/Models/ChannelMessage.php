<?php

namespace App\Models;

use App\Livewire\Dashboard\Channel\Actions\ForceDeleteChannelMessageAction;
use App\Models\Concerns\HasPrunableStatus;
use App\Models\Concerns\HasThreadMessages;
use App\Models\Concerns\Hooks\HasChannelMessageHooks;
use App\Traits\CleansAttachedFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ChannelMessage extends Model
{
    use HasFactory,
        SoftDeletes,
        Prunable,
        HasPrunableStatus,
        CleansAttachedFiles,
        HasThreadMessages,
        HasChannelMessageHooks;

    protected $fillable = [
        'channel_id',
        'sender_id',
        'body',
        'attachments',
        'reply_to_id',
        'is_edited',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public static function lastIdForChannel(int $channelId): ?int
    {
        return static::withoutTrashed()
            ->where('channel_id', $channelId)
            ->max('id');
    }

    public static function totalUnreadFor(int $userId): int
    {
        return DB::table('channel_messages')
            ->join('channel_members', function ($join) use ($userId) {
                $join->on('channel_members.channel_id', '=', 'channel_messages.channel_id')
                    ->where('channel_members.user_id', $userId)
                    ->where('channel_messages.id', '>', DB::raw('COALESCE(channel_members.last_read_message_id, 0)'));
            })
            ->join('channels', function ($join) {
                $join->on('channels.id', '=', 'channel_messages.channel_id')
                    ->whereNull('channels.deleted_at');
            })
            ->whereNull('channel_messages.deleted_at')
            ->count();
    }

    public function prunable()
    {
        return static::where('deleted_at', '<=', now()->subDays($this->getPruneDays()));
    }

    public function prune()
    {
        app(ForceDeleteChannelMessageAction::class)->execute($this);
    }

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'is_edited' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }
}