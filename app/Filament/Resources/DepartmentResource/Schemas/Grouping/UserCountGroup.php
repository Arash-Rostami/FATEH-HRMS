<?php

namespace App\Filament\Resources\DepartmentResource\Schemas\Grouping;

use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;

class UserCountGroup
{
    private const SUBQUERY = <<<'SQL'
(
    SELECT COUNT(*)
    FROM `users`
    INNER JOIN `profiles`
        ON `profiles`.`user_id` = `users`.`id`
    WHERE `profiles`.`department_id` = `departments`.`code`
)
SQL;

    private const BUCKETS = [
        '0' => ['min' => 0, 'max' => 0, 'label' => 'بدون کاربر'],
        '1-3' => ['min' => 1, 'max' => 3, 'label' => '۱ تا ۳ کاربر'],
        '4-6' => ['min' => 4, 'max' => 6, 'label' => '۴ تا ۶ کاربر'],
        '7-10' => ['min' => 7, 'max' => 10, 'label' => '۷ تا ۱۰ کاربر'],
        '11-20' => ['min' => 11, 'max' => 20, 'label' => '۱۱ تا ۲۰ کاربر'],
        '20+' => ['min' => 21, 'max' => null, 'label' => 'بیشتر از ۲۰ کاربر'],
    ];

    public static function make(): Group
    {
        $expr = self::expression();

        return Group::make('users_count')
            ->label(__('resources/department/strings.fields.users_count'))
            ->groupQueryUsing(static fn(Builder $query): Builder => $query
                ->addSelect('departments.*')
                ->selectRaw("{$expr} as users_count"))
            ->scopeQueryByKeyUsing(static function (Builder $query, string $key) use ($expr): Builder {
                $bucket = self::BUCKETS[$key] ?? null;
                if ($bucket === null) return $query;
                return match (true) {
                    $bucket['max'] === null => $query->whereRaw("{$expr} >= ?", [$bucket['min']]),
                    $bucket['min'] === $bucket['max'] => $query->whereRaw("{$expr} = ?", [$bucket['min']]),
                    default => $query->whereRaw("{$expr} BETWEEN ? AND ?", [$bucket['min'], $bucket['max']]),
                };
            })
            ->orderQueryUsing(static function (Builder $query, string $direction) use ($expr): Builder {
                $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
                return $query->orderByRaw("{$expr} {$direction}");
            })
            ->getKeyFromRecordUsing(static fn($record): string => self::bucket((int)($record->users_count ?? 0))['key'])
            ->getTitleFromRecordUsing(static fn($record): string => self::bucket((int)($record->users_count ?? 0))['label'])
            ->titlePrefixedWithLabel(false)
            ->collapsible();
    }

    private static function bucket(int $count): array
    {
        foreach (self::BUCKETS as $key => $bucket) {
            if ($count < $bucket['min']) continue;
            if ($bucket['max'] !== null && $count > $bucket['max']) continue;
            return ['key' => $key, 'label' => $bucket['label']];
        }
        return ['key' => '0', 'label' => self::BUCKETS['0']['label']];
    }

    private static function expression(): string
    {
        return 'COALESCE(' . self::SUBQUERY . ', 0)';
    }
}
