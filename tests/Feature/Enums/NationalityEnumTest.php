<?php

namespace Tests\Feature\Enums;

use App\Enums\Nationality;
use Tests\TestCase;
use ValueError;

class NationalityEnumTest extends TestCase
{
    public function test_cases_returns_non_empty_stable_list(): void
    {
        $cases = Nationality::cases();

        $this->assertNotEmpty($cases);
        $this->assertCount(191, $cases);
        $this->assertSame(Nationality::AF, $cases[0]);
        $this->assertSame(Nationality::ZW, $cases[array_key_last($cases)]);
    }

    public function test_representative_sample_case_values_are_pinned(): void
    {
        $this->assertSame('افغان', Nationality::AF->value);
        $this->assertSame('ایرانی', Nationality::IR->value);
        $this->assertSame('آمریکایی', Nationality::US->value);
        $this->assertSame('زیمبابوه‌ای', Nationality::ZW->value);
    }

    public function test_code_returns_name_for_known_label(): void
    {
        $this->assertSame('IR', Nationality::code('ایرانی'));
        $this->assertSame('AF', Nationality::code('افغان'));
        $this->assertSame('US', Nationality::code('آمریکایی'));
    }

    public function test_code_returns_null_for_unknown_label(): void
    {
        $this->assertNull(Nationality::code('ملیت نامعتبر'));
        $this->assertNull(Nationality::code(''));
    }

    public function test_codes_returns_all_case_names(): void
    {
        $codes = Nationality::codes();

        $this->assertCount(191, $codes);
        $this->assertSame('AF', $codes[0]);
        $this->assertSame('ZW', $codes[array_key_last($codes)]);
        $this->assertContains('IR', $codes);
        $this->assertSame(count(Nationality::cases()), count(array_unique($codes)));
    }

    public function test_label_returns_value_for_uppercase_code(): void
    {
        $this->assertSame('ایرانی', Nationality::label('IR'));
        $this->assertSame('افغان', Nationality::label('AF'));
        $this->assertSame('زیمبابوه‌ای', Nationality::label('ZW'));
    }

    public function test_label_normalizes_lowercase_code_to_uppercase(): void
    {
        $this->assertSame('ایرانی', Nationality::label('ir'));
        $this->assertSame('افغان', Nationality::label('af'));
    }

    public function test_label_returns_null_for_unknown_code(): void
    {
        $this->assertNull(Nationality::label('ZZ'));
        $this->assertNull(Nationality::label(''));
    }

    public function test_labels_returns_sorted_unique_values(): void
    {
        $labels = Nationality::labels();

        $this->assertCount(191, $labels);
        $sorted = $labels;
        sort($sorted);
        $this->assertSame($sorted, $labels);
        $this->assertContains('ایرانی', $labels);
        $this->assertSame(count(array_unique($labels)), count($labels));
    }

    public function test_to_array_returns_name_to_value_map_sorted_by_value(): void
    {
        $map = Nationality::toArray();

        $this->assertCount(191, $map);
        $this->assertSame('ایرانی', $map['IR']);
        $this->assertSame('افغان', $map['AF']);
        $this->assertSame('زیمبابوه‌ای', $map['ZW']);
        $values = array_values($map);
        $sorted = $values;
        sort($sorted);
        $this->assertSame($sorted, $values);
    }

    public function test_try_from_round_trips_known_value(): void
    {
        $this->assertSame(Nationality::IR, Nationality::tryFrom('ایرانی'));
        $this->assertSame('IR', Nationality::tryFrom('ایرانی')->name);
    }

    public function test_try_from_returns_null_for_unknown_value(): void
    {
        $this->assertNull(Nationality::tryFrom('ملیت نامعتبر'));
        $this->assertNull(Nationality::tryFrom(''));
    }

    public function test_from_throws_value_error_for_unknown_value(): void
    {
        $threw = false;
        try {
            Nationality::from('ملیت نامعتبر');
        } catch (ValueError) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected Nationality::from to throw ValueError for an unknown value.');
    }
}