<?php

namespace App\Traits;

trait FilamentPageBehavior
{
    protected function getCreatedNotificationTitle(): ?string
    {
        return __('resources/general/strings.notifications.created');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('resources/general/strings.notifications.saved');
    }
}
