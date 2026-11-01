<?php

namespace Tests\Feature\Enums;

use App\Enums\ReminderRecurrence;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReminderRecurrenceEnumTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    private const LABELS = [
        'None' => 'بدون تکرار',
        'Daily' => 'روزانه',
        'Weekly' => 'هفتگی',
        'Monthly' => 'ماهانه',
    ];

    public function test_label_returns_persian_label_for_every_case(): void
    {
        foreach (ReminderRecurrence::cases() as $case) {
            $this->assertSame(self::LABELS[$case->name], $case->label(), "Label mismatch for {$case->name}.");
        }
    }

    public function test_advance_returns_null_for_none(): void
    {
        $this->assertNull(ReminderRecurrence::None->advance(Carbon::parse('2026-01-01 09:00:00')));
    }

    public function test_advance_steps_forward_until_the_result_is_no_longer_in_the_past(): void
    {
        Carbon::setTestNow('2026-02-15 12:00:00');

        $from = Carbon::parse('2026-01-01 09:00:00');
        $this->assertFalse(ReminderRecurrence::Daily->advance($from)->isPast());
        $this->assertFalse(ReminderRecurrence::Weekly->advance($from)->isPast());
        $this->assertFalse(ReminderRecurrence::Monthly->advance($from)->isPast());
    }

    public function test_advance_does_not_mutate_the_original_carbon_instance(): void
    {
        Carbon::setTestNow('2026-02-15 12:00:00');

        $from = Carbon::parse('2026-01-01 09:00:00');
        $original = $from->format('Y-m-d H:i:s');

        ReminderRecurrence::Monthly->advance($from);

        $this->assertSame($original, $from->format('Y-m-d H:i:s'));
    }
}
