<?php


namespace App\Services;

use Carbon\Carbon;
use Closure;
use DateTimeInterface;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\FusedGroup;
use Morilog\Jalali\Jalalian;

final class PersianDateFieldService
{
    /** @var array<int, string> */
    private const MONTH_NAMES = [
        1 => 'فروردین',
        2 => 'اردیبهشت',
        3 => 'خرداد',
        4 => 'تیر',
        5 => 'مرداد',
        6 => 'شهریور',
        7 => 'مهر',
        8 => 'آبان',
        9 => 'آذر',
        10 => 'دی',
        11 => 'بهمن',
        12 => 'اسفند',
    ];

    private readonly string $label;
    private int $yearTo;

    public function __construct(
        private readonly string $prefix = 'birthdate',
        string                  $label = '',
        private readonly bool   $required = true,
        private readonly int    $yearFrom = 1330,
        ?int                    $yearTo = null,
        private readonly bool   $fullWidth = false,
        private readonly bool   $gap = true,
    )
    {
        $this->label = $label ?: __("resources/profile/strings.form.{$prefix}");
        $this->yearTo = $yearTo ?? Jalalian::now()->getYear();
    }

    public function build(): FusedGroup
    {
        return FusedGroup::make(
            [
                $this->hiddenField($this->prefix),
                $this->yearSelect($this->prefix),
                $this->monthSelect($this->prefix),
                $this->daySelect($this->prefix),
            ]
        )
            ->label($this->label)
            ->gap($this->gap)
            ->columns(3)
            ->extraFieldWrapperAttributes(['class' => 'no-shell'])
            ->when($this->fullWidth, fn($c) => $c->columnSpanFull());
    }

    public static function make(
        string $prefix = 'birthdate',
        string $label = '',
        bool   $required = true,
        int    $yearFrom = 1330,
        ?int   $yearTo = null,
        bool   $fullWidth = false,
        bool   $gap = true,
    ): FusedGroup
    {
        return (new self($prefix, $label, $required, $yearFrom, $yearTo, $fullWidth, $gap))->build();
    }

    private static function assemble(mixed $year, mixed $month, mixed $day): ?string
    {
        if (!filled($year) || !filled($month) || !filled($day)) {
            return null;
        }

        return Jalalian::fromFormat('Y/m/d', sprintf('%04d/%02d/%02d', $year, $month, $day))
            ->toCarbon()
            ->format('Y-m-d');
    }

    /**
     * @param array<int, int|string>|Closure $options
     */
    private function baseSelect(string $name, string $placeholder, array|Closure $options, string $prefix): Select
    {
        return Select::make($name)
            ->placeholder($placeholder)
            ->options($options)
            ->native(false)
            ->required($this->required)
            ->preload()
            ->live()
            ->optionsLimit(200)
            ->dehydrated(false)
            ->validationMessages(['required' => "{$placeholder} الزامی است"])
            ->afterStateUpdated(fn(callable $set, callable $get) => $this->syncDate($set, $get, $prefix));
    }

    /**
     * @return array<int, int>
     */
    private function dayOptions(int $month): array
    {
        $days = range(1, $this->daysInMonth($month));

        return array_combine($days, $days);
    }

    private function daySelect(string $prefix): Select
    {
        return $this->baseSelect(
            name: "{$prefix}_day",
            placeholder: 'روز',
            options: fn(callable $get) => $this->dayOptions((int)$get("{$prefix}_month")),
            prefix: $prefix,
        );
    }

    private function daysInMonth(int $month): int
    {
        if ($month < 1 || $month > 12) {
            return 31;
        }

        // Months 1–6: 31 days | Months 7–11: 30 days | Month 12: 29 days (non-leap)
        return match ($month) {
            12 => 29,
            default => $month <= 6 ? 31 : 30,
        };
    }

    private function hiddenField(string $prefix): Hidden
    {
        return Hidden::make($prefix)
            ->dehydrated(true)
            ->afterStateHydrated(function (callable $set, mixed $state) use ($prefix): void {
                if (blank($state)) {
                    return;
                }

                [$year, $month, $day] = self::toJalaliParts($state);

                if (blank($year)) {
                    return;
                }

                $set("{$prefix}_year", $year);
                $set("{$prefix}_month", $month);
                $set("{$prefix}_day", $day);
            });
    }

    private function monthSelect(string $prefix): Select
    {
        return $this->baseSelect(
            name: "{$prefix}_month",
            placeholder: 'ماه',
            options: self::MONTH_NAMES,
            prefix: $prefix,
        );
    }

    private function syncDate(callable $set, callable $get, string $prefix): void
    {
        $set($prefix, self::assemble(
            $get("{$prefix}_year"),
            $get("{$prefix}_month"),
            $get("{$prefix}_day")
        ));
    }

    /**
     * @return array{0: ?int, 1: ?int, 2: ?int}
     */
    private static function toJalaliParts(mixed $raw): array
    {
        try {
            $carbon = $raw instanceof DateTimeInterface
                ? Carbon::instance($raw)
                : Carbon::parse($raw);

            $jalali = Jalalian::fromCarbon($carbon);

            return [$jalali->getYear(), $jalali->getMonth(), $jalali->getDay()];
        } catch (\Throwable) {
            return [null, null, null];
        }
    }

    private function yearSelect(string $prefix): Select
    {
        $years = range($this->yearFrom, $this->yearTo);

        return $this->baseSelect(
            name: "{$prefix}_year",
            placeholder: 'سال',
            options: array_combine($years, $years),
            prefix: $prefix,
        );
    }
}
