<?php

namespace Tests\Feature\Services;

use App\Services\PersianDateFieldService;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Utilities\Get;
use ReflectionMethod;
use Tests\TestCase;

class PersianDateFieldServiceTest extends TestCase
{
    private function assemble(mixed $year, mixed $month, mixed $day): ?string
    {
        return (new ReflectionMethod(PersianDateFieldService::class, 'assemble'))->invoke(null, $year, $month, $day);
    }

    private function toJalaliParts(mixed $raw): array
    {
        return (new ReflectionMethod(PersianDateFieldService::class, 'toJalaliParts'))->invoke(null, $raw);
    }

    public function test_assemble_builds_gregorian_date_string_from_valid_jalali_parts(): void
    {
        $this->assertSame('2024-03-24', $this->assemble(1403, 1, 5));
    }

    public function test_assemble_returns_null_when_any_part_is_missing(): void
    {
        $this->assertNull($this->assemble(null, 1, 5));
        $this->assertNull($this->assemble(1403, null, 5));
        $this->assertNull($this->assemble(1403, 1, null));
        $this->assertNull($this->assemble('', 1, 5));
    }

    public function test_assemble_throws_for_a_structurally_invalid_jalali_date(): void
    {
        $threw = false;
        try {
            $this->assemble(9999, 99, 99);
        } catch (\Throwable) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected assemble to throw for a structurally invalid Jalali date.');
    }

    public function test_to_jalali_parts_returns_correct_year_month_day_for_a_known_gregorian_date(): void
    {
        [$year, $month, $day] = $this->toJalaliParts('2024-03-24');

        $this->assertSame(1403, $year);
        $this->assertSame(1, $month);
        $this->assertSame(5, $day);
    }

    public function test_to_jalali_parts_returns_null_triplet_for_an_unparseable_value(): void
    {
        [$year, $month, $day] = $this->toJalaliParts('not-a-date');

        $this->assertNull($year);
        $this->assertNull($month);
        $this->assertNull($day);
    }

    public function test_make_with_a_carrier_rule_builds_without_throwing(): void
    {
        $rule = fn(Get $get, $record) => 'nullable';

        $group = PersianDateFieldService::make(prefix: 'deadline_date', carrierRule: $rule);

        $this->assertInstanceOf(FusedGroup::class, $group);
    }

    public function test_make_without_a_carrier_rule_builds_without_throwing(): void
    {
        $group = PersianDateFieldService::make(prefix: 'deadline_date');

        $this->assertInstanceOf(FusedGroup::class, $group);
    }
}