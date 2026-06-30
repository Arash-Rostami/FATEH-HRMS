<?php

namespace App\Models\Traits;

trait HasTicketCountHelpers
{
    public static function getInProgressTicketCount(): int
    {
        return self::where('requester_id', auth()->id())
            ->where('status', 'in-progress')
            ->count();
    }

    public static function getOpenTicketCount(): int
    {
        return self::where('requester_id', auth()->id())
            ->where('status', 'open')
            ->count();
    }
}
