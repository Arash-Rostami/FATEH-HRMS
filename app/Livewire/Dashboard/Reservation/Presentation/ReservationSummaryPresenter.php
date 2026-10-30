<?php

namespace App\Livewire\Dashboard\Reservation\Presentation;

use App\Enums\ResourceType;
use App\Livewire\Dashboard\Reservation\Main;
use App\Models\Resource;
use Carbon\Carbon;

class ReservationSummaryPresenter
{
    public static function occurrences(string $date, string $pattern, int $count, ?int $windowDays, ?array $allowedDays, bool $elevated): array
    {
        $interval = $pattern === 'weekly' ? 7 : 1;
        $count = max(2, min(52, $count));
        $first = Carbon::parse($date);
        $today = Carbon::now()->startOfDay();
        $horizon = $windowDays !== null ? Carbon::now()->addDays($windowDays)->endOfDay() : null;

        $items = [];
        for ($i = 0; $i < $count; $i++) {
            $day = $first->copy()->addDays($i * $interval);
            $items[] = [
                'day' => $day,
                'ok' => $day >= $today
                    && ($horizon === null || $day <= $horizon)
                    && ($elevated || $allowedDays === null || in_array(strtolower($day->englishDayOfWeek), $allowedDays, true)),
            ];
        }

        return $items;
    }

    public static function summary(Main $c, Resource $resource): string
    {
        $isFullDay = ResourceType::tryFrom($c->activeTab)?->isFullDay() ?? false;
        $start = $isFullDay ? Carbon::parse($c->date)->startOfDay() : Carbon::parse("{$c->date} {$c->startTime}");

        $lines = [
            'تاریخ: ' . toJalali($start, 'l، j F Y'),
            $isFullDay ? 'مدت: تمام روز' : "ساعت: {$c->startTime} تا {$c->endTime}",
        ];

        if ($c->isRecurring && $c->allowsRepeat) {
            $times = self::repeatCount($c, $resource, $start);
            $lines[] = $times <= 1
                ? 'تکرار: فقط همین یک‌بار (بقیهٔ تکرارها خارج از قوانین رزرو است)'
                : 'تکرار: ' . ($c->recurPattern === 'weekly' ? 'هفتگی' : 'روزانه') . '، تا ' . $times . ' بار (تکرارهای مغایر با قوانین یا تداخل‌دار ثبت نمی‌شوند)';
        }

        if ($c->activeTab === 'meeting' && self::invitesColleague($resource)) {
            $lines[] = 'اطلاع‌رسانی: دعوتنامه در تقویم همکار ثبت می‌شود';
        }

        return convertToPersian(implode("\n", $lines));
    }

    private static function repeatCount(Main $c, Resource $resource, Carbon $start): int
    {
        $days = $c->policies['allowed_days'] ?? null;
        $days = is_array($days) ? array_values(array_map('strtolower', $days)) : null;
        $own = $resource->metadata['available_days'] ?? null;

        if (filled($own)) {
            $days = array_values(array_intersect($days ?? $own, $own));
        }

        $windowDays = $c->policies['window_days'] ?? null;
        $items = self::occurrences($c->date, $c->recurPattern, (int) $c->recurCount, $windowDays === null ? null : (int) $windowDays, $days, auth()->user()->hasElevatedRole());

        $usage = $c->activeLimitUsage;
        $remaining = $usage === null ? null : max(0, $usage['max'] - $usage['count']);
        [$monthStart, $monthEnd] = $remaining === null ? [null, null] : jalaliMonthBounds(Carbon::parse($c->date));
        $relaxHours = $c->lastMinuteHours;
        $cutoff = $relaxHours === null ? null : now()->addHours($relaxHours);

        $total = 0;
        foreach ($items as $item) {
            if (! $item['ok']) {
                continue;
            }

            if ($remaining !== null && $item['day']->between($monthStart, $monthEnd)) {
                $relaxed = $cutoff !== null && $item['day']->copy()->setTimeFrom($start)->lte($cutoff);

                if ($remaining <= 0 && ! $relaxed) {
                    continue;
                }

                $remaining--;
            }

            $total++;
        }

        return $total;
    }

    private static function invitesColleague(Resource $resource): bool
    {
        $related = $resource->relationLoaded('relatedUser') ? $resource->relatedUser : null;

        return $related !== null && $related->id !== auth()->id();
    }
}
