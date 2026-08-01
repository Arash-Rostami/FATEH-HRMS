<?php

namespace App\Traits;

use Illuminate\Contracts\Support\Htmlable;

trait FilamentEditHeading
{
    public function getHeading(): string|Htmlable
    {
        $resource = static::getResource();
        $label = $resource::getModelLabel();
        $title = (string) $resource::getRecordTitle($this->getRecord());

        if (! filled($title) || $title === $label) {
            return $this->getTitle();
        }

        return __('resources/general/strings.edit_heading', ['label' => $label, 'title' => $title]);
    }
}