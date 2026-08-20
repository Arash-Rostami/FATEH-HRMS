<?php

namespace App\Livewire\Dashboard\Tab\Presentation;

use App\Models\Photo;
use App\Traits\HasTimelineMonths;

class GalleryPresenter
{
    use HasTimelineMonths;

    private const COLLAGE_TRANSFORMS = [
        ['z' => 'z-20', 'rotate' => 'rotate-6', 'hover' => 'group-hover:-translate-x-12 group-hover:-rotate-12'],
        ['z' => 'z-10', 'rotate' => '-rotate-2', 'hover' => 'group-hover:translate-x-0 group-hover:rotate-3'],
        ['z' => 'z-0', 'rotate' => 'rotate-3', 'hover' => 'group-hover:translate-x-12 group-hover:rotate-12'],
    ];

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

    public function wallCardData(Photo $photo): array
    {
        $urls = $photo->image_urls ?? [];

        return [
            'urls' => $urls,
            'lead' => $urls[0] ?? null,
            'rest' => array_slice($urls, 1),
            'monthKey' => toJalali($photo->event_date, 'F Y'),
            'dateLabel' => toJalali($photo->event_date, 'j F Y'),
            'extraCount' => count($urls),
        ];
    }

    public function collageData(Photo $photo): array
    {
        $paths = $photo->image_urls;
        $visibleImages = array_slice($paths, 0, 3);

        return [
            'paths' => $paths,
            'visibleImages' => $visibleImages,
            'hiddenImages' => array_slice($paths, 3),
            'hiddenImageCount' => count($paths) - count($visibleImages),
        ];
    }

    public function collageCellData(int $index, string $url): array
    {
        return [
            'transform' => self::COLLAGE_TRANSFORMS[$index] ?? ['z' => 'z-0', 'rotate' => '', 'hover' => ''],
            'isVideo' => isVideo($url),
        ];
    }

    public function captionText(Photo $photo): string
    {
        return strip_tags($photo->description);
    }
}
