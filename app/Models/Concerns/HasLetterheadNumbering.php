<?php

namespace App\Models\Concerns;

use App\Models\Correspondence;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;
use Morilog\Jalali\CalendarUtils;

trait HasLetterheadNumbering
{
    public function preview(array $context = []): string
    {
        $root = $this->numberingRoot();
        $pattern = trim((string) $root->pattern);

        if (!self::hasSequence($pattern)) {
            return '';
        }

        $values = self::numberValues($context + ['type' => (string) config('ai.correspondence.types.letter.code', 'L')], self::numberPrefix($root, $context));

        return self::formatNumber($pattern, $values, self::nextSequence((array) ($root->counters ?? []), self::counterKey($pattern, $values), (int) $root->start_at));
    }

    public function lastNumber(): ?int
    {
        $root = $this->numberingRoot();
        $pattern = trim((string) $root->pattern);

        if (!self::hasSequence($pattern)) {
            return null;
        }

        return self::lastSequence($pattern, (array) ($root->counters ?? []), self::numberValues([], self::numberPrefix($root, [])));
    }

    public function nextNumber(array $context): array
    {
        if (DB::transactionLevel() < 1 || !$this->exists) {
            throw new LogicException('nextNumber must run inside the caller transaction on a stored letterhead.');
        }

        $root = $this->numberingRoot(true);
        $pattern = trim((string) $root->pattern);
        $unavailable = fn () => ValidationException::withMessages(['letterhead_id' => __('resources/correspondence/strings.errors.number_unavailable')]);

        if (!self::hasSequence($pattern)) {
            throw $unavailable();
        }

        $values = self::numberValues($context, self::numberPrefix($root, $context));
        $key = self::counterKey($pattern, $values);
        $counters = (array) ($root->counters ?? []);
        $seq = self::nextSequence($counters, $key, (int) $root->start_at);

        for ($attempt = 0; $attempt < 25; $attempt++, $seq++) {
            $number = self::formatNumber($pattern, $values, $seq);

            if (mb_strlen($number) > 64) {
                throw $unavailable();
            }

            try {
                if ($root->numberTaken($number)) {
                    continue;
                }

                $counters[$key] = $seq;
                $root->forceFill(['counters' => $counters])->save();
            } catch (QueryException $e) {
                if (($e->errorInfo[0] ?? null) !== '23000') {
                    throw $e;
                }

                continue;
            }

            return ['number' => $number, 'seq' => $seq, 'scope' => $key];
        }

        throw $unavailable();
    }

    public static function numberValues(array $context, string $prefix = ''): array
    {
        [$year, $month] = self::jalaliYearMonth($context['date'] ?? null);
        $word = function (mixed $value, string $fallback, bool $lower): string {
            $clean = is_scalar($value) ? preg_replace('/[^A-Za-z0-9_-]/', '', (string) $value) : '';
            $clean = $lower ? strtolower((string) $clean) : (string) $clean;

            return $clean === '' ? $fallback : $clean;
        };

        return [
            'PREFIX' => mb_substr(trim((string) preg_replace('/[|{}\x00-\x1F\x7F]/u', '', $prefix)), 0, 20),
            'YYYY' => sprintf('%04d', $year),
            'YY' => sprintf('%02d', $year % 100),
            'MM' => sprintf('%02d', $month),
            'DEPT' => $word($context['dept'] ?? null, 'gen', true),
            'TYPE' => $word($context['type'] ?? null, 'GEN', false),
        ];
    }

    public static function formatNumber(string $pattern, array $values, int $seq): string
    {
        return (string) preg_replace_callback(self::tokenRegex(), function (array $match) use ($values, $seq): string {
            $token = $match[1] ?? '';

            if ($token !== '') {
                return (string) $values[$token];
            }

            $width = (int) ($match[2] ?? 0);

            return $width > 0 ? str_pad((string) $seq, $width, '0', STR_PAD_LEFT) : (string) $seq;
        }, $pattern);
    }

    public static function counterTokens(string $pattern): array
    {
        preg_match_all(self::tokenRegex(), $pattern, $found, PREG_SET_ORDER);
        $present = array_unique(array_filter(array_map(fn (array $match) => $match[1] ?? '', $found), fn (string $token) => $token !== ''));

        return array_values(array_intersect(['PREFIX', 'YYYY', 'YY', 'MM', 'DEPT', 'TYPE'], $present));
    }

    public static function counterKey(string $pattern, array $values): string
    {
        $tokens = self::counterTokens($pattern);

        return $tokens === [] ? '-' : implode('|', array_map(fn (string $token) => $values[$token], $tokens));
    }

    public static function nextSequence(array $counters, string $key, int $startAt): int
    {
        return max((int) ($counters[$key] ?? 0), $startAt - 1) + 1;
    }

    public static function lastSequence(string $pattern, array $counters, array $values): ?int
    {
        $tokens = self::counterTokens($pattern);
        $last = null;

        foreach ($counters as $key => $seq) {
            $parts = $tokens === [] ? ['-'] : explode('|', (string) $key);

            if (count($parts) !== max(1, count($tokens)) || !is_numeric($seq)) {
                continue;
            }

            foreach ($tokens as $index => $token) {
                if (!in_array($token, ['DEPT', 'TYPE'], true) && $parts[$index] !== $values[$token]) {
                    continue 2;
                }
            }

            if ($tokens === [] && (string) $key !== '-') {
                continue;
            }

            $last = max($last ?? 0, (int) $seq);
        }

        return $last;
    }

    public static function hasSequence(string $pattern): bool
    {
        return preg_match('/\{SEQ(?::[1-9]\d?)?\}/', $pattern) === 1;
    }

    protected function numberTaken(string $number): bool
    {
        return Correspondence::withTrashed()->where('number', $number)->exists();
    }

    protected function numberingRoot(bool $lock = false): static
    {
        if ($this->parent_id === null && !$lock) {
            return $this;
        }

        $query = static::query()->whereKey($this->parent_id ?? $this->getKey());

        return ($lock ? $query->lockForUpdate() : $query)->firstOrFail();
    }

    private static function numberPrefix(self $root, array $context): string
    {
        return (string) ($context['prefix'] ?? data_get($root->config, 'prefix', ''));
    }

    private static function tokenRegex(): string
    {
        return '/\{(?:(PREFIX|DEPT|TYPE|YYYY|YY|MM)|SEQ(?::([1-9]\d?))?)\}/';
    }

    private static function jalaliYearMonth(mixed $date): array
    {
        if (is_string($date) && preg_match('#^(\d{4})/(\d{1,2})/(\d{1,2})$#', CalendarUtils::convertNumbers(trim($date), true), $parts) === 1) {
            return [(int) $parts[1], (int) $parts[2]];
        }

        $moment = $date instanceof CarbonInterface ? Carbon::instance($date)->setTimezone('Asia/Tehran') : Carbon::now('Asia/Tehran');
        [$year, $month] = CalendarUtils::toJalali($moment->year, $moment->month, $moment->day);

        return [$year, $month];
    }
}
