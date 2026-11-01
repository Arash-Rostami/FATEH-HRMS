<?php

namespace Tests\Feature\Services;

use App\Services\HolidayService;
use Tests\TestCase;

class HolidayServiceTest extends TestCase
{
    public function test_get_all_returns_the_full_holidays_map(): void
    {
        $all = HolidayService::getAll();

        $this->assertIsArray($all);
        $this->assertNotEmpty($all);
    }

    public function test_get_all_returns_immutable_constant_reference(): void
    {
        $this->assertSame(HolidayService::getAll(), HolidayService::getAll());
    }

    public function test_get_all_keys_are_jalali_dated_strings(): void
    {
        foreach (HolidayService::getAll() as $date => $entries) {
            $this->assertIsString($date);
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $date);
        }
    }

    public function test_get_all_entries_carry_title_type_and_hijri_keys(): void
    {
        foreach (HolidayService::getAll() as $entries) {
            $this->assertNotEmpty($entries);
            foreach ($entries as $entry) {
                $this->assertArrayHasKey('title', $entry);
                $this->assertArrayHasKey('type', $entry);
                $this->assertArrayHasKey('hijri', $entry);
                $this->assertIsString($entry['title']);
                $this->assertContains($entry['type'], ['ملی', 'مذهبی']);
            }
        }
    }

    public function test_get_holidays_for_date_returns_entries_for_known_date(): void
    {
        $entries = HolidayService::getHolidaysForDate('1405-01-12');

        $this->assertCount(1, $entries);
        $this->assertSame('روز جمهوری اسلامی', $entries[0]['title']);
        $this->assertSame('ملی', $entries[0]['type']);
        $this->assertNull($entries[0]['hijri']);
    }

    public function test_get_holidays_for_date_returns_multiple_entries_when_date_has_overlapping_holidays(): void
    {
        $entries = HolidayService::getHolidaysForDate('1405-01-01');

        $this->assertCount(2, $entries);
        $this->assertSame('جشن نوروز/جشن سال نو', $entries[0]['title']);
        $this->assertSame('ملی', $entries[0]['type']);
        $this->assertNull($entries[0]['hijri']);

        $this->assertSame('عید سعید فطر', $entries[1]['title']);
        $this->assertSame('مذهبی', $entries[1]['type']);
        $this->assertSame('۱ شوال', $entries[1]['hijri']);
    }

    public function test_get_holidays_for_date_returns_empty_array_for_unknown_date(): void
    {
        $this->assertSame([], HolidayService::getHolidaysForDate('1300-07-07'));
    }

    public function test_get_holidays_for_date_returns_empty_array_for_empty_string(): void
    {
        $this->assertSame([], HolidayService::getHolidaysForDate(''));
    }

    public function test_get_holidays_for_date_is_consistent_with_get_all_for_known_date(): void
    {
        $date = '1406-03-14';
        $this->assertSame(
            HolidayService::getAll()[$date],
            HolidayService::getHolidaysForDate($date)
        );
    }

    public function test_get_holidays_for_date_does_not_mutate_the_underlying_map(): void
    {
        $before = HolidayService::getAll();
        HolidayService::getHolidaysForDate('1405-01-01');
        $after = HolidayService::getAll();

        $this->assertSame($before, $after);
    }
}