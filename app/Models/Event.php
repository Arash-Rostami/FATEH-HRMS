<?php

namespace App\Models;

use App\Models\Traits\HasCountdown;
use App\Models\Traits\HasMenuState;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasMenuState,
        HasCountdown,
        HasFactory;

    public const MENU_STATE_EVENTS = ['updated', 'deleted'];

    public const REMIND_HOURS_OPTIONS = [1, 2, 3, 6, 12, 24];

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'date',
        'private',
        'remind_hours',
        'countdown',
        'date_jalali',
        'date_time_part',
    ];

    protected $appends = [
        'date_jalali',
        'date_time_part',
    ];

    public static function hasImminentSharedFor(User $user): bool
    {
        return static::query()
            ->where('user_id', $user->id)
            ->whereHas('shares')
            ->whereBetween('date', [now(), now()->addDay()])
            ->exists();
    }

    public function scopePrivate($query)
    {
        return $query->where('private', true);
    }

    public function scopePublic($query)
    {
        return $query->where('private', false);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now());
    }

    public function shares(): HasMany
    {
        return $this->hasMany(EventShare::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function nextReminderFor(User $user): ?array
    {
        $event = static::query()
            ->whereNotNull('remind_hours')
            ->where('date', '>', now())
            ->whereRaw('DATE_SUB(date, INTERVAL remind_hours HOUR) <= ?', [now()])
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('private', false)
                    ->orWhereHas('shares', fn($sq) => $sq->where('user_id', $user->id));
            })
            ->orderBy('date')
            ->first(['id', 'title', 'date']);

        if (!$event) {
            return null;
        }

        return [
            'id' => $event->id,
            'title' => $event->title,
            'event_at_iso' => $event->date->toIso8601String(),
        ];
    }

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'private' => 'boolean',
            'remind_hours' => 'integer',
            'countdown' => 'array',
        ];
    }

    protected function dateJalali(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => isset($attributes['date'])
                ? Carbon::parse($attributes['date'])->format('Y-m-d') : null,
            set: function (?string $value, array $attributes) {
                if (blank($value)) return [];

                $existing = isset($attributes['date']) ? Carbon::parse($attributes['date']) : now();
                $newDate = Carbon::parse($value);

                return [
                    'date' => $newDate->setTime(
                        $existing->hour, $existing->minute, $existing->second
                    )->format('Y-m-d H:i:s'),
                ];
            }
        );
    }

    protected function dateTimePart(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => isset($attributes['date'])
                ? Carbon::parse($attributes['date'])->format('H:i') : '08:00',
            set: function (?string $value, array $attributes) {
                if (blank($value)) return [];

                $existing = isset($attributes['date']) ? Carbon::parse($attributes['date']) : now();
                $newTime = Carbon::parse($value);

                return [
                    'date' => $existing->setTime(
                        $newTime->hour, $newTime->minute, $newTime->second
                    )->format('Y-m-d H:i:s'),
                ];
            }
        );
    }
}
