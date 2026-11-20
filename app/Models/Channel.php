<?php

namespace App\Models;

use App\Enums\ChannelType;
use App\Livewire\Dashboard\Channel\Actions\ForceDeleteChannelAction;
use App\Models\Concerns\HasPrunableStatus;
use App\Traits\CleansAttachedFiles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use App\Models\Concerns\HasDailyQuota;
use App\Models\Concerns\HasNotifyEvents;
use App\Models\Concerns\Hooks\HasChannelHooks;

class Channel extends Model
{
    use HasFactory,
        SoftDeletes,
        Prunable,
        HasPrunableStatus,
        CleansAttachedFiles,
        HasDailyQuota,
        HasNotifyEvents,
        HasChannelHooks;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'owner_id',
    ];

    protected static function quotaFeature(): string
    {
        return 'channel';
    }

    public function members(): HasMany
    {
        return $this->hasMany(ChannelMember::class);
    }

    public function memberUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'channel_members')
            ->withPivot(['joined_at', 'entered_at', 'last_read_message_id', 'role', 'created_at', 'updated_at']);
    }

    public function isProjectLinked(): bool
    {
        return array_key_exists('project_link_id', $this->attributes)
            ? $this->attributes['project_link_id'] !== null
            : Project::idForChannel($this->id) !== null;
    }

    public static function invitedUserIds(int $channelId): array
    {
        return DB::table('channel_members')
            ->where('channel_id', $channelId)
            ->whereNull('entered_at')
            ->pluck('user_id')
            ->all();
    }

    public static function unreadCountsFor(int $channelId): array
    {
        return DB::table('channel_members as cm')
            ->join('channel_messages as msg', function ($join) {
                $join->on('msg.channel_id', '=', 'cm.channel_id')
                    ->where('msg.id', '>', DB::raw('COALESCE(cm.last_read_message_id, 0)'));
            })
            ->where('cm.channel_id', $channelId)
            ->whereNotNull('cm.entered_at')
            ->whereNull('msg.deleted_at')
            ->groupBy('cm.user_id')
            ->pluck(DB::raw('count(*)'), 'cm.user_id')
            ->toArray();
    }

    public function scopeWithPinnedPreview(Builder $query): Builder
    {
        return $query->addSelect([
            'pinned_body' => ChannelMessage::select('body')->whereColumn('channel_messages.id', 'channels.pinned_message_id')->limit(1),
            'pinned_sender_id' => ChannelMessage::select('sender_id')->whereColumn('channel_messages.id', 'channels.pinned_message_id')->limit(1),
        ]);
    }

    public function scopeWithViewerRole(Builder $query, int $userId): Builder
    {
        return $query->addSelect([
            'viewer_role' => ChannelMember::select('role')->whereColumn('channel_members.channel_id', 'channels.id')->where('channel_members.user_id', $userId)->limit(1),
            'project_link_id' => Project::withTrashed()->select('id')->whereColumn('projects.channel_id', 'channels.id')->limit(1),
        ]);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChannelMessage::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function prunable()
    {
        return static::where('deleted_at', '<=', now()->subDays($this->getPruneDays()));
    }

    public function prune()
    {
        app(ForceDeleteChannelAction::class)->execute($this);
    }

    public static function notifyFields(): array
    {
        return ['owner_id' => 'user'];
    }

    protected function casts(): array
    {
        return [
            'type' => ChannelType::class,
            'deleted_at' => 'datetime',
        ];
    }
}
