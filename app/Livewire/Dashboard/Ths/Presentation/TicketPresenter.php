<?php

namespace App\Livewire\Dashboard\Ths\Presentation;

use App\Filament\Resources\ThsResource\Enums\TicketPriority;
use App\Models\Ticket;
use Carbon\Carbon;

class TicketPresenter
{
    public function formatId(?array $ticket): string
    {
        if (!$ticket) return '';

        $prefix = $ticket['extra']['target_department'] ?? 'T';

        return sprintf('%s-%s-%04d', strtoupper($prefix), Carbon::parse($ticket['created_at'])->format('ym'), (int) $ticket['id']);
    }

    public function priorityMeta(string $priority): ?array
    {
        return match ($priority) {
            'low'    => ['color' => 'text-[var(--md-sys-color-primary)]', 'icon' => 'low_priority', 'title' => TicketPriority::Low->getLabel()],
            'medium' => ['color' => 'text-[var(--md-sys-color-secondary)]', 'icon' => 'drag_handle', 'title' => TicketPriority::Medium->getLabel()],
            'high'   => ['color' => 'text-[var(--md-sys-color-error)]', 'icon' => 'priority_high', 'title' => TicketPriority::High->getLabel()],
            default  => null,
        };
    }

    public function statusMeta(string $status): ?array
    {
        return match ($status) {
            'open'        => ['icon' => 'pending', 'textColor' => 'text-[var(--md-sys-color-primary)]', 'bg' => 'bg-[var(--md-sys-color-primary-container)]', 'title' => 'باز', 'pulse' => true, 'spin' => false],
            'in-progress' => ['icon' => 'sync', 'textColor' => 'text-[var(--md-sys-color-tertiary)]', 'bg' => 'bg-[var(--md-sys-color-tertiary-container)]', 'title' => 'در حال بررسی', 'pulse' => false, 'spin' => true],
            'closed'      => ['icon' => 'check_circle', 'textColor' => 'text-[var(--md-sys-color-secondary)]', 'bg' => 'bg-[var(--md-sys-color-secondary-container)]', 'title' => 'بسته شده', 'pulse' => false, 'spin' => false],
            default       => null,
        };
    }

    public function formatTimestamp(?array $ticket, string $col): string
    {
        if (!$ticket || !isset($ticket[$col])) return 'نامشخص';
        return toJalaliRelative($ticket[$col]);
    }

    public function requestAreaLabel(string $requestType, ?string $requestArea, ?string $department = null): string
    {
        return $requestArea ? Ticket::getCustomRequestAreaLabel($requestType, $requestArea, $department) : '—';
    }

    public function requestAreaIcon(?string $area, ?string $department = null, string $fallback = 'location_on'): string
    {
        return $area ? Ticket::getCustomMaterialIconForArea($area, $department) : $fallback;
    }
}
