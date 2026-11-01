<?php

namespace Tests\Feature\Enums;

use App\Enums\CancelReason;
use Tests\TestCase;

class CancelReasonEnumTest extends TestCase
{
    private const EXPECTED_LABELS = [
        'user_request' => 'انصراف کاربر',
        'schedule_change' => 'تغییر برنامه زمانی',
        'duplicate_booking' => 'رزرو تکراری',
        'no_longer_needed' => 'عدم نیاز',
        'emergency' => 'موقعیت اضطراری',
        'sickness' => 'بیماری و کسالت',
        'travel_change' => 'تغییر برنامه سفر',
        'resource_unavailable' => 'خارج از دسترس بودن منبع',
        'maintenance' => 'در دست تعمیر و نگهداری',
        'admin_action' => 'لغو توسط مدیر سیستم',
        'policy_violation' => 'عدم رعایت قوانین مجموعه',
        'overbooking' => 'تداخل با رزروهای دیگر',
        'wrong_time_selected' => 'اشتباه در انتخاب تاریخ و ساعت',
        'wrong_resource_selected' => 'اشتباه در انتخاب منبع',
        'system_error' => ' خطای سیستمی',
        'test_booking' => 'تست عملکرد سیستم',
        'other' => 'سایر موارد',
    ];

    public function test_cases_count_matches_expected_set(): void
    {
        $this->assertSame(count(self::EXPECTED_LABELS), count(CancelReason::cases()));
    }

    public function test_every_case_value_is_unique_and_backed_string(): void
    {
        $values = array_map(fn (CancelReason $case) => $case->value, CancelReason::cases());

        $this->assertSame(count($values), count(array_unique($values)));
        $this->assertSame(array_keys(self::EXPECTED_LABELS), $values);
    }

    public function test_get_label_returns_persian_label_for_every_case(): void
    {
        foreach (CancelReason::cases() as $case) {
            $this->assertSame(
                self::EXPECTED_LABELS[$case->value],
                $case->getLabel(),
                "Label mismatch for case {$case->name}."
            );
        }
    }

    public function test_try_from_returns_case_for_valid_value_and_null_for_invalid_value(): void
    {
        foreach (self::EXPECTED_LABELS as $value => $label) {
            $this->assertSame($value, CancelReason::tryFrom($value)->value);
        }

        $this->assertNull(CancelReason::tryFrom('nonexistent_reason'));
        $this->assertNull(CancelReason::tryFrom(''));
    }

    public function test_from_returns_case_for_valid_value_and_throws_for_invalid_value(): void
    {
        $first = array_key_first(self::EXPECTED_LABELS);
        $last = array_key_last(self::EXPECTED_LABELS);

        $this->assertSame($first, CancelReason::from($first)->value);
        $this->assertSame($last, CancelReason::from($last)->value);

        $threw = false;
        try {
            CancelReason::from('nonexistent_reason');
        } catch (\ValueError) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected from() to throw for an invalid value.');
    }

    public function test_implements_has_label_contract(): void
    {
        $this->assertContains('Filament\Support\Contracts\HasLabel', class_implements(CancelReason::class));
    }
}