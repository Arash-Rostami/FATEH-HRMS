<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'date',
        'private',
        'date_jalali',
        'date_time_part',
    ];

    protected $appends = [
        'date_jalali',
        'date_time_part',
    ];

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'private' => 'boolean',
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
