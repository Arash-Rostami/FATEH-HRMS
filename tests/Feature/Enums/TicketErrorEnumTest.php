<?php

namespace Tests\Feature\Enums;

use App\Enums\TicketError;
use Tests\TestCase;

class TicketErrorEnumTest extends TestCase
{
    private const EXPECTED_CASES = [
        'EffectivenessRequired' => 'ERR-T001',
    ];

    private const EXPECTED_MESSAGES = [
        'ERR-T001' => 'برای بستن تیکت، ثبت اثربخشی توسط تخصیص‌گیرنده یا مدیر واحد الزامی است.',
    ];

    public function test_cases_count_matches_expected_set(): void
    {
        $this->assertSame(count(self::EXPECTED_CASES), count(TicketError::cases()));
    }

    public function test_every_case_value_is_unique_and_backed_string(): void
    {
        $values = array_map(fn (TicketError $case) => $case->value, TicketError::cases());

        $this->assertSame(count($values), count(array_unique($values)));
        $this->assertSame(array_values(self::EXPECTED_CASES), $values);
    }

    public function test_message_returns_value_prefixed_persian_text(): void
    {
        foreach (TicketError::cases() as $case) {
            $expected = "[{$case->value}] " . self::EXPECTED_MESSAGES[$case->value];

            $this->assertSame($expected, $case->message(), "Message mismatch for case {$case->name}.");
        }
    }

    public function test_throw_raises_exception_with_case_message(): void
    {
        foreach (TicketError::cases() as $case) {
            $threw = false;
            try {
                $case->throw();
            } catch (\Exception $e) {
                $threw = true;
                $this->assertSame($case->message(), $e->getMessage(), "Exception message mismatch for case {$case->name}.");
            }

            $this->assertTrue($threw, "Expected throw() to raise for case {$case->name}.");
        }
    }

    public function test_try_from_returns_case_for_valid_value_and_null_for_invalid_value(): void
    {
        foreach (self::EXPECTED_CASES as $value) {
            $this->assertSame($value, TicketError::tryFrom($value)->value);
        }

        $this->assertNull(TicketError::tryFrom('ERR-T999'));
        $this->assertNull(TicketError::tryFrom(''));
        $this->assertNull(TicketError::tryFrom('nonexistent_error'));
    }

    public function test_from_returns_case_for_valid_value_and_throws_for_invalid_value(): void
    {
        $first = array_key_first(self::EXPECTED_CASES);
        $value = self::EXPECTED_CASES[$first];

        $this->assertSame($value, TicketError::from($value)->value);

        $threw = false;
        try {
            TicketError::from('ERR-T999');
        } catch (\ValueError) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected from() to throw for an invalid value.');
    }
}