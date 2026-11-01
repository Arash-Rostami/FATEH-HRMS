<?php

namespace Tests\Feature\Services\ProjectTask;

use App\Services\ProjectTask\RankGenerator;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class RankGeneratorTest extends TestCase
{
    public function test_between_null_and_null_returns_a_midpoint_rank(): void
    {
        $rank = RankGenerator::between(null, null);

        $this->assertNotSame('', $rank);
        $this->assertLessThanOrEqual(40, strlen($rank));
    }

    public function test_between_is_correctly_ordered_against_its_neighbors(): void
    {
        $first = RankGenerator::between(null, null);
        $before = RankGenerator::between(null, $first);
        $after = RankGenerator::between($first, null);

        $this->assertLessThan($first, $before);
        $this->assertGreaterThan($first, $after);
    }

    public function test_between_adjacent_single_characters_extends_length_and_stays_ordered(): void
    {
        $rank = RankGenerator::between('a', 'b');

        $this->assertGreaterThan('a', $rank);
        $this->assertLessThan('b', $rank);
        $this->assertGreaterThan(1, strlen($rank));
    }

    public function test_between_throws_when_before_is_not_strictly_less_than_after(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RankGenerator::between('m', 'm');
    }

    public function test_between_throws_when_before_is_greater_than_after(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RankGenerator::between('z', 'a');
    }

    public function test_repeated_inserts_before_the_same_upper_bound_stay_ordered(): void
    {
        $upper = 'm';
        $ranks = [];
        $cursor = null;

        for ($i = 0; $i < 20; $i++) {
            $rank = RankGenerator::between($cursor, $upper);
            $ranks[] = $rank;
            $cursor = $rank;
        }

        $sorted = $ranks;
        sort($sorted, SORT_STRING);
        $this->assertSame($sorted, $ranks);

        foreach ($ranks as $rank) {
            $this->assertLessThan($upper, $rank);
        }
    }

    public function test_between_throws_runtime_exception_once_the_bounded_width_is_exhausted(): void
    {
        $this->expectException(RuntimeException::class);

        $before = str_repeat('a', 40);
        $after = str_repeat('a', 39) . 'b';

        RankGenerator::between($before, $after);
    }

    public function test_sequence_returns_the_requested_count_in_ascending_order(): void
    {
        $ranks = RankGenerator::sequence(5);

        $this->assertCount(5, $ranks);

        $sorted = $ranks;
        sort($sorted, SORT_STRING);
        $this->assertSame($sorted, $ranks);
        $this->assertSame(array_unique($ranks), $ranks);
    }

    public function test_sequence_of_zero_or_negative_returns_empty_array(): void
    {
        $this->assertSame([], RankGenerator::sequence(0));
        $this->assertSame([], RankGenerator::sequence(-3));
    }

    public function test_rebalance_insert_places_new_rank_at_the_requested_index_in_ascending_order(): void
    {
        $result = RankGenerator::rebalanceInsert([10, 20, 30], 1);

        $ordered = [$result['assignments'][10], $result['insertRank'], $result['assignments'][20], $result['assignments'][30]];
        $sorted = $ordered;
        sort($sorted, SORT_STRING);

        $this->assertSame($sorted, $ordered);
        $this->assertArrayNotHasKey(0, $result['assignments']);
    }

    public function test_rebalance_insert_at_start_sorts_before_every_existing_id(): void
    {
        $result = RankGenerator::rebalanceInsert([1, 2, 3], 0);

        $this->assertLessThan($result['assignments'][1], $result['insertRank']);
        $this->assertLessThan($result['assignments'][2], $result['insertRank']);
        $this->assertLessThan($result['assignments'][3], $result['insertRank']);
    }

    public function test_rebalance_insert_at_end_sorts_after_every_existing_id(): void
    {
        $result = RankGenerator::rebalanceInsert([1, 2, 3], 3);

        $this->assertGreaterThan($result['assignments'][1], $result['insertRank']);
        $this->assertGreaterThan($result['assignments'][2], $result['insertRank']);
        $this->assertGreaterThan($result['assignments'][3], $result['insertRank']);
    }

    public function test_rebalance_insert_with_empty_list_returns_a_single_rank_and_no_assignments(): void
    {
        $result = RankGenerator::rebalanceInsert([], 0);

        $this->assertSame([], $result['assignments']);
        $this->assertNotSame('', $result['insertRank']);
    }
}
