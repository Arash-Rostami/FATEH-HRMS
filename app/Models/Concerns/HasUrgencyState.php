<?php

namespace App\Models\Concerns;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasUrgencyState
{
    protected function urgencyState(): Attribute
    {
        return Attribute::make(get: fn() => $this->calculateUrgencyState());
    }

    protected function calculateUrgencyState(): array
    {
        $exempt = $this->urgencyExemptState();
        if ($exempt !== null) {
            return $exempt;
        }

        $today = now()->startOfDay();
        $urgency = ['score' => 0.0, 'kind' => null, 'label' => null];

        $urgency = $this->applyDeadlineUrgency($urgency, $today);
        $urgency = $this->applyExtraUrgency($urgency);
        $urgency = $this->applyPriorityFloor($urgency);
        $urgency = $this->applyIdleUrgency($urgency, $today);

        $urgency['score'] = round($urgency['score'], 2);

        return $urgency;
    }

    protected function applyDeadlineUrgency(array $urgency, $today): array
    {
        $deadline = $this->urgencyDeadline();

        if (!$deadline) {
            return $urgency;
        }

        $due = clone $deadline;
        $due = $due->startOfDay();
        $daysDiff = (int)$due->diffInDays($today, true);

        if ($due->isBefore($today)) {
            return ['score' => 1.0, 'kind' => 'overdue', 'label' => $daysDiff . ' روز تأخیر'];
        }

        if ($daysDiff <= 3) {
            $label = match ($daysDiff) {
                0 => 'امروز',
                1 => 'فردا',
                default => $daysDiff . ' روز دیگر',
            };

            return ['score' => 0.7, 'kind' => 'due', 'label' => $label];
        }

        return $urgency;
    }

    protected function applyPriorityFloor(array $urgency): array
    {
        $urgency['score'] = max($urgency['score'], $this->urgencyPriorityFloor());

        return $urgency;
    }

    protected function applyIdleUrgency(array $urgency, $today): array
    {
        if (!$this->updated_at) {
            return $urgency;
        }

        $updatedAt = clone $this->updated_at;
        $idleDays = (int)$updatedAt->startOfDay()->diffInDays($today, true);

        if ($idleDays >= 7) {
            $urgency['score'] = max($urgency['score'], 0.45);
        } elseif ($idleDays >= 3) {
            $urgency['score'] = max($urgency['score'], 0.22);
        } else {
            return $urgency;
        }

        $urgency['kind'] ??= 'idle';
        $urgency['label'] ??= 'چند روز بی‌تغیر';

        return $urgency;
    }

    protected function urgencyExemptState(): ?array
    {
        return null;
    }

    protected function applyExtraUrgency(array $urgency): array
    {
        return $urgency;
    }

    abstract protected function urgencyDeadline(): ?CarbonInterface;

    abstract protected function urgencyPriorityFloor(): float;
}