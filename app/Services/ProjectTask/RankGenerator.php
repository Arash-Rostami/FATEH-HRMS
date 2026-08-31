<?php

namespace App\Services\ProjectTask;

use InvalidArgumentException;
use RuntimeException;

class RankGenerator
{
    private const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyz';
    private const MAX_LENGTH = 40;

    public static function between(?string $before, ?string $after): string
    {
        if ($before !== null && $after !== null && $before >= $after) {
            throw new InvalidArgumentException('The before rank must sort strictly before the after rank.');
        }

        $rank = self::midpoint($before ?? '', $after);

        if (mb_strlen($rank) > self::MAX_LENGTH) {
            throw new RuntimeException('Rank column exhausted — rebalance required before another insert here.');
        }

        return $rank;
    }

    public static function rebalanceInsert(array $orderedIds, int $insertIndex): array
    {
        $ranks = self::sequence(count($orderedIds) + 1);
        $withPlaceholder = $orderedIds;
        array_splice($withPlaceholder, $insertIndex, 0, [null]);

        $assignments = [];

        foreach ($withPlaceholder as $position => $id) {
            if ($id !== null) {
                $assignments[$id] = $ranks[$position];
            }
        }

        return ['assignments' => $assignments, 'insertRank' => $ranks[$insertIndex]];
    }

    public static function sequence(int $count): array
    {
        if ($count < 1) {
            return [];
        }

        $ranks = [];
        $lower = null;
        for ($i = 0; $i < $count; $i++) {
            $ranks[] = self::between($lower, null);
            $lower = $ranks[$i];
        }

        return $ranks;
    }

    private static function midpoint(string $lower, ?string $upper): string
    {
        $alphabetLength = mb_strlen(self::ALPHABET);
        $lastIndex = $alphabetLength - 1;
        $result = '';
        $i = 0;

        while (true) {
            $lowerDigit = $i < mb_strlen($lower) ? self::digitAt($lower, $i) : 0;
            $upperDigit = ($upper !== null && $i < mb_strlen($upper)) ? self::digitAt($upper, $i) : $lastIndex;

            if ($lowerDigit === $upperDigit) {
                $result .= self::ALPHABET[$lowerDigit];
                $i++;
                continue;
            }

            if ($upperDigit - $lowerDigit > 1) {
                $mid = intdiv($lowerDigit + $upperDigit, 2);

                return $result . self::ALPHABET[$mid];
            }

            $result .= self::ALPHABET[$lowerDigit];
            $i++;
            $upper = null;
        }
    }

    private static function digitAt(string $value, int $position): int
    {
        return strpos(self::ALPHABET, mb_substr($value, $position, 1)) ?: 0;
    }
}
