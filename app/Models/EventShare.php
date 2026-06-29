<?php

namespace App\Models;

use App\Services\Menu\StateService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class EventShare extends Model
{
    protected $fillable = [
        'event_id',
        'user_id',
        'shared_by',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public static function hasImminentFor(User $user): bool
    {
        return static::query()
            ->where('user_id', $user->id)
            ->whereHas('event', fn($query) => $query->whereBetween('date', [now(), now()->addDay()]))
            ->exists();
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sharer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    protected static function booted(): void
    {
        $flush = fn() => DB::afterCommit(fn() => StateService::flush());
        static::created($flush);
        static::deleted($flush);
    }
}