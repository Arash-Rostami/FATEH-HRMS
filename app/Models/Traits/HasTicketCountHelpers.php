<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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

    public static function getStatistics()
    {
        return Cache::remember('ticket_statistics', 600, function () {
            return DB::table('tickets')->selectRaw("
                COUNT(CASE WHEN status = 'open' THEN 1 END) AS open_count,
                COUNT(CASE WHEN status = 'in-progress' THEN 1 END) AS in_progress_count,
                COUNT(CASE WHEN status = 'closed' THEN 1 END) AS closed_count,
                COUNT(CASE WHEN status = 'open' AND priority = 'high' THEN 1 END) AS high_priority_count,
                ROUND(AVG(CASE WHEN status = 'closed' AND satisfaction_score IS NOT NULL AND satisfaction_score != 0 THEN satisfaction_score END), 1) AS average_satisfaction_score,
                ROUND(AVG(CASE WHEN status = 'closed' AND effectiveness IS NOT NULL AND effectiveness != 0 THEN effectiveness END), 1) AS average_effectiveness_score,
                COUNT(CASE WHEN completion_deadline IS NOT NULL
                           AND completion_date IS NOT NULL
                           AND completion_deadline < completion_date THEN 1 END) AS overdue_count,
                COUNT(CASE WHEN request_type = 'support' THEN 1 END) AS support_count,
                COUNT(CASE WHEN request_type = 'access' THEN 1 END) AS access_count
        ")->first();
        });
    }
}
