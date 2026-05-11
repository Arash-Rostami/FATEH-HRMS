<?php

namespace App\Models\Traits;

use Carbon\Carbon;

trait HasPrunableStatus
{
    abstract public function getPruneDays(): int;

    public function getPruneWarnDays(): int
    {
        return 20;
    }

    public function isOverdue(): bool
    {
        $pruneAt = $this->pruneAt();

        return $pruneAt && now()->greaterThanOrEqualTo($pruneAt);
    }

    public function pruneAt(): ?Carbon
    {
        return $this->deleted_at
            ? $this->deleted_at->copy()->addDays($this->getPruneDays())
            : null;
    }

    public function pruneRemainingInterval(): ?string
    {
        if (!$this->deleted_at) {
            return null;
        }

        if ($this->isOverdue()) {
            return 'در آستانه حذف';
        }

        return now()
            ->diffAsCarbonInterval($this->pruneAt())
            ->forHumans([
                'parts' => 2,
                'join' => true,
                'minimumUnit' => 'hour',
            ]);
    }

    public function pruneState(): ?string
    {
        if (!$this->deleted_at) {
            return null;
        }

        if ($this->isOverdue()) {
            return 'danger';
        }

        return $this->deleted_at->diffInDays(now()) >= $this->getPruneWarnDays()
            ? 'warning'
            : 'safe';
    }

    public function pruneStatusColor(): string
    {
        return match ($this->pruneState()) {
            'danger' => 'danger',
            'warning' => 'warning',
            default => 'info',
        };
    }

    public function pruneStatusText(): ?string
    {
        if (!$this->deleted_at) {
            return null;
        }

        return match ($this->pruneState()) {
            'danger' => 'در آستانه حذف',
            'warning' => "تا حذف خودکار: {$this->pruneRemainingInterval()}",
            default => "وضعیت ایمن تا: {$this->pruneRemainingInterval()}",
        };
    }
}
