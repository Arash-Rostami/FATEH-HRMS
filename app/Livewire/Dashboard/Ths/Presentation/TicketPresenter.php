<?php

namespace App\Livewire\Dashboard\Ths\Presentation;

use App\Filament\Resources\ThsResource\Enums\TicketPriority;
use App\Models\Ticket;
use App\Traits\RiskEscalationChip;
use Carbon\Carbon;

class TicketPresenter
{
    use RiskEscalationChip;

    private const STATUS_ORDER = ['open', 'in-progress', 'closed'];

    public function formatId(?array $ticket): string
    {
        if (!$ticket) return '';

        $prefix = $ticket['extra']['target_department'] ?? 'T';

        return sprintf('%s-%s-%04d', strtoupper($prefix), Carbon::parse($ticket['created_at'])->format('ym'), (int) $ticket['id']);
    }

    public function priorityMeta(string $priority): ?array
    {
        return match ($priority) {
            'low'    => ['color' => 'text-[var(--md-sys-color-primary)]', 'bg' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]', 'icon' => 'low_priority', 'title' => TicketPriority::Low->getLabel()],
            'medium' => ['color' => 'text-[var(--md-sys-color-secondary)]', 'bg' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]', 'icon' => 'drag_handle', 'title' => TicketPriority::Medium->getLabel()],
            'high'   => ['color' => 'text-[var(--md-sys-color-error)]', 'bg' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]', 'icon' => 'priority_high', 'title' => TicketPriority::High->getLabel()],
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

    public function deadlineChip(\DateTimeInterface|string|null $deadline, \DateTimeInterface|string|null $completionDate, string $status): ?array
    {
        if (!$deadline) {
            return null;
        }

        $deadlineAt = Carbon::parse($deadline);
        $reference = $completionDate ? Carbon::parse($completionDate) : now();
        $diff = $reference->diff($deadlineAt);
        $duration = convertToPersian($diff->days) . ' روز و ' . convertToPersian($diff->h) . ' ساعت';
        $isLate = $reference->gt($deadlineAt);

        if ($completionDate) {
            return [
                'icon' => $isLate ? 'event_busy' : 'check_circle',
                'text' => $isLate ? "با تأخیر: {$duration}" : "پیش از موعد: {$duration}",
                'classes' => $this->riskToneClasses($isLate ? 'error' : 'success'),
            ];
        }

        if ($status === 'closed') {
            return null;
        }

        return [
            'icon' => $isLate ? 'event_busy' : 'schedule',
            'text' => $isLate ? "سررسید گذشته: {$duration}" : "تا سررسید: {$duration}",
            'classes' => $this->riskToneClasses($isLate ? 'error' : ($diff->days < 1 ? 'warning' : 'success')),
        ];
    }

    public function statusSteps(string $currentStatus): array
    {
        $currentIndex = array_search($currentStatus, self::STATUS_ORDER, true);
        $currentIndex = $currentIndex === false ? 0 : $currentIndex;

        $steps = [];

        foreach (self::STATUS_ORDER as $index => $status) {
            $meta = $this->statusMeta($status);

            $steps[] = [
                'icon' => $meta['icon'] ?? 'circle',
                'label' => $meta['title'] ?? $status,
                'state' => match (true) {
                    $index < $currentIndex => 'done',
                    $index === $currentIndex => 'active',
                    default => 'upcoming',
                },
            ];
        }

        return $steps;
    }
}
