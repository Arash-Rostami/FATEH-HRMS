<?php

namespace App\Services\Reservation\Contracts;

use App\Models\Resource;
use App\Models\User;
use Carbon\Carbon;

final class BookingContext
{
    public function __construct(
        public readonly User $user,
        public readonly Resource $resource,
        public readonly Carbon $start,
        public readonly Carbon $end,
        public readonly bool $isFullDay,
        public readonly array $policies,
        public readonly ?array $recurrence = null,
        public readonly ?int $excludeId = null,
    ) {}

    public function isRange(): bool
    {
        return !$this->isFullDay && $this->start->diffInDays($this->end) >= 1;
    }
}
