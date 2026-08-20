<?php

namespace App\Values;

final class CalendarLayout
{
    public const GRID_START_MINUTES = 360;

    public const DAY_END_MINUTES = 1440;

    public static function pack(array $pills): array
    {
        if (empty($pills)) {
            return [];
        }

        usort(
            $pills,
            fn($a, $b) => [$a['start_minutes'], -$a['duration_minutes']]
                <=> [$b['start_minutes'], -$b['duration_minutes']]
        );

        $columns = [];
        foreach ($pills as &$p) {
            $end = min($p['start_minutes'] + $p['duration_minutes'], self::DAY_END_MINUTES);
            $p['_end'] = $end;
            $col = null;
            foreach ($columns as $i => $colEnd) {
                if ($colEnd <= $p['start_minutes']) {
                    $columns[$i] = $end;
                    $col = $i;
                    break;
                }
            }
            if ($col === null) {
                $col = count($columns);
                $columns[] = $end;
            }
            $p['col'] = $col;
        }
        unset($p);

        $n = count($pills);
        $i = 0;
        while ($i < $n) {
            $j = $i;
            $clusterMaxEnd = $pills[$i]['_end'];
            while ($j + 1 < $n && $pills[$j + 1]['start_minutes'] < $clusterMaxEnd) {
                $j++;
                $clusterMaxEnd = max($clusterMaxEnd, $pills[$j]['_end']);
            }
            $span = 1;
            for ($k = $i; $k <= $j; $k++) {
                $span = max($span, $pills[$k]['col'] + 1);
            }
            for ($k = $i; $k <= $j; $k++) {
                $pills[$k]['span'] = $span;
            }
            $i = $j + 1;
        }

        foreach ($pills as &$p) {
            unset($p['_end']);
        }
        unset($p);

        return $pills;
    }
}