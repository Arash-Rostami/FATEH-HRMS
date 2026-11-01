<?php

namespace Tests\Feature\Enums;

use App\Enums\ReservationStatus;
use Tests\TestCase;

class ReservationStatusEnumTest extends TestCase
{
    public function test_cases_returns_all_four_cases_with_correct_backed_values(): void
    {
        $cases = ReservationStatus::cases();

        $this->assertCount(4, $cases);
        $this->assertSame('active', ReservationStatus::Active->value);
        $this->assertSame('released', ReservationStatus::Released->value);
        $this->assertSame('cancelled_user', ReservationStatus::CancelledUser->value);
        $this->assertSame('cancelled_admin', ReservationStatus::CancelledAdmin->value);
    }

    public function test_from_and_try_from_resolve_backed_value_to_case(): void
    {
        $this->assertSame(ReservationStatus::Active, ReservationStatus::from('active'));
        $this->assertSame(ReservationStatus::Released, ReservationStatus::from('released'));
        $this->assertSame(ReservationStatus::CancelledUser, ReservationStatus::from('cancelled_user'));
        $this->assertSame(ReservationStatus::CancelledAdmin, ReservationStatus::from('cancelled_admin'));

        $this->assertSame(ReservationStatus::Active, ReservationStatus::tryFrom('active'));
        $this->assertNull(ReservationStatus::tryFrom('nonexistent'));
        $this->assertNull(ReservationStatus::tryFrom(''));
    }

    public function test_from_throws_value_error_for_invalid_value(): void
    {
        $threw = false;
        try {
            ReservationStatus::from('invalid');
        } catch (\ValueError) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected from() to throw ValueError for an invalid backed value.');
    }

    public function test_get_label_returns_persian_label_for_every_case(): void
    {
        $this->assertSame('فعال', ReservationStatus::Active->getLabel());
        $this->assertSame('آزادشده', ReservationStatus::Released->getLabel());
        $this->assertSame('لغو توسط کاربر', ReservationStatus::CancelledUser->getLabel());
        $this->assertSame('لغو توسط ادمین', ReservationStatus::CancelledAdmin->getLabel());
    }

    public function test_get_color_returns_distinct_filament_color_per_case(): void
    {
        $this->assertSame('success', ReservationStatus::Active->getColor());
        $this->assertSame('info', ReservationStatus::Released->getColor());
        $this->assertSame('warning', ReservationStatus::CancelledUser->getColor());
        $this->assertSame('danger', ReservationStatus::CancelledAdmin->getColor());
    }

    public function test_get_icon_returns_distinct_heroicon_per_case(): void
    {
        $this->assertSame('heroicon-o-check-circle', ReservationStatus::Active->getIcon());
        $this->assertSame('heroicon-o-arrow-path', ReservationStatus::Released->getIcon());
        $this->assertSame('heroicon-o-x-circle', ReservationStatus::CancelledUser->getIcon());
        $this->assertSame('heroicon-o-shield-exclamation', ReservationStatus::CancelledAdmin->getIcon());
    }

    public function test_is_cancelled_returns_true_only_for_cancelled_cases(): void
    {
        $this->assertTrue(ReservationStatus::CancelledUser->isCancelled());
        $this->assertTrue(ReservationStatus::CancelledAdmin->isCancelled());
        $this->assertFalse(ReservationStatus::Active->isCancelled());
        $this->assertFalse(ReservationStatus::Released->isCancelled());
    }
}