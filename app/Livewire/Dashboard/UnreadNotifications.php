<?php

namespace App\Livewire\Dashboard;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Livewire\DatabaseNotifications;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;


class UnreadNotifications extends DatabaseNotifications
{
    public function clearNotificationsAction(): Action
    {
        return parent::clearNotificationsAction()->hidden();
    }

    public function getNotificationsQuery(): Builder|Relation
    {
        $query = parent::getNotificationsQuery();

        if (Filament::getCurrentPanel()?->getId() !== 'admin') {
            $query->unread();
        }

        return $query;
    }

    public function markAllNotificationsAsReadAction(): Action
    {
        return parent::markAllNotificationsAsReadAction()->label('حذف همه اعلانات');
    }
}
