<?php

namespace App\Livewire\Dashboard\Profile\Presentation;

use App\Enums\ProfileDetailGroup;
use App\Services\ProfileDetailCatalog;

class DetailsPresenter
{
    public function groups(): array
    {
        $out = [];
        foreach (ProfileDetailCatalog::grouped() as $section => $fields) {
            $out[] = [
                'group'  => ProfileDetailGroup::from($section),
                'fields' => $fields,
            ];
        }
        return $out;
    }
}
