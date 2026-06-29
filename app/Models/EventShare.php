<?php

namespace App\Models;

use App\Models\Traits\HasMenuState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventShare extends Model
{
    use HasMenuState;

    public const MENU_STATE_EVENTS = ['created', 'deleted'];


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
}
