<?php

namespace Tests\Feature\Enums;

use App\Enums\FavoriteColor;
use Tests\TestCase;

class FavoriteColorEnumTest extends TestCase
{
    public function test_cases_returns_all_twenty_nine_cases_indexed_by_name(): void
    {
        $cases = FavoriteColor::cases();

        $this->assertCount(29, $cases);
        $this->assertSame(FavoriteColor::cases(), array_values($cases));
    }

    public function test_each_case_value_is_a_unique_lowercase_hex_color_string(): void
    {
        $values = [];
        foreach (FavoriteColor::cases() as $case) {
            $this->assertIsString($case->value);
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $case->value);
            $values[] = $case->value;
        }

        $this->assertSame($values, array_unique($values), 'FavoriteColor hex values must be unique.');
    }

    public function test_get_color_returns_the_case_value_for_every_case(): void
    {
        foreach (FavoriteColor::cases() as $case) {
            $this->assertSame($case->value, $case->getColor());
        }
    }

    public function test_get_label_returns_a_persian_display_name_for_every_case(): void
    {
        $labels = [];
        foreach (FavoriteColor::cases() as $case) {
            $label = $case->getLabel();
            $this->assertNotEmpty($label);
            $this->assertMatchesRegularExpression('/\p{Arabic}/u', $label, "{$case->name} label must be Persian text");
            $labels[] = $label;
        }

        $this->assertSame($labels, array_unique($labels), 'FavoriteColor labels must be unique so options are distinguishable in the select.');
    }

    public function test_get_label_is_display_only_and_does_not_change_the_stored_hex_value(): void
    {
        foreach (FavoriteColor::cases() as $case) {
            $case->getLabel();
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $case->value);
            $this->assertSame($case->value, $case->getColor());
        }
    }

    public function test_try_from_round_trips_every_case_value(): void
    {
        foreach (FavoriteColor::cases() as $case) {
            $this->assertSame($case, FavoriteColor::tryFrom($case->value));
        }
    }

    public function test_from_round_trips_every_case_value(): void
    {
        foreach (FavoriteColor::cases() as $case) {
            $this->assertSame($case, FavoriteColor::from($case->value));
        }
    }

    public function test_try_from_returns_null_for_an_unknown_value(): void
    {
        $this->assertNull(FavoriteColor::tryFrom('#0000000'));
        $this->assertNull(FavoriteColor::tryFrom('not-a-color'));
        $this->assertNull(FavoriteColor::tryFrom(''));
    }

    public function test_from_throws_value_error_for_an_unknown_value(): void
    {
        $threw = false;
        try {
            FavoriteColor::from('nope');
        } catch (\ValueError) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected from() to throw ValueError for an unknown value.');
    }
}