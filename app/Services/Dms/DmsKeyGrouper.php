<?php

namespace App\Services\Dms;

use App\Models\DMS;
use Filament\Tables\Grouping\Group;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** One Group per distinct key across every record's `extra`/`tags` (JSON objects); auto-grows. Keys equal after trim + case-fold merge into one group (label = most frequent variant). DMS model untouched. */
class DmsKeyGrouper
{
    public const CACHE_KEY = 'dms_dynamic_group_keys';
    public const CACHE_TTL = 900;

    /** Upper bound on keys per JSON object; sizes the numbers table that unnests keys. */
    public const MAX_KEYS_PER_OBJECT = 32;

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
                        SELECT JSON_UNQUOTE(JSON_EXTRACT(JSON_KEYS(extra), CONCAT('$[', n, ']'))) AS k
                        FROM dms
                        CROSS JOIN {{numbers}}
                        WHERE JSON_KEYS(extra) IS NOT NULL
                        UNION ALL
                        SELECT JSON_UNQUOTE(JSON_EXTRACT(JSON_KEYS(tags), CONCAT('$[', n, ']'))) AS k
                        FROM dms
                        CROSS JOIN {{numbers}}
                        WHERE JSON_KEYS(tags) IS NOT NULL
                    ) AS merged
                    WHERE k IS NOT NULL
                    GROUP BY CAST(k AS BINARY)
                SQL,
            );

            return self::mergeRows(DB::select($sql));
        });
    }

    /** Merge raw variant rows ({k, cnt}) into canonical groups: trim + case-fold, label = most frequent variant. Pure — split out of map() so the merge logic is testable without the MySQL-only query. */
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

    /** Record's value for a merged key (group bucket/title). Tries each variant byte-exactly across extra then tags; null/missing → '—'. */
    public static function valueFor(DMS $record, array $variants): string
    {
        foreach ($variants as $key) {
            foreach (['extra', 'tags'] as $column) {
                $map = $record->{$column};

                if (is_array($map) && array_key_exists($key, $map)) {
                    $value = $map[$key];

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
                    // id is an opaque hash, not a column — without this Filament appends `ORDER BY <hash>` → "Unknown column 'dyn_…'".
                    ->orderQueryUsing(fn ($query) => $query)
                    // "select all in group" runs `WHERE <hash> = <value>` → same crash. Rebuild the scope as a COALESCE over the variants' JSON_EXTRACT, mirroring valueFor.
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

    /** SQL COALESCE mirroring valueFor: first non-null JSON_UNQUOTE(JSON_EXTRACT(col, path)) across the variants, extra before tags. */
    protected static function valueExpression(array $variants): string
    {
        $parts = [];
        foreach ($variants as $key) {
            foreach (['extra', 'tags'] as $column) {
                $parts[] = 'JSON_UNQUOTE(JSON_EXTRACT(`' . $column . '`, ' . self::jsonPath($key) . '))';
            }
        }

        return 'COALESCE(' . implode(', ', $parts) . ')';
    }

    /** SQL JSON path literal for a key. `"` and `\` are escaped for the JSON path; `'` is escaped for the wrapping single-quoted SQL string — a key can't close the literal or inject SQL (the key is user-controlled via the KeyValue field). */
    protected static function jsonPath(string $key): string
    {
        return "'$.\"" . addcslashes($key, '"\'\\') . "\"'";
    }

    /** Stable, unique, ASCII-safe id per merged key. */
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