<?php

namespace App\Services\User;

use App\Models\User;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** One Group per distinct key across every user's `extra->admin` (JSON object); auto-grows. Keys equal after trim + case-fold merge into one group (label = most frequent variant). User model untouched. Mirrors App\Services\Dms\DmsKeyGrouper, scoped to the single `extra` column at path `$.admin` — the catch-all bag the User::extra() setter already routes non-preference keys into. */
class UserKeyGrouper
{
    public const CACHE_KEY = 'user_dynamic_group_keys';
    public const CACHE_TTL = 900;

    public const MAX_KEYS_PER_OBJECT = 32;

    public const HIDDEN_PREFIX = '_';

    /** @return list<array{norm: string, label: string, variants: list<string>}> */
    public static function map(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function (): array {
            $numbers = self::numbersTable(self::MAX_KEYS_PER_OBJECT);

            $sql = str_replace(
                '{{numbers}}',
                $numbers,
                <<<'SQL'
                    SELECT CAST(k AS BINARY) AS k, COUNT(*) AS cnt
                    FROM (
                        SELECT JSON_UNQUOTE(JSON_EXTRACT(JSON_KEYS(extra, '$.admin'), CONCAT('$[', n, ']'))) AS k
                        FROM users
                        CROSS JOIN {{numbers}}
                        WHERE JSON_KEYS(extra, '$.admin') IS NOT NULL
                    ) AS merged
                    WHERE k IS NOT NULL
                    GROUP BY CAST(k AS BINARY)
                SQL,
            );

            return self::mergeRows(DB::select($sql));
        });
    }

    public static function mergeRows(iterable $rows): array
    {
        $byNorm = [];

        foreach ($rows as $row) {
            $orig = (string) $row->k;
            $cnt = (int) $row->cnt;
            $norm = mb_strtolower(trim($orig), 'UTF-8');

            if (! isset($byNorm[$norm])) {
                $byNorm[$norm] = ['label' => $orig, 'labelCnt' => $cnt, 'variants' => []];
            }

            $byNorm[$norm]['variants'][] = $orig;

            if ($cnt > $byNorm[$norm]['labelCnt']) {
                $byNorm[$norm]['label'] = $orig;
                $byNorm[$norm]['labelCnt'] = $cnt;
            }
        }

        $groups = [];
        foreach ($byNorm as $norm => $g) {
            $groups[] = ['norm' => $norm, 'label' => $g['label'], 'variants' => $g['variants']];
        }
        usort($groups, fn (array $a, array $b): int => strcmp($a['label'], $b['label']));

        return $groups;
    }

    public static function valueFor(User $record, array $variants): string
    {
        $admin = $record->extra['admin'] ?? [];

        if (is_array($admin)) {
            foreach ($variants as $key) {
                if (array_key_exists($key, $admin)) {
                    $value = $admin[$key];

                    if ($value === null) {
                        return '—';
                    }

                    return is_scalar($value)
                        ? (string) $value
                        : json_encode($value, JSON_UNESCAPED_UNICODE);
                }
            }
        }

        return '—';
    }

    /** Distinct values for a given group across all users — covers every variant spelling via COALESCE, mirroring valueFor(). */
    public static function distinctValues(array $variants): array
    {
        $expr = self::valueExpression($variants);

        return DB::table('users')
            ->selectRaw("DISTINCT ({$expr}) AS val")
            ->whereRaw("({$expr}) IS NOT NULL")
            ->pluck('val')
            ->filter()
            ->mapWithKeys(fn (string $v) => [$v => $v])
            ->all();
    }

    /** @return list<SelectFilter> — one per discovered key, options = that key's distinct values. */
    public static function filters(): array
    {
        return array_map(
            function (array $g): SelectFilter {
                $variants = $g['variants'];

                return SelectFilter::make(self::idFor($g['norm']))
                    ->label($g['label'])
                    ->options(fn (): array => self::distinctValues($variants))
                    ->query(function (Builder $query, array $data) use ($variants): void {
                        if (blank($data['value'] ?? null)) {
                            return;
                        }

                        self::applyFilter($query, $variants, $data['value']);
                    });
            },
            self::map(),
        );
    }

    public static function applyFilter(Builder $query, array $variants, string $value): Builder
    {
        $expr = self::valueExpression($variants);

        return $query->whereRaw("({$expr}) = ?", [$value]);
    }

    public static function isHidden(string $norm): bool
    {
        return str_starts_with($norm, self::HIDDEN_PREFIX);
    }

    /** @return list<Group> */
    public static function groups(): array
    {
        return array_map(
            function (array $g): Group {
                $variants = $g['variants'];

                return Group::make(self::idFor($g['norm']))
                    ->label($g['label'])
                    ->getTitleFromRecordUsing(fn ($record): string => self::valueFor($record, $variants))
                    ->getKeyFromRecordUsing(fn ($record): string => self::valueFor($record, $variants))
                    ->orderQueryUsing(fn ($query) => $query)
                    ->scopeQueryByKeyUsing(function ($query, $key) use ($variants) {
                        $expr = self::valueExpression($variants);

                        if ($key === '—' || $key === null || $key === '') {
                            return $query->whereRaw("({$expr}) IS NULL");
                        }

                        return $query->whereRaw("({$expr}) = ?", [$key]);
                    })
                    ->titlePrefixedWithLabel(false)
                    ->collapsible();
            },
            self::map(),
        );
    }

    protected static function valueExpression(array $variants): string
    {
        $parts = [];
        foreach ($variants as $key) {
            $parts[] = 'JSON_UNQUOTE(JSON_EXTRACT(`extra`, ' . self::jsonPath($key) . '))';
        }

        return 'COALESCE(' . implode(', ', $parts) . ')';
    }

    protected static function jsonPath(string $key): string
    {
        return "'$.admin.\"" . addcslashes($key, '"\'\\') . "\"'";
    }

    protected static function idFor(string $norm): string
    {
        return 'dyn_' . substr(sha1($norm), 0, 16);
    }

    protected static function numbersTable(int $count): string
    {
        $rows = array_map(fn (int $i): string => "SELECT {$i} AS n", range(0, $count - 1));

        return '(' . implode(' UNION ALL ', $rows) . ') AS numbers';
    }
}