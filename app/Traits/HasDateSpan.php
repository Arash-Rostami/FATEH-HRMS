<?php

namespace App\Traits;

use Livewire\Attributes\Locked;

trait HasDateSpan
{
    public string $dateBasis = 'updated';
    public ?int $fromYear = null;
    public ?int $fromMonth = null;
    public ?int $fromDay = null;
    public ?int $toYear = null;
    public ?int $toMonth = null;
    public ?int $toDay = null;

    #[Locked]
    public array $appliedDateParts = [];

    #[Locked]
    public string $appliedBasis = 'updated';

    abstract protected function afterDateSpanChange(): void;

    public function setDateBasis(string $basis): void
    {
        if (!in_array($basis, ['created', 'updated'], true) || $this->dateBasis === $basis) {
            return;
        }

        $this->dateBasis = $basis;

        if ($this->dateSpan() !== null && $this->appliedBasis !== $basis) {
            $this->appliedBasis = $basis;
            $this->afterDateSpanChange();
        }
    }

    public function applyDateSpan(): void
    {
        if (jalaliSpanFromParts($this->fromYear, $this->fromMonth, $this->fromDay, $this->toYear, $this->toMonth, $this->toDay) === null) {
            $this->dispatch('toast', message: 'بازهٔ انتخاب‌شده نامعتبر است.', type: 'warning');
            return;
        }

        $this->appliedBasis = $this->dateBasis;
        $this->appliedDateParts = $this->dateParts();
        $this->afterDateSpanChange();
    }

    public function clearDateSpan(): void
    {
        if ($this->dateParts() === [null, null, null, null, null, null]) {
            return;
        }

        $this->fromYear = $this->fromMonth = $this->fromDay = $this->toYear = $this->toMonth = $this->toDay = null;
        $this->appliedDateParts = $this->dateParts();
        $this->afterDateSpanChange();
    }

    public function dateSpanActive(): bool
    {
        return $this->dateSpan() !== null;
    }

    protected function dateParts(): array
    {
        return [$this->fromYear, $this->fromMonth, $this->fromDay, $this->toYear, $this->toMonth, $this->toDay];
    }

    protected function dateSpan(): ?array
    {
        if (count($this->appliedDateParts) < 6) {
            return null;
        }

        $span = jalaliSpanFromParts(...$this->appliedDateParts);

        return $span === null ? null : [$this->appliedBasis === 'created' ? 'created_at' : 'updated_at', ...$span];
    }
}
