<?php

namespace App\Livewire\Dashboard\Tab\Presentation;

use App\Models\Photo;
use Illuminate\Support\Collection;

class GalleryPresenter
{
    public function months(Collection $photos): Collection
    {
        return $photos
            ->filter(fn($p) => $p->event_date)
            ->map(fn($p) => ['key' => toJalali($p->event_date, 'F Y'), 'sort' => $p->event_date])
            ->unique('key')
            ->sortByDesc('sort')
            ->values();
    }

    public function scopeMeta(Photo $photo): array
    {
        $count = count($photo->all_departments);

        return match (true) {
            $count > 1 => [
                'icon'  => 'groups',
                'label' => __('resources/gallery/strings.filters.multiple_departments'),
            ],
            $count === 1 => [
                'icon'  => 'lock',
                'label' => __('resources/gallery/strings.filters.single_department'),
            ],
            default => [
                'icon'  => 'public',
                'label' => __('resources/gallery/strings.fields.public_gallery'),
            ],
        };
    }
}