<?php

namespace App\Models;

use App\Enums\ChannelType;
use App\Livewire\Dashboard\Channel\Actions\ForceDeleteChannelAction;
use App\Models\Traits\HasPrunableStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Channel extends Model
{
    use HasFactory,
        SoftDeletes,
        Prunable,
        HasPrunableStatus;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'owner_id',
    ];

    public function getPruneDays(): int
    {
        return 30;
    }

    public function members(): HasMany
    {
        return $this->hasMany(ChannelMember::class);
    }

    public function memberUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'channel_members')
            ->withPivot(['joined_at', 'entered_at', 'last_read_message_id', 'created_at', 'updated_at']);
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
        return static::where('deleted_at', '<=', now()->subDays(30));
    }

    public function prune()
    {
        app(ForceDeleteChannelAction::class)->execute($this);
    }

    protected static function booted(): void
    {
        static::created(function (self $channel) {
            if ($channel->owner_id) {
                $channel->memberUsers()->attach($channel->owner_id, [
                    'joined_at' => now(),
                    'entered_at' => now(),
                    'last_read_message_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        static::forceDeleted(function (self $channel) {
            Storage::disk('public')->deleteDirectory("channel_messages/{$channel->id}");
        });
    }

    protected function casts(): array
    {
        return [
            'type' => ChannelType::class,
            'deleted_at' => 'datetime',
        ];
    }
}
