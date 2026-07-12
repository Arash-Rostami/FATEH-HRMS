<?php

namespace App\Models;

use App\Livewire\Dashboard\Channel\Actions\ForceDeleteChannelMessageAction;
use App\Models\Traits\HasPrunableStatus;
use App\Services\ContentSanitizerService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ChannelMessage extends Model
{
    use HasFactory,
        SoftDeletes,
        Prunable,
        HasPrunableStatus;

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

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'reply_to_id');
    }

    public function attachmentUrls(): array
    {
        return array_map(
            fn($item) => [...$item, 'url' => Storage::disk('public')->url($item['path'])],
            $this->attachments ?? []
        );
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
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::forceDeleted(function (self $message) {
            Storage::disk('public')->deleteDirectory("channel_messages/{$message->channel_id}/{$message->id}");
        });
    }
}