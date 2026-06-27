<?php

namespace App\Livewire\Dashboard\Tab\Presentation;

use App\Models\Photo;

class GalleryPresenter
{
    /**
     * Department-scope metadata for a photo card, mirroring the admin
     * GalleryTablePresenter::department() three-way classification so the
     * user panel and admin stay in lock-step:
     *   count(all_departments) > 1  -> multi-departmental (shared)
     *   count(all_departments) === 1 -> single department (restricted)
     *   count(all_departments) === 0 -> public (everyone)
     *
     * `all_departments` (Photo accessor) = department_id + departments(JSON)
     * merged and deduped, so it covers every combination of the two columns
     * without the gaps a literal "department_id filled / departments > 1" rule
     * would leave (e.g. department_id null + departments with a single entry).
     */
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