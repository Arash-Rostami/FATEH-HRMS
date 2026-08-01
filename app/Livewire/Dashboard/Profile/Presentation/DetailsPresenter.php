<?php

namespace App\Livewire\Dashboard\Profile\Presentation;

use App\Enums\ProfileDetailGroup;
use App\Services\ProfileDetailCatalog;

class DetailsPresenter
{
    public function groups(): array
    {
        return once(function () {
            $out = [];
            foreach (ProfileDetailCatalog::userGrouped() as $section => $fields) {
                $out[] = [
                    'group'  => ProfileDetailGroup::from($section),
                    'fields' => $fields,
                ];
            }
            return $out;
        });
    }
}
