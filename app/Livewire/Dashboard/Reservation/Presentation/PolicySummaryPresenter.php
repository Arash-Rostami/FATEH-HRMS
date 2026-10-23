<?php

namespace App\Livewire\Dashboard\Reservation\Presentation;

use App\Services\Reservation\ValidationService;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

class PolicySummaryPresenter
{
    private const FULL_WEEK = 7;
    private const WARN_RATIO = 0.8;
    private const GROUPS = ['time' => 'زمان‌بندی', 'duration' => 'مدت', 'quota' => 'سهمیه', 'other' => 'سایر'];
    private const GROUP_BY_ICON = ['event_upcoming' => 'time', 'schedule' => 'time', 'date_range' => 'time', 'alarm' => 'time', 'timelapse' => 'duration', 'stacks' => 'quota', 'event_busy' => 'quota'];

    public static function chips(array $policies, bool $isFullDay, string $type = '', ?int $personalCap = null): array
    {
        $chips = [];

        if ($type === 'meeting') {
            $chips[] = ['icon' => 'person', 'text' => 'منبع ملاقات = یک همکار؛ دعوت‌نامه در تقویم او ثبت می‌شود', 'tone' => 'info'];
        }

        $days = $policies['window_days'] ?? null;
        if ($days !== null && (int) $days > 0) {
            $chips[] = ['icon' => 'event_upcoming', 'text' => 'رزرو تا ' . convertToPersian((string) (int) $days) . ' روز آینده'];
        }

        $hours = $policies['window_hours'] ?? null;
        if ($hours !== null) {
            $chips[] = ['icon' => 'schedule', 'text' => (int) $hours > 0
                ? 'حداقل ' . convertToPersian((string) (int) $hours) . ' ساعت قبل از شروع'
                : 'بدون حداقل مهلت قبل از شروع'];
        }

        $allowedDays = $policies['allowed_days'] ?? null;
        if (is_array($allowedDays) && $allowedDays !== []) {
            $chips[] = ['icon' => 'date_range', 'text' => 'روزهای مجاز: ' . (count($allowedDays) < self::FULL_WEEK
                ? implode('، ', array_map(fn($day) => __("resources/policy/strings.days.{$day}"), $allowedDays))
                : 'همهٔ روزهای هفته')];
        }

        if (! $isFullDay) {
            $window = $policies['allowed_hours'] ?? null;
            if (is_array($window) && filled($window['start'] ?? null) && filled($window['end'] ?? null)) {
                $chips[] = ['icon' => 'alarm', 'text' => 'ساعت مجاز: ' . convertToPersian($window['start']) . ' تا ' . convertToPersian($window['end'])];
            }

            $bounds = TimeSlotPresenter::durationBounds($policies['min_duration_minutes'] ?? null, $policies['max_duration_minutes'] ?? null);
            if ($bounds !== null) {
                $chips[] = ['icon' => 'timelapse', 'text' => $bounds];
            }
        }

        $cap = $personalCap ?? $policies['max_per_user'] ?? null;
        if ($cap !== null) {
            $chips[] = ['icon' => 'stacks', 'text' => 'سقف رزرو ماهانه: ' . convertToPersian((string) (int) $cap) . ($personalCap !== null ? ' (سقف شخصی شما)' : '')];
        }

        $cancelCap = $policies['max_cancel_count'] ?? null;
        if ($cancelCap !== null) {
            $chips[] = ['icon' => 'event_busy', 'text' => 'سقف لغو در ' . convertToPersian((string) ValidationService::cancelWindowDays($policies)) . ' روز: ' . convertToPersian((string) max(0, (int) $cancelCap)) . ((int) $cancelCap < 1 ? ' (لغو مجاز نیست)' : '')];
        }

        $flags = [
            'allow_repeat' => ['repeat', 'رزرو تکراری', 'مجاز', 'مجاز نیست', true],
            'allow_full_day' => ['wb_sunny', 'رزرو تمام‌روز', 'مجاز', 'مجاز نیست', ! $isFullDay],
            'allow_partial_cancel' => ['call_split', 'لغو یک رزرو از سری تکراری', 'فقط همان رزرو لغو می‌شود', 'کل سری لغو می‌شود', true],
            'allow_overlap_release' => ['autorenew', 'رزرو آزادشده', 'منبع دوباره قابل رزرو می‌شود', 'منبع همچنان اشغال می‌ماند', true],
        ];
        foreach ($flags as $key => [$icon, $label, $yes, $no, $applies]) {
            if ($applies && array_key_exists($key, $policies)) {
                $chips[] = ['icon' => $icon, 'text' => "{$label}: " . ($policies[$key] ? $yes : $no), 'tone' => $policies[$key] ? 'neutral' : 'warning'];
            }
        }

        if (! empty($policies['requires_approval'])) {
            $chips[] = ['icon' => 'verified_user', 'text' => 'رزرو پس از تأیید مدیر نهایی می‌شود', 'tone' => 'warning'];
        }

        $rangeDays = $policies['max_range_days'] ?? null;
        if ($rangeDays !== null) {
            $chips[] = ['icon' => 'calendar_view_month', 'text' => 'حداکثر رزرو بلندمدت (فقط مدیر): ' . convertToPersian((string) (int) $rangeDays) . ' روز'];
        }

        $lastMinute = $policies['last_minute_hours'] ?? null;
        if ($lastMinute !== null && (int) $lastMinute > 0) {
            $chips[] = ['icon' => 'bolt', 'text' => 'رزرو لحظه‌آخری تا ' . convertToPersian((string) (int) $lastMinute) . ' ساعت مانده به شروع'];
        }

        return $chips;
    }

    public static function grouped(array $chips): array
    {
        $byGroup = collect($chips)->groupBy(fn(array $chip) => self::GROUP_BY_ICON[$chip['icon']] ?? 'other');

        return collect(self::GROUPS)
            ->mapWithKeys(fn(string $label, string $key) => [$label => $byGroup->get($key, collect())->values()->all()])
            ->filter()
            ->all();
    }

    public static function quotaLevel(int $count, int $max): string
    {
        return match (true) {
            $count >= $max => 'full',
            $count >= $max * self::WARN_RATIO => 'warn',
            default => 'ok',
        };
    }

    public static function quotaReset(Carbon $selected): ?array
    {
        [$monthStart, $monthEnd] = jalaliMonthBounds(now());

        if ($selected->lt($monthStart) || $selected->gt($monthEnd)) {
            return null;
        }

        $start = $monthEnd->copy()->addDay()->startOfDay();

        return [
            'days' => max(1, (int) ceil(now()->diffInDays($start))),
            'date' => convertToPersian(toJalali($start, 'j F')),
        ];
    }
}
