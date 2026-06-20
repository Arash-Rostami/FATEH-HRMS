<?php

namespace App\Livewire\Dashboard\Profile\Presentation;

use App\Enums\ProfileDetailGroup;
use App\Services\ProfileDetailCatalog;

class DetailsPresenter
{
    public function groups(): array
    {
        static $cachedGroups = null;

        if ($cachedGroups !== null) {
            return $cachedGroups;
        }

        $out = [];
        foreach (ProfileDetailCatalog::grouped() as $section => $fields) {
            $out[] = [
                'group'  => ProfileDetailGroup::from($section),
                'fields' => $fields,
            ];
        }

        $cachedGroups = $out;

        return $cachedGroups;
    }
}
