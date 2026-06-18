<?php

namespace App\Livewire\Dashboard\Ths\Presentation;

use App\Models\Ticket;
use Carbon\Carbon;

class TicketPresenter
{
    public function formatId(?array $ticket): string
    {
        if (!$ticket) return '';
        return sprintf('T-%s-%04d', Carbon::parse($ticket['created_at'])->format('ym'), (int) $ticket['id']);
    }

    public function formatTimestamp(?array $ticket, string $col): string
    {
        if (!$ticket || !isset($ticket[$col])) return 'نامشخص';
        return Carbon::parse($ticket[$col])->diffForHumans();
    }

    public function requestAreaLabel(string $requestType, string $requestArea, ?string $department = null): string
    {
        return Ticket::getCustomRequestAreaLabel($requestType, $requestArea, $department);
    }

    public function requestAreaIcon(?string $area, ?string $department = null, string $fallback = 'location_on'): string
    {
        return $area ? Ticket::getCustomMaterialIconForArea($area, $department) : $fallback;
    }

}
