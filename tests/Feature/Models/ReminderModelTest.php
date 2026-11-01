<?php

namespace Tests\Feature\Models;

use App\Enums\ReminderRecurrence;
use App\Jobs\ReconcileReminderDue;
use App\Models\DMS;
use App\Models\Reminder;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReminderModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    private function useMysql(): void
    {
        $env = base_path('.env');
        if (is_file($env)) {
            $vals = [];
            foreach (explode("\n", (string) file_get_contents($env)) as $line) {
                if (preg_match('/^\s*(DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME|DB_PASSWORD)\s*=\s*(.*)$/', $line, $m)) {
                    $vals[$m[1]] = trim(preg_replace('/\s+#.*$/', '', trim($m[2])), "\"' \t");
                }
            }
            $map = ['DB_HOST' => 'host', 'DB_PORT' => 'port', 'DB_DATABASE' => 'database', 'DB_USERNAME' => 'username', 'DB_PASSWORD' => 'password'];
            foreach ($map as $envKey => $cfgKey) {
                if (isset($vals[$envKey])) {
                    config(['database.connections.mysql.' . $cfgKey => $vals[$envKey]]);
                }
            }
        }
        DB::purge('mysql');
        config(['database.default' => 'mysql']);
    }

    private function activeUser(): User
    {
        return User::factory()->create(['status' => 'active']);
    }

    private function makeReminder(User $user, array $overrides = []): Reminder
    {
        return Reminder::create(array_merge([
            'user_id' => $user->id,
            'title' => 'Test reminder',
            'due_at' => now(),
            'recurs' => ReminderRecurrence::None,
            'channels' => ['inapp:edge,badge,nudge'],
        ], $overrides));
    }

    public function test_scope_active_excludes_completed_and_future_snoozed_but_includes_past_snoozed(): void
    {
        $user = $this->activeUser();
        $completed = $this->makeReminder($user, ['completed_at' => now()]);
        $futureSnoozed = $this->makeReminder($user, ['snoozed_until' => now()->addHour()]);
        $pastSnoozed = $this->makeReminder($user, ['snoozed_until' => now()->subHour()]);
        $plain = $this->makeReminder($user);

        $ids = Reminder::forUser($user->id)->active()->pluck('id')->all();

        $this->assertNotContains($completed->id, $ids);
        $this->assertNotContains($futureSnoozed->id, $ids);
        $this->assertContains($pastSnoozed->id, $ids);
        $this->assertContains($plain->id, $ids);
    }

    public function test_scope_overdue_only_includes_reminders_due_before_start_of_today(): void
    {
        $user = $this->activeUser();
        $overdue = $this->makeReminder($user, ['due_at' => now()->subDay()]);
        $today = $this->makeReminder($user, ['due_at' => now()]);
        $future = $this->makeReminder($user, ['due_at' => now()->addDay()]);

        $ids = Reminder::forUser($user->id)->overdue()->pluck('id')->all();

        $this->assertContains($overdue->id, $ids);
        $this->assertNotContains($today->id, $ids);
        $this->assertNotContains($future->id, $ids);
    }

    public function test_scope_due_today_only_includes_reminders_due_on_the_current_calendar_day(): void
    {
        $user = $this->activeUser();
        $today = $this->makeReminder($user, ['due_at' => now()]);
        $yesterday = $this->makeReminder($user, ['due_at' => now()->subDay()]);
        $tomorrow = $this->makeReminder($user, ['due_at' => now()->addDay()]);

        $ids = Reminder::forUser($user->id)->dueToday()->pluck('id')->all();

        $this->assertContains($today->id, $ids);
        $this->assertNotContains($yesterday->id, $ids);
        $this->assertNotContains($tomorrow->id, $ids);
    }

    public function test_scope_this_week_respects_the_saturday_start_boundary(): void
    {
        Carbon::setTestNow('2026-09-10 12:00:00');

        $user = $this->activeUser();
        $weekStart = now()->startOfWeek(Carbon::SATURDAY);

        $beforeStart = $this->makeReminder($user, ['due_at' => $weekStart->copy()->subSecond()]);
        $atStart = $this->makeReminder($user, ['due_at' => $weekStart->copy()]);
        $atEnd = $this->makeReminder($user, ['due_at' => $weekStart->copy()->addDays(6)->endOfDay()]);
        $afterEnd = $this->makeReminder($user, ['due_at' => $weekStart->copy()->addDays(7)]);

        $ids = Reminder::forUser($user->id)->thisWeek()->pluck('id')->all();

        $this->assertNotContains($beforeStart->id, $ids);
        $this->assertContains($atStart->id, $ids);
        $this->assertContains($atEnd->id, $ids);
        $this->assertNotContains($afterEnd->id, $ids);
    }

    public function test_complete_with_no_recurrence_marks_done_and_spawns_no_successor(): void
    {
        $user = $this->activeUser();
        $reminder = $this->makeReminder($user, ['title' => 'One-off', 'recurs' => ReminderRecurrence::None]);

        $result = $reminder->complete();

        $this->assertTrue($result);
        $this->assertNotNull($reminder->fresh()->completed_at);
        $this->assertSame(1, Reminder::where('title', 'One-off')->count());
    }

    public function test_complete_is_idempotent_and_never_spawns_a_second_successor(): void
    {
        $user = $this->activeUser();
        $reminder = $this->makeReminder($user, [
            'title' => 'Recurring idempotent',
            'due_at' => now()->subDay(),
            'recurs' => ReminderRecurrence::Daily,
        ]);

        $first = $reminder->complete();
        $second = $reminder->complete();

        $this->assertTrue($first);
        $this->assertFalse($second);
        $this->assertSame(2, Reminder::where('title', 'Recurring idempotent')->count());
    }

    public function test_complete_with_recurrence_spawns_exactly_one_fresh_successor(): void
    {
        $user = $this->activeUser();
        $reminder = $this->makeReminder($user, [
            'title' => 'Weekly chore',
            'due_at' => now()->subDay(),
            'recurs' => ReminderRecurrence::Weekly,
            'notified_at' => now(),
        ]);

        $reminder->complete();

        $successor = Reminder::where('title', 'Weekly chore')->where('id', '!=', $reminder->id)->firstOrFail();

        $this->assertFalse($successor->due_at->isPast());
        $this->assertNull($successor->completed_at);
        $this->assertNull($successor->snoozed_until);
        $this->assertNull($successor->notified_at);
    }

    public function test_complete_with_monthly_recurrence_steps_forward_one_jalali_month(): void
    {
        Carbon::setTestNow('2026-02-01 00:00:00');

        $user = $this->activeUser();
        $reminder = $this->makeReminder($user, [
            'title' => 'End of month bill',
            'due_at' => Carbon::parse('2026-01-31 09:00:00'),
            'recurs' => ReminderRecurrence::Monthly,
        ]);

        $reminder->complete();

        $successor = Reminder::where('title', 'End of month bill')->where('id', '!=', $reminder->id)->firstOrFail();

        // 11 Bahman 1404 -> 11 Esfand 1404, same time of day.
        $this->assertSame('2026-03-02 09:00', $successor->due_at->format('Y-m-d H:i'));
    }

    public function test_monthly_recurrence_clamps_to_the_shorter_jalali_month(): void
    {
        Carbon::setTestNow('2026-09-01 00:00:00');

        $user = $this->activeUser();
        $reminder = $this->makeReminder($user, [
            'title' => 'Shahrivar 31st',
            'due_at' => Carbon::parse('2026-09-22 09:00:00'),
            'recurs' => ReminderRecurrence::Monthly,
        ]);

        $reminder->complete();

        $successor = Reminder::where('title', 'Shahrivar 31st')->where('id', '!=', $reminder->id)->firstOrFail();

        // 31 Shahrivar 1405 -> 30 Mehr 1405 (Mehr has 30 days).
        $this->assertSame('2026-10-22', $successor->due_at->format('Y-m-d'));
    }

    public function test_snooze_sets_snoozed_until_and_resets_notified_at(): void
    {
        $user = $this->activeUser();
        $reminder = $this->makeReminder($user, ['notified_at' => now()]);
        $until = now()->addHours(3);

        $reminder->snooze($until);
        $fresh = $reminder->fresh();

        $this->assertSame($until->format('Y-m-d H:i:s'), $fresh->snoozed_until->format('Y-m-d H:i:s'));
        $this->assertNull($fresh->notified_at);
    }

    public function test_suggested_titles_ranks_by_frequency_descending_and_is_scoped_per_user(): void
    {
        $userA = $this->activeUser();
        $userB = $this->activeUser();

        $this->makeReminder($userA, ['title' => 'Rare title']);
        for ($i = 0; $i < 3; $i++) {
            $this->makeReminder($userA, ['title' => 'Frequent title']);
        }
        for ($i = 0; $i < 10; $i++) {
            $this->makeReminder($userB, ['title' => 'Rare title']);
        }

        $titles = Reminder::suggestedTitles($userA->id);

        $this->assertSame('Frequent title', $titles[0]);
        $this->assertSame('Rare title', $titles[1]);
    }

    public function test_channel_enabled_handles_missing_and_malformed_channel_tokens(): void
    {
        $this->assertFalse(Reminder::channelEnabled([], 'edge'));
        $this->assertFalse(Reminder::channelEnabled(['email'], 'badge'));
        $this->assertFalse(Reminder::channelEnabled(['inapp:'], 'edge'));
        $this->assertTrue(Reminder::channelEnabled(['email', 'inapp:edge,badge,nudge'], 'nudge'));
    }

    public function test_due_badge_count_excludes_badge_disabled_trashed_host_and_respects_host_scope(): void
    {
        $user = $this->activeUser();

        $this->makeReminder($user);
        $this->makeReminder($user, ['channels' => ['inapp:edge,nudge'], 'title' => 'Badge disabled']);
        $trashedHost = Task::factory()->ownedBy($user)->create();
        $this->makeReminder($user, ['remindable_type' => Task::class, 'remindable_id' => $trashedHost->id, 'title' => 'Trashed host']);
        $trashedHost->delete();
        $this->makeReminder($user, ['due_at' => now()->addDays(2), 'title' => 'Not due yet']);
        $this->makeReminder($this->activeUser());

        $this->assertSame(1, Reminder::dueBadgeCount($user->id));

        $keptTask = Task::factory()->ownedBy($user)->create();
        $this->makeReminder($user, ['remindable_type' => Task::class, 'remindable_id' => $keptTask->id]);

        $this->assertSame(1, Reminder::dueBadgeCount($user->id, Task::class, $keptTask->id));
        $this->assertSame(0, Reminder::dueBadgeCount($user->id, Task::class, $trashedHost->id));
    }

    public function test_url_for_returns_null_for_an_unrecognized_remindable_type(): void
    {
        $this->assertNull(Reminder::urlFor('App\\Models\\SomeUnknownModel', 1));
        $this->assertNull(Reminder::urlFor(null, 1));
    }

    public function test_host_url_if_available_drops_the_link_when_the_hosts_module_is_off_for_the_tenant(): void
    {
        $dms = new Reminder(['remindable_type' => DMS::class, 'remindable_id' => 5]);
        $task = new Reminder(['remindable_type' => Task::class, 'remindable_id' => 5]);

        $this->assertStringContainsString('open=5', (string) $dms->hostUrlIfAvailable());

        config(['app.off' => ['dms']]);

        $this->assertNull($dms->hostUrlIfAvailable());
        $this->assertStringContainsString('open=5', (string) $task->hostUrlIfAvailable());
        $this->assertNotNull($dms->hostUrl());
    }

    public function test_host_trashed_reflects_soft_delete_state_and_ignores_non_soft_deletable_hosts(): void
    {
        $user = $this->activeUser();
        $task = Task::factory()->ownedBy($user)->create();
        $reminder = $this->makeReminder($user, ['remindable_type' => Task::class, 'remindable_id' => $task->id]);

        $this->assertFalse($reminder->hostTrashed());

        $task->delete();

        $this->assertTrue($reminder->fresh()->hostTrashed());

        $unscoped = $this->makeReminder($user);
        $this->assertFalse($unscoped->hostTrashed());
    }

    public function test_soft_deleting_a_task_does_not_destroy_its_reminders(): void
    {
        $user = $this->activeUser();
        $task = Task::factory()->ownedBy($user)->create();
        $r1 = $this->makeReminder($user, ['remindable_type' => Task::class, 'remindable_id' => $task->id]);
        $r2 = $this->makeReminder($user, ['remindable_type' => Task::class, 'remindable_id' => $task->id]);

        $task->delete();

        $this->assertSame(2, Reminder::whereIn('id', [$r1->id, $r2->id])->count());
    }

    public function test_force_deleting_a_task_cascades_to_delete_its_reminders(): void
    {
        $user = $this->activeUser();
        $task = Task::factory()->ownedBy($user)->create();
        $r1 = $this->makeReminder($user, ['remindable_type' => Task::class, 'remindable_id' => $task->id]);
        $r2 = $this->makeReminder($user, ['remindable_type' => Task::class, 'remindable_id' => $task->id]);

        $task->forceDelete();

        $this->assertSame(0, Reminder::whereIn('id', [$r1->id, $r2->id])->count());
    }

    public function test_saving_a_reminder_due_within_a_day_queues_one_delayed_due_time_reconcile(): void
    {
        Cache::flush();
        Queue::fake();
        $user = User::factory()->create(['status' => 'active']);

        $reminder = $this->makeReminder($user, ['due_at' => now()->addHours(2)]);
        $reminder->update(['title' => 'renamed']);

        Queue::assertPushed(ReconcileReminderDue::class, 1);
        Queue::assertPushed(ReconcileReminderDue::class, fn (ReconcileReminderDue $job) => $job->reminderId === $reminder->id
            && $job->delay->equalTo($reminder->due_at));
    }

    public function test_a_new_due_time_or_snooze_end_queues_a_new_delayed_reconcile(): void
    {
        Cache::flush();
        Queue::fake();
        $user = User::factory()->create(['status' => 'active']);
        $reminder = $this->makeReminder($user, ['due_at' => now()->addHours(2)]);

        $reminder->update(['due_at' => now()->addHours(5)]);
        $reminder->snooze(now()->addHours(8));

        Queue::assertPushed(ReconcileReminderDue::class, 3);
    }

    public function test_no_delayed_reconcile_for_far_future_or_completed_reminders(): void
    {
        Cache::flush();
        Queue::fake();
        $user = User::factory()->create(['status' => 'active']);

        $this->makeReminder($user, ['due_at' => now()->addDays(3)]);
        $this->makeReminder($user, ['due_at' => now()->addHours(2), 'completed_at' => now()]);
        $this->makeReminder($user, ['due_at' => now()->subHour(), 'completed_at' => now()]);

        Queue::assertNotPushed(ReconcileReminderDue::class);
    }

    public function test_creating_an_already_due_reminder_queues_one_immediate_reconcile_even_after_repeated_saves(): void
    {
        Cache::flush();
        Queue::fake();
        $user = User::factory()->create(['status' => 'active']);

        $reminder = $this->makeReminder($user, ['due_at' => now()->subHour(), 'channels' => ['email']]);
        $reminder->update(['title' => 'renamed']);
        $reminder->update(['title' => 'renamed again']);

        Queue::assertPushed(ReconcileReminderDue::class, 1);
        Queue::assertPushed(ReconcileReminderDue::class, fn (ReconcileReminderDue $job) => $job->reminderId === $reminder->id);
    }

    public function test_a_past_due_reminder_within_a_day_queues_a_reconcile_only_with_an_email_or_sms_channel(): void
    {
        Cache::flush();
        Queue::fake();
        $user = User::factory()->create(['status' => 'active']);

        $this->makeReminder($user, ['due_at' => now()->subHours(2), 'channels' => ['inapp:edge,badge,nudge']]);
        Queue::assertNotPushed(ReconcileReminderDue::class);

        $this->makeReminder($user, ['due_at' => now()->subHours(2), 'channels' => ['inapp:edge,badge,nudge', 'email']]);
        Queue::assertPushed(ReconcileReminderDue::class, 1);
    }

    public function test_a_reminder_overdue_by_more_than_a_day_is_not_requeued_by_a_save(): void
    {
        Cache::flush();
        Queue::fake();
        $user = User::factory()->create(['status' => 'active']);

        $reminder = $this->makeReminder($user, ['due_at' => now()->subHours(30), 'channels' => ['email']]);
        $reminder->update(['title' => 'renamed']);

        Queue::assertNotPushed(ReconcileReminderDue::class);
    }

    public function test_a_future_moment_within_a_day_queues_a_reconcile_regardless_of_channels(): void
    {
        Cache::flush();
        Queue::fake();
        $user = User::factory()->create(['status' => 'active']);

        $this->makeReminder($user, ['due_at' => now()->addHours(3), 'channels' => ['inapp:edge,badge,nudge']]);

        Queue::assertPushed(ReconcileReminderDue::class, 1);
    }

    public function test_deliver_due_on_a_stale_instance_sends_nothing_once_the_row_is_completed_or_snoozed(): void
    {
        Queue::fake();
        Notification::fake();
        $user = User::factory()->create(['status' => 'active']);

        $completed = $this->makeReminder($user, ['due_at' => now()->subHour(), 'channels' => ['email']]);
        $stale = Reminder::find($completed->id);
        Reminder::whereKey($completed->id)->update(['completed_at' => now()]);
        $stale->deliverDue();

        $snoozed = $this->makeReminder($user, ['due_at' => now()->subHour(), 'channels' => ['email']]);
        $staleSnoozed = Reminder::find($snoozed->id);
        Reminder::whereKey($snoozed->id)->update(['snoozed_until' => now()->addHour()]);
        $staleSnoozed->deliverDue();

        Notification::assertNothingSent();
        $this->assertNull($completed->fresh()->notified_at);
        $this->assertNull($snoozed->fresh()->notified_at);
    }
}
