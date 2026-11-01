<?php

namespace Tests\Feature\Services;

use App\Services\CityService;
use Tests\TestCase;

class CityServiceTest extends TestCase
{
    public function test_all_returns_a_non_empty_list_of_cities(): void
    {
        $cities = CityService::all();

        $this->assertIsArray($cities);
        $this->assertNotEmpty($cities);
        $this->assertSame(array_keys($cities), range(0, count($cities) - 1));
    }

    public function test_all_contains_every_entry_as_a_non_empty_string(): void
    {
        foreach (CityService::all() as $city) {
            $this->assertIsString($city);
            $this->assertNotSame('', $city);
        }
    }

    public function test_all_preserves_cross_province_duplicate_names_verbatim_without_deduplicating(): void
    {
        $cities = CityService::all();
        $counts = array_count_values($cities);

        foreach (['محمودآباد', 'نصرآباد', 'دولت آباد', 'محمدآباد', 'صالح آباد', 'حاجی آباد', 'نورآباد', 'فیروزآباد', 'اردکان', 'رودبار', 'خرم آباد', 'مهاجران'] as $shared) {
            $this->assertArrayHasKey($shared, $counts, "Expected shared name {$shared} to be present.");
            $this->assertGreaterThan(1, $counts[$shared], "Expected shared name {$shared} to appear in multiple provinces.");
        }

        $this->assertGreaterThan(count(array_unique($cities)), count($cities));
    }

    public function test_all_returns_well_known_provincial_capitals(): void
    {
        $cities = CityService::all();

        foreach (['تهران', 'مشهد', 'شیراز', 'اصفهان', 'تبریز', 'کرج', 'یزد', 'کرمان', 'اهواز', 'رشت'] as $expected) {
            $this->assertContains($expected, $cities);
        }
    }

    public function test_all_returns_the_same_array_instance_on_repeated_calls(): void
    {
        $first = CityService::all();
        $second = CityService::all();

        $this->assertSame($first, $second);
    }

    public function test_all_covers_all_thirty_one_provincial_capitals_in_the_constant(): void
    {
        $cities = CityService::all();

        $capitals = [
            'تبریز', 'ارومیه', 'اردبیل', 'اصفهان', 'کرج', 'ایلام', 'بوشهر',
            'تهران', 'شهرکرد', 'بیرجند', 'مشهد', 'بجنورد', 'اهواز', 'زنجان',
            'سمنان', 'زاهدان', 'شیراز', 'قزوین', 'قم', 'سنندج', 'کرمان',
            'کرمانشاه', 'یاسوج', 'گرگان', 'رشت', 'خرم آباد', 'ساری', 'اراک',
            'بندرعباس', 'همدان', 'یزد',
        ];

        foreach ($capitals as $capital) {
            $this->assertContains($capital, $cities);
        }
    }
}