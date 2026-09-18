<?php

namespace App\Enums;

use Carbon\Carbon;

enum ReminderRecurrence: string
{
    case None = 'none';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::None => 'بدون تکرار',
            self::Daily => 'روزانه',
            self::Weekly => 'هفتگی',
            self::Monthly => 'ماهانه',
        };
    }

    public function advance(Carbon $from): ?Carbon
    {
        if ($this === self::None) {
            return null;
        }

        $next = $from->copy();

        do {
            $next = match ($this) {
                self::Daily => $next->addDay(),
                self::Weekly => $next->addWeek(),
                self::Monthly => $next->addMonthNoOverflow(),
            };
        } while ($next->isPast());

        return $next;
    }
}
