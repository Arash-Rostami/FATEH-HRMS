<?php

namespace App\Models\Concerns;

use App\Models\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait HasTodaysDesk
{
    protected static ?array $todaysDeskCache = null;
    protected static ?array $todaysReservationsCache = null;
    protected static ?array $todaysStatusSummaryCache = null;

    public function getTodaysDeskExtension(): ?string
    {
        return static::$todaysDeskCache[$this->id] ?? null;
    }

    public function getTodaysReservationsLabel(): ?string
    {
        return static::$todaysReservationsCache[$this->id] ?? null;
    }

    public function getTodaysStatusSummary(): ?string
    {
        return static::$todaysStatusSummaryCache[$this->id] ?? null;
    }

    public static function primeTodaysDeskCache(): void
    {
        if (static::$todaysDeskCache !== null) {
            return;
        }

        $reservations = Reservation::forToday()
            ->whereHas('resource', fn(Builder $q) => $q->whereIn('type', ['seat', 'car', 'meeting']))
            ->with('resource:id,type,name,metadata')
            ->get(['id', 'user_id', 'resource_id']);

        static::$todaysDeskCache = $reservations
            ->mapWithKeys(fn(Reservation $r) => [
                $r->user_id => Arr::get((array) $r->resource?->metadata, 'extension'),
            ])
            ->filter()
            ->all();

        static::$todaysStatusSummaryCache = $reservations
            ->mapWithKeys(fn(Reservation $r) => [
                $r->user_id => $r->resource?->status_summary,
            ])
            ->filter()
            ->all();

        static::$todaysReservationsCache = $reservations
            ->groupBy('user_id')
            ->map(fn(Collection $rows) => $rows->pluck('resource.labeled_name')->filter()->implode('، '))
            ->filter()
            ->all();
    }

    public static function resetTodaysDeskCache(): void
    {
        static::$todaysDeskCache = null;
        static::$todaysReservationsCache = null;
        static::$todaysStatusSummaryCache = null;
    }
}