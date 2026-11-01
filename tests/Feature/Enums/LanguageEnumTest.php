<?php

namespace Tests\Feature\Enums;

use App\Enums\Language;
use Tests\TestCase;

class LanguageEnumTest extends TestCase
{
    public function test_cases_count_is_non_zero_and_stable(): void
    {
        $this->assertCount(89, Language::cases());
    }

    public function test_codes_returns_every_case_name_unsorted(): void
    {
        $codes = Language::codes();
        $this->assertCount(89, $codes);
        $this->assertSame('AF', $codes[0]);
        $this->assertSame('ZU', $codes[88]);
        $this->assertContains('FA', $codes);
        $this->assertSame(array_column(Language::cases(), 'name'), $codes);
    }

    public function test_labels_returns_sorted_unique_persian_values(): void
    {
        $labels = Language::labels();
        $this->assertCount(89, $labels);
        $sorted = $labels;
        sort($sorted);
        $this->assertSame($sorted, $labels);
        $this->assertContains('فارسی', $labels);
        $this->assertNotContains('FA', $labels);
    }

    public function test_to_array_returns_name_to_value_map_sorted_by_value(): void
    {
        $map = Language::toArray();
        $this->assertCount(89, $map);
        $this->assertSame('فارسی', $map['FA']);
        $this->assertSame('انگلیسی', $map['EN']);
        $this->assertSame('زولو', $map['ZU']);
        $asorted = $map;
        asort($asorted);
        $this->assertSame(array_keys($asorted), array_keys($map));
    }

    public function test_code_returns_name_for_a_known_label_and_null_for_unknown(): void
    {
        $this->assertSame('FA', Language::code('فارسی'));
        $this->assertSame('EN', Language::code('انگلیسی'));
        $this->assertNull(Language::code('nonexistent-language'));
        $this->assertNull(Language::code(''));
    }

    public function test_label_returns_value_for_a_known_code_regardless_of_case_and_null_for_unknown(): void
    {
        $this->assertSame('فارسی', Language::label('FA'));
        $this->assertSame('فارسی', Language::label('fa'));
        $this->assertSame('فارسی', Language::label('Fa'));
        $this->assertSame('انگلیسی', Language::label('en'));
        $this->assertSame('زولو', Language::label('zu'));
        $this->assertNull(Language::label('ZZ'));
        $this->assertNull(Language::label(''));
    }

    public function test_tryfrom_round_trips_known_value_and_throws_on_unknown(): void
    {
        $this->assertSame(Language::FA, Language::tryFrom('فارسی'));
        $this->assertSame('FA', Language::tryFrom('فارسی')->name);
        $this->assertNull(Language::tryFrom('nonexistent-language'));

        $threw = false;
        try {
            Language::from('nonexistent-language');
        } catch (\ValueError) {
            $threw = true;
        }
        $this->assertTrue($threw, 'Expected Language::from to throw for an unknown value.');
    }

    public function test_representative_cases_carry_expected_name_and_value(): void
    {
        $this->assertSame('AF', Language::AF->name);
        $this->assertSame('آفریکانس', Language::AF->value);
        $this->assertSame('FA', Language::FA->name);
        $this->assertSame('فارسی', Language::FA->value);
        $this->assertSame('EN', Language::EN->name);
        $this->assertSame('انگلیسی', Language::EN->value);
        $this->assertSame('ZU', Language::ZU->name);
        $this->assertSame('زولو', Language::ZU->value);
    }
}