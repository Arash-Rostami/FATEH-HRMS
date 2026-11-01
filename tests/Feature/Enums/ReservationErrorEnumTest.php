<?php

namespace Tests\Feature\Enums;

use App\Enums\ReservationError;
use Tests\TestCase;

class ReservationErrorEnumTest extends TestCase
{
    private const EXPECTED_VALUES = [
        'ERR-001', 'ERR-002', 'ERR-003', 'ERR-004', 'ERR-005', 'ERR-006',
        'ERR-007', 'ERR-008', 'ERR-009', 'ERR-010', 'ERR-011', 'ERR-012',
        'ERR-013', 'ERR-014', 'ERR-015', 'ERR-016', 'ERR-017', 'ERR-018',
        'ERR-019', 'ERR-020', 'ERR-021', 'ERR-022', 'ERR-023', 'ERR-024',
        'ERR-025',
    ];

    public function test_cases_count_matches_expected_set(): void
    {
        $this->assertSame(count(self::EXPECTED_VALUES), count(ReservationError::cases()));
    }

    public function test_every_case_value_is_unique_and_backed_string(): void
    {
        $values = array_map(fn (ReservationError $case) => $case->value, ReservationError::cases());

        $this->assertSame(count($values), count(array_unique($values)));
        $this->assertSame(self::EXPECTED_VALUES, $values);
    }

    public function test_try_from_returns_case_for_every_known_value(): void
    {
        foreach (self::EXPECTED_VALUES as $value) {
            $this->assertSame($value, ReservationError::tryFrom($value)->value);
        }
    }

    public function test_try_from_returns_null_for_unknown_or_empty_value(): void
    {
        $this->assertNull(ReservationError::tryFrom('ERR-999'));
        $this->assertNull(ReservationError::tryFrom(''));
        $this->assertNull(ReservationError::tryFrom('err-001'));
    }

    public function test_from_returns_case_for_valid_value_and_throws_for_invalid_value(): void
    {
        $this->assertSame('ERR-001', ReservationError::from('ERR-001')->value);
        $this->assertSame('ERR-022', ReservationError::from('ERR-022')->value);

        $threw = false;
        try {
            ReservationError::from('ERR-999');
        } catch (\ValueError) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected from() to throw for an invalid value.');
    }

    public function test_message_for_argless_cases_is_prefixed_value_followed_by_static_text(): void
    {
        $argless = [
            ReservationError::UserInactive,
            ReservationError::NoPermission,
            ReservationError::FullDayForbidden,
            ReservationError::RecurForbidden,
            ReservationError::CancelLimitBooking,
            ReservationError::ActiveLimitReached,
            ReservationError::ResourceTaken,
            ReservationError::TimeConflict,
            ReservationError::NotActive,
            ReservationError::CancelForbidden,
            ReservationError::CancelLimitReached,
            ReservationError::InvalidTimeRange,
            ReservationError::PastBooking,
            ReservationError::ResourceInactive,
            ReservationError::DataCorruption,
            ReservationError::TypeInactive,
        ];

        foreach ($argless as $case) {
            $message = $case->message();
            $this->assertStringStartsWith("[{$case->value}] ", $message);
            $this->assertStringNotContainsString('{', $message);
            $this->assertStringNotContainsString('$args', $message);
        }
    }

    public function test_message_window_exceeded_interpolates_first_arg_as_day_count(): void
    {
        $message = ReservationError::WindowExceeded->message(7);

        $this->assertSame('[ERR-003] تاریخ انتخابی خارج از بازه مجاز (حداکثر 7 روز آینده) است.', $message);
    }

    public function test_message_early_booking_interpolates_first_arg_as_hour_threshold(): void
    {
        $message = ReservationError::EarlyBooking->message(2);

        $this->assertSame('[ERR-004] رزرو باید حداقل 2 ساعت قبل از زمان شروع ثبت شود.', $message);
    }

    public function test_message_day_not_allowed_interpolates_first_arg_as_day_label(): void
    {
        $message = ReservationError::DayNotAllowed->message('شنبه');

        $this->assertSame('[ERR-005] رزرو در روز شنبه برای این نوع منبع مجاز نیست.', $message);
    }

    public function test_message_too_short_and_too_long_interpolate_first_arg_as_minutes(): void
    {
        $this->assertSame('[ERR-007] حداقل مدت رزرو 30 دقیقه است.', ReservationError::TooShort->message(30));
        $this->assertSame('[ERR-008] حداکثر مدت رزرو 120 دقیقه است.', ReservationError::TooLong->message(120));
    }

    public function test_message_hour_not_allowed_interpolates_both_args_as_hour_bounds(): void
    {
        $message = ReservationError::HourNotAllowed->message('08:00', '18:00');

        $this->assertSame('[ERR-009] رزرو فقط بین ساعت 08:00 تا 18:00 مجاز است.', $message);
    }

    public function test_message_prefix_uses_enum_value_for_every_case(): void
    {
        foreach (ReservationError::cases() as $case) {
            $args = in_array($case, [
                ReservationError::WindowExceeded,
                ReservationError::EarlyBooking,
                ReservationError::DayNotAllowed,
                ReservationError::TooShort,
                ReservationError::TooLong,
                ReservationError::HourNotAllowed,
                ReservationError::ResourceDayNotAllowed,
                ReservationError::ResourceHourNotAllowed,
                ReservationError::RangeTooLong,
            ], true) ? ['x', 'y'] : [];

            $this->assertStringStartsWith("[{$case->value}] ", $case->message(...$args));
        }
    }

    public function test_message_resource_day_not_allowed_interpolates_first_arg_as_day_label(): void
    {
        $message = ReservationError::ResourceDayNotAllowed->message('شنبه');

        $this->assertSame('[ERR-023] این منبع در روز شنبه قابل رزرو نیست.', $message);
    }

    public function test_message_resource_hour_not_allowed_interpolates_both_args_as_hour_bounds(): void
    {
        $message = ReservationError::ResourceHourNotAllowed->message('08:00', '18:00');

        $this->assertSame('[ERR-024] این منبع فقط بین ساعت 08:00 تا 18:00 قابل رزرو است.', $message);
    }

    public function test_legend_returns_entry_for_every_case_with_policy_and_hint_keys(): void
    {
        $legend = ReservationError::legend();

        $this->assertSame(self::EXPECTED_VALUES, array_keys($legend));

        foreach (self::EXPECTED_VALUES as $code) {
            $this->assertArrayHasKey('policy', $legend[$code]);
            $this->assertArrayHasKey('hint', $legend[$code]);
            $this->assertNotEmpty($legend[$code]['hint']);
        }
    }

    public function test_legend_policy_is_em_dash_for_non_field_bound_cases(): void
    {
        $legend = ReservationError::legend();

        foreach (['ERR-001', 'ERR-002', 'ERR-014', 'ERR-015', 'ERR-016', 'ERR-018', 'ERR-019', 'ERR-020', 'ERR-021'] as $code) {
            $this->assertSame('—', $legend[$code]['policy'], "Policy for {$code} should be the em-dash placeholder.");
        }
    }

    public function test_throw_raises_exception_carrying_prefixed_message(): void
    {
        $threw = false;
        $captured = '';

        try {
            ReservationError::PastBooking->throw();
        } catch (\Exception $e) {
            $threw = true;
            $captured = $e->getMessage();
        }

        $this->assertTrue($threw, 'Expected throw() to raise an exception.');
        $this->assertSame(ReservationError::PastBooking->message(), $captured);
    }

    public function test_throw_propagates_interpolated_args_into_exception_message(): void
    {
        try {
            ReservationError::WindowExceeded->throw(5);
            $this->fail('Expected throw() to raise an exception.');
        } catch (\Exception $e) {
            $this->assertSame(ReservationError::WindowExceeded->message(5), $e->getMessage());
            $this->assertStringContainsString('5 روز', $e->getMessage());
        }
    }
}