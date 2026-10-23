<?php

namespace App\Livewire\Dashboard\Reservation\Presentation;

use App\Enums\ResourceType;
use App\Filament\Resources\UserResource\Enums\UserType;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Morilog\Jalali\CalendarUtils;
use Morilog\Jalali\Jalalian;
use Throwable;

class DailyPresenter
{
    public const SCOPES = ['day', 'week', 'month', 'custom'];
    public const MAX_CUSTOM_DAYS = 93;
    public const PER_PAGE = 30;
    public const MAX_ROWS = 600;

    public static function quickTabs(): array
    {
        return array_map(fn(array $tab) => [
            'id' => $tab['id'],
            'icon' => $tab['icon'],
            'label' => $tab['label'],
            'alpine' => "\$wire.setDailyQuick('{$tab['id']}')",
            'activeExpr' => "\$wire.daily.preset === '{$tab['id']}'",
        ], [
            ['id' => 'today', 'icon' => 'today', 'label' => 'امروز'],
            ['id' => 'tomorrow', 'icon' => 'event', 'label' => 'فردا'],
            ['id' => 'week', 'icon' => 'date_range', 'label' => 'این هفته'],
            ['id' => 'next_week', 'icon' => 'next_week', 'label' => 'هفته بعد'],
            ['id' => 'month', 'icon' => 'calendar_month', 'label' => 'این ماه'],
            ['id' => 'next_month', 'icon' => 'edit_calendar', 'label' => 'ماه بعد'],
            ['id' => 'custom', 'icon' => 'tune', 'label' => 'بازه دلخواه'],
        ]);
    }

    public static function defaults(string $date, string $type): array
    {
        $today = now()->toDateString();

        return [
            'scope' => 'day',
            'anchor' => $date ?: $today,
            'preset' => $date === $today ? 'today' : null,
            'type' => $type,
            'search' => '',
            'mine' => false,
            'limit' => self::PER_PAGE,
            'fromYear' => null, 'fromMonth' => null, 'fromDay' => null,
            'toYear' => null, 'toMonth' => null, 'toDay' => null,
        ];
    }

    public static function scope(array $state): string
    {
        return in_array($state['scope'] ?? null, self::SCOPES, true) ? $state['scope'] : 'day';
    }

    public static function anchor(array $state): Carbon
    {
        try {
            $anchor = Carbon::parse($state['anchor'] ?? null)->startOfDay();
        } catch (Throwable) {
            return Carbon::today();
        }

        return $anchor->between(Carbon::create(1950), Carbon::create(2100)) ? $anchor : Carbon::today();
    }

    public static function withQuick(array $state, string $key): array
    {
        if (! in_array($key, array_column(static::quickTabs(), 'id'), true)) {
            return $state;
        }

        [$scope, $anchor] = static::quick($key, Carbon::today());

        return [...$state, 'scope' => $scope, 'anchor' => $anchor->toDateString(), 'preset' => $key, 'limit' => self::PER_PAGE];
    }

    public static function withShift(array $state, int $direction): array
    {
        $scope = static::scope($state);

        if ($scope === 'custom' || ! in_array($direction, [-1, 1], true)) {
            return $state;
        }

        return [...$state, 'anchor' => static::shift($scope, static::anchor($state), $direction)->toDateString(), 'preset' => null, 'limit' => self::PER_PAGE];
    }

    public static function monthOptions(): array
    {
        return collect(range(1, 12))->mapWithKeys(fn(int $month) => [$month => (new Jalalian(1405, $month, 1))->format('F')])->all();
    }

    public static function yearOptions(): array
    {
        $year = Jalalian::now()->getYear();

        return range($year - 2, $year + 1);
    }

    public static function rangeProblem(array $state): ?array
    {
        $sides = ['from' => 'شروع', 'to' => 'پایان'];
        $parts = ['Day' => 'روز', 'Month' => 'ماه', 'Year' => 'سال'];

        foreach ($sides as $side => $sideLabel) {
            foreach ($parts as $part => $partLabel) {
                if (blank($state[$side . $part] ?? null)) {
                    return ["daily.{$side}{$part}", "{$partLabel} {$sideLabel} را انتخاب کنید."];
                }
            }

            if (! CalendarUtils::checkDate((int) $state["{$side}Year"], (int) $state["{$side}Month"], (int) $state["{$side}Day"], true)) {
                return ["daily.{$side}Day", "تاریخ {$sideLabel} معتبر نیست (برای مثال روز ۳۱ در ماه‌های ۳۰ روزه)."];
            }
        }

        $span = jalaliSpanFromParts($state['fromYear'], $state['fromMonth'], $state['fromDay'], $state['toYear'], $state['toMonth'], $state['toDay']);

        if ($span === null) {
            return ['daily.toDay', 'تاریخ پایان باید بعد از تاریخ شروع (یا برابر آن) باشد.'];
        }

        return $span[0]->diffInDays($span[1]) > self::MAX_CUSTOM_DAYS
            ? ['daily.toDay', 'حداکثر بازه ' . convertToPersian((string) self::MAX_CUSTOM_DAYS) . ' روز است؛ تاریخ پایان را نزدیک‌تر کنید.']
            : null;
    }

    public static function withCustomRange(array $state): ?array
    {
        return static::customRange($state) === null ? null : [...$state, 'scope' => 'custom', 'preset' => 'custom', 'limit' => self::PER_PAGE];
    }

    public static function withoutCustomRange(array $state): array
    {
        return static::withQuick([...$state, 'fromYear' => null, 'fromMonth' => null, 'fromDay' => null, 'toYear' => null, 'toMonth' => null, 'toDay' => null], 'today');
    }

    public static function quick(string $key, Carbon $today): array
    {
        return match ($key) {
            'tomorrow' => ['day', $today->copy()->addDay()],
            'week' => ['week', $today->copy()],
            'next_week' => ['week', $today->copy()->addWeek()],
            'month' => ['month', $today->copy()],
            'next_month' => ['month', static::shiftMonth($today, 1)],
            'custom' => ['custom', $today->copy()],
            default => ['day', $today->copy()],
        };
    }

    public static function shift(string $scope, Carbon $anchor, int $direction): Carbon
    {
        return match ($scope) {
            'day' => $anchor->copy()->addDays($direction),
            'week' => $anchor->copy()->addWeeks($direction),
            'month' => static::shiftMonth($anchor, $direction),
            default => $anchor->copy(),
        };
    }

    public static function range(string $scope, Carbon $anchor, array $parts = []): array
    {
        return match ($scope) {
            'week' => [$anchor->copy()->startOfWeek(Carbon::SATURDAY), $anchor->copy()->endOfWeek(Carbon::FRIDAY)],
            'month' => static::monthRange($anchor),
            'custom' => static::customRange($parts) ?? [$anchor->copy()->startOfDay(), $anchor->copy()->endOfDay()],
            default => [$anchor->copy()->startOfDay(), $anchor->copy()->endOfDay()],
        };
    }

    public static function label(string $scope, Carbon $from, Carbon $to): string
    {
        return convertToPersian(match ($scope) {
            'month' => toJalali($from, 'F Y'),
            'week', 'custom' => 'از ' . toJalali($from, 'j F') . ' تا ' . toJalali($to, 'j F Y'),
            default => toJalali($from, 'l، j F Y'),
        });
    }

    public static function groups(Collection $reservations, int $viewerId, Carbon $from): array
    {
        $types = collect(ResourceType::cases())->keyBy('value');
        $windowStart = $from->toDateString();

        return $reservations
            ->groupBy(fn(Reservation $r) => max($r->start_time->toDateString(), $windowStart))
            ->map(fn(Collection $rows, string $day) => [
                'key' => $day,
                'heading' => convertToPersian(toJalali(Carbon::parse($day), 'l، j F Y')),
                'rows' => $rows->map(fn(Reservation $r) => static::row($r, $viewerId, $types[$r->resource?->type] ?? null))->all(),
            ])
            ->values()
            ->all();
    }

    public static function messagePresets(array $presets): array
    {
        return array_map(fn(array $preset) => ['icon' => $preset['icon'], 'label' => $preset['label']], $presets);
    }

    private static function row(Reservation $reservation, int $viewerId, ?ResourceType $type): array
    {
        $user = $reservation->user;
        $visible = $user && $user->isActive() && $user->type !== UserType::Guest->value;

        return [
            'reservation' => $reservation,
            'resource' => $reservation->resource,
            'type' => $type,
            'image' => $reservation->resource?->display_image_url,
            'mine' => $reservation->user_id === $viewerId,
            'person' => $visible ? $user : null,
            'anonymous' => $user && $user->type !== UserType::Guest->value ? 'کاربر غیرفعال' : 'کاربر مهمان',
            'dept' => $visible ? $user->profile?->department?->displayLabel() : null,
            'time' => static::time($reservation),
            'contact' => $visible && $reservation->user_id !== $viewerId ? static::contact($user, $reservation->resource) : null,
        ];
    }

    private static function time(Reservation $reservation): string
    {
        $sameDay = $reservation->start_time->isSameDay($reservation->end_time);

        if ($reservation->is_full_day) {
            return $sameDay
                ? 'تمام روز'
                : convertToPersian('از ' . toJalali($reservation->start_time, 'j F') . ' تا ' . toJalali($reservation->end_time, 'j F'));
        }

        return $sameDay
            ? convertToPersian(toJalali($reservation->start_time, 'H:i') . ' تا ' . toJalali($reservation->end_time, 'H:i'))
            : convertToPersian('از ' . toJalali($reservation->start_time, 'j F H:i') . ' تا ' . toJalali($reservation->end_time, 'j F H:i'));
    }

    private static function contact(User $user, ?Resource $resource): array
    {
        $hidden = presence($user->presence)->isObscured();
        $office = static::dialable(config('app.office_phone'));
        $ext = static::dialable($resource?->metadata['extension'] ?? null);
        $mobile = static::dialable($user->sms_number);

        return [
            'chatUrl' => route('contact', ['open' => $user->id]),
            'extTel' => $hidden || ! $ext || ! $office ? null : $office . ',' . $ext,
            'mobileTel' => $hidden ? null : $mobile,
        ];
    }

    private static function dialable(mixed $value): ?string
    {
        $clean = preg_replace('/[^\d+]/', '', (string) $value);

        return $clean === '' ? null : $clean;
    }

    private static function jalali(Carbon $date): Jalalian
    {
        return Jalalian::fromCarbon($date);
    }

    private static function shiftMonth(Carbon $anchor, int $direction): Carbon
    {
        $jalali = static::jalali($anchor);
        $index = $jalali->getYear() * 12 + ($jalali->getMonth() - 1) + $direction;

        return (new Jalalian(intdiv($index, 12), $index % 12 + 1, 1))->toCarbon()->startOfDay();
    }

    private static function monthRange(Carbon $anchor): array
    {
        $jalali = static::jalali($anchor);
        $first = new Jalalian($jalali->getYear(), $jalali->getMonth(), 1);
        $last = new Jalalian($jalali->getYear(), $jalali->getMonth(), $first->getMonthDays());

        return [$first->toCarbon()->startOfDay(), $last->toCarbon()->endOfDay()];
    }

    private static function customRange(array $parts): ?array
    {
        $span = jalaliSpanFromParts(
            $parts['fromYear'] ?? null, $parts['fromMonth'] ?? null, $parts['fromDay'] ?? null,
            $parts['toYear'] ?? null, $parts['toMonth'] ?? null, $parts['toDay'] ?? null,
        );

        return $span === null || $span[0]->diffInDays($span[1]) > self::MAX_CUSTOM_DAYS ? null : $span;
    }
}
