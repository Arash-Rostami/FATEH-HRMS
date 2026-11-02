<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Reminder\Presentation\ReminderPresenter;
use App\Models\Reminder;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReminderPresenterTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    private function makeReminder(array $overrides = []): Reminder
    {
        return new Reminder(array_merge([
            'due_at' => now(),
            'completed_at' => null,
            'recurs' => 'none',
        ], $overrides));
    }

    public function test_overdue_label_reports_a_whole_number_of_days_not_a_fraction(): void
    {
        Carbon::setTestNow('2026-01-10 15:30:00');
        $reminder = $this->makeReminder(['due_at' => Carbon::parse('2026-01-10 12:00:00')->subDays(3)->addHours(2)]);

        $label = (new ReminderPresenter($reminder))->dueLabel();

        $this->assertStringNotContainsString('.', $label);
        $this->assertSame('دیرکرد ۳ روز', $label);
    }

    public function test_channel_badges_hide_email_and_sms_when_the_tenant_switches_them_off(): void
    {
        $presenter = new ReminderPresenter($this->makeReminder(['channels' => ['email', 'sms']]));
        $labels = fn () => array_column($presenter->channelBadges(), 'label');

        $this->assertContains('ایمیل', $labels());
        $this->assertContains('پیامک', $labels());

        config(['app.off' => ['notify:mail']]);
        $this->assertNotContains('ایمیل', $labels());
        $this->assertContains('پیامک', $labels());

        config(['app.off' => ['notify:sms']]);
        $this->assertContains('ایمیل', $labels());
        $this->assertNotContains('پیامک', $labels());
    }

    public function test_overdue_today_uses_the_time_branch_not_the_day_count(): void
    {
        Carbon::setTestNow('2026-01-10 15:30:00');
        $reminder = $this->makeReminder(['due_at' => Carbon::parse('2026-01-10 09:00:00')]);

        $label = (new ReminderPresenter($reminder))->dueLabel();

        $this->assertSame('دیرکرد امروز، ۰۹:۰۰', $label);
    }
}
