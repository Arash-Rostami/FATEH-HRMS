<?php

namespace Tests\Feature\Traits;

use App\Traits\HasTimelineMonths;
use Tests\TestCase;

class HasTimelineMonthsTest extends TestCase
{
    private function host(): object
    {
        return new class {
            use HasTimelineMonths;
        };
    }

    private function item(?string $date, int $id): object
    {
        return (object) ['date' => $date, 'id' => $id];
    }

    public function test_months_returns_empty_collection_for_empty_input(): void
    {
        $this->assertCount(0, $this->host()->months(collect([]), 'date'));
    }

    public function test_months_filters_out_items_with_blank_date(): void
    {
        $items = collect([
            $this->item('2026-08-01', 1),
            $this->item(null, 2),
            $this->item('', 3),
        ]);

        $this->assertCount(1, $this->host()->months($items, 'date'));
    }

    public function test_months_dedupes_by_jalali_key_and_sorts_desc_by_raw_date(): void
    {
        $items = collect([
            $this->item('2026-08-01', 1),
            $this->item('2026-06-01', 2),
            $this->item('2026-08-01', 3),
        ]);

        $result = $this->host()->months($items, 'date');

        $this->assertCount(2, $result);
        $this->assertSame(['2026-08-01', '2026-06-01'], $result->pluck('sort')->all());
        $result->each(fn (array $row) => $this->assertNotEmpty($row['key']));
    }

    public function test_months_collapses_same_jalali_month_into_single_key(): void
    {
        $items = collect([
            $this->item('2026-08-01', 1),
            $this->item('2026-08-15', 2),
            $this->item('2026-06-01', 3),
        ]);

        $keys = $this->host()->months($items, 'date')->pluck('key')->all();

        $this->assertCount(2, $keys);
        $this->assertSame(2, count(array_unique($keys)));
    }

    public function test_months_uses_the_supplied_date_field(): void
    {
        $items = collect([
            (object) ['created_at' => '2026-08-01', 'date' => null, 'id' => 1],
        ]);

        $this->assertCount(1, $this->host()->months($items, 'created_at'));
        $this->assertCount(0, $this->host()->months($items, 'date'));
    }
}