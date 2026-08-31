<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Edge extends Model
{
    public const DURATIONS = [
        '1day' => 'addDay',
        '1week' => 'addWeek',
        '1month' => 'addMonth',
    ];
    protected $fillable = [
        'user_id',
        'edge_key',
        'subject_type',
        'subject_id',
        'icon',
        'title',
        'body',
        'url',
        'dismissed_at',
        'snoozed_until'
    ];
    protected $casts = [
        'dismissed_at' => 'datetime',
        'snoozed_until' => 'datetime',
    ];

    public function clearDismissal(): void
    {
        $this->update(['dismissed_at' => null, 'snoozed_until' => null]);
    }

    public function scopeFor($query, int $userId, string $edgeKey, int|string $subjectId)
    {
        return $query->where('user_id', $userId)->where('edge_key', $edgeKey)->where('subject_id', $subjectId);
    }

    public function scopeMatchingSubject($query, string $edgeKey, string $subjectClass, int|string $subjectId)
    {
        return $query->where('edge_key', $edgeKey)->where('subject_type', $subjectClass)->where('subject_id', $subjectId);
    }

    public function scopeVisible($query)
    {
        return $query->whereNull('dismissed_at')
            ->where(fn($q) => $q->whereNull('snoozed_until')->orWhere('snoozed_until', '<=', now()));
    }

    public function snooze(string $optionKey): void
    {
        if ($optionKey === 'forever') {
            $this->update(['dismissed_at' => now(), 'snoozed_until' => null]);
            return;
        }

        $method = self::DURATIONS[$optionKey] ?? null;

        if ($method !== null) {
            $this->update(['dismissed_at' => null, 'snoozed_until' => now()->{$method}()]);
        }
    }
}
