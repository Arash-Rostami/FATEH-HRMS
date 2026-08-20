<?php

namespace App\Traits;

use Illuminate\Support\Collection;

trait HasTimelineMonths
{
    public function months(Collection $items, string $dateField): Collection
    {
        return $items
            ->filter(fn($item) => $item->{$dateField})
            ->map(fn($item) => ['key' => toJalali($item->{$dateField}, 'F Y'), 'sort' => $item->{$dateField}])
            ->unique('key')
            ->sortByDesc('sort')
            ->values();
    }
}
