<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ReconcileReminderDue;
use App\Jobs\SendReminderSms;
use App\Models\Edge;
use App\Models\Reminder;
use App\Models\Task;
use App\Models\User;
use App\Notifications\ReminderDueNotification;
use App\Services\Menu\EdgeService;
use App\Services\Menu\Notifications\ReminderOverdueNudge;
use App\Services\Menu\NudgeService;
use App\Services\Menu\Toasts\ReminderDueTodayEdge;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReconcileReminderDueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Queue::fake();
        NudgeService::reset();
        EdgeService::reset();
        NudgeService::register(new ReminderOverdueNudge());
        EdgeService::register(new ReminderDueTodayEdge());
        Cache::flush();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        NudgeService::reset();
        EdgeService::reset();
        Cache::flush();
        Carbon::setTestNow();
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

    private function rows(Reminder $reminder): array
    {
        return [
            DatabaseNotification::query()
                ->where('notifiable_id', $reminder->user_id)
                ->where('data->menu_key', 'reminders-controller:due-nudge')
                ->where('data->item_id', (string) $reminder->id)
                ->count(),
            Edge::query()
                ->where('user_id', $reminder->user_id)
                ->where('edge_key', 'reminders-controller:due-today-edge')
                ->where('subject_id', (string) $reminder->id)
                ->count(),
        ];
    }

    public function test_the_job_creates_the_nudge_and_the_toast_once_the_due_time_has_passed(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Carbon::setTestNow('2026-03-10 10:00:00');
        $reminder = Reminder::create(['user_id' => $user->id, 'title' => 'at three', 'due_at' => '2026-03-10 15:00:00', 'recurs' => 'none', 'channels' => ['inapp:edge,badge,nudge']]);

        (new ReconcileReminderDue($reminder->id))->handle();
        $this->assertSame([0, 0], $this->rows($reminder));

        Carbon::setTestNow('2026-03-10 15:00:01');
        (new ReconcileReminderDue($reminder->id))->handle();
        $this->assertSame([1, 1], $this->rows($reminder));
    }

    public function test_the_job_honours_the_snooze_and_removes_rows_of_a_completed_reminder(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $reminder = Reminder::create(['user_id' => $user->id, 'title' => 'snoozed', 'due_at' => now()->subHour(), 'recurs' => 'none', 'channels' => ['inapp:edge,badge,nudge'], 'snoozed_until' => now()->addHour()]);

        (new ReconcileReminderDue($reminder->id))->handle();
        $this->assertSame([0, 0], $this->rows($reminder));

        $reminder->update(['snoozed_until' => null]);
        (new ReconcileReminderDue($reminder->id))->handle();
        $this->assertSame([1, 1], $this->rows($reminder));

        $reminder->update(['completed_at' => now()]);
        (new ReconcileReminderDue($reminder->id))->handle();
        $this->assertSame([0, 0], $this->rows($reminder));
    }

    private function deliverable(User $user, array $overrides = []): Reminder
    {
        return Reminder::create(array_merge(['user_id' => $user->id, 'title' => 'deliver', 'due_at' => now()->subMinute(), 'recurs' => 'none', 'channels' => ['inapp:edge,badge,nudge', 'email', 'sms']], $overrides));
    }

    private function runJob(Reminder $reminder): void
    {
        (new ReconcileReminderDue($reminder->id))->handle();
    }

    public function test_email_and_sms_go_out_once_from_the_job_even_when_the_toast_is_completed_before_the_sweep(): void
    {
        Notification::fake();
        $user = User::factory()->create(['status' => 'active']);
        $reminder = $this->deliverable($user);

        $this->runJob($reminder);

        Notification::assertSentToTimes($user, ReminderDueNotification::class, 1);
        $this->assertSame(1, Queue::pushed(SendReminderSms::class, fn ($job) => $job->reminderId === $reminder->id)->count());
        $this->assertNotNull($reminder->fresh()->notified_at);

        $reminder->complete();
        Artisan::call('reminders:sweep-due');

        Notification::assertSentToTimes($user, ReminderDueNotification::class, 1);
        $this->assertSame(1, Queue::pushed(SendReminderSms::class, fn ($job) => $job->reminderId === $reminder->id)->count());
    }

    public function test_nothing_is_delivered_before_the_due_time_and_a_repeat_run_never_delivers_twice(): void
    {
        Notification::fake();
        $user = User::factory()->create(['status' => 'active']);
        Carbon::setTestNow('2026-03-10 10:00:00');
        $reminder = $this->deliverable($user, ['due_at' => '2026-03-10 15:00:00']);

        $this->runJob($reminder);
        Notification::assertNothingSent();
        Queue::assertNotPushed(SendReminderSms::class);
        $this->assertNull($reminder->fresh()->notified_at);

        Carbon::setTestNow('2026-03-10 15:00:01');
        $this->runJob($reminder);
        $this->runJob($reminder);

        Notification::assertSentToTimes($user, ReminderDueNotification::class, 1);
        Queue::assertPushed(SendReminderSms::class, 1);
    }

    public function test_a_snoozed_reminder_is_held_until_the_snooze_ends_then_delivered(): void
    {
        Notification::fake();
        $user = User::factory()->create(['status' => 'active']);
        Carbon::setTestNow('2026-03-10 10:00:00');
        $reminder = $this->deliverable($user, ['due_at' => '2026-03-10 09:00:00', 'snoozed_until' => '2026-03-10 12:00:00']);

        $this->runJob($reminder);
        Notification::assertNothingSent();
        $this->assertNull($reminder->fresh()->notified_at);

        $reminder->snooze(now()->addHours(3));
        Carbon::setTestNow('2026-03-10 13:00:01');
        $this->runJob($reminder);

        Notification::assertSentToTimes($user, ReminderDueNotification::class, 1);
        Queue::assertPushed(SendReminderSms::class, 1);
    }

    public function test_a_reminder_completed_before_delivery_is_never_delivered(): void
    {
        Notification::fake();
        $user = User::factory()->create(['status' => 'active']);
        $reminder = $this->deliverable($user);
        $reminder->complete();

        $this->runJob($reminder);

        Notification::assertNothingSent();
        Queue::assertNotPushed(SendReminderSms::class);
        $this->assertNull($reminder->fresh()->notified_at);
    }

    public function test_delivery_respects_the_tenant_reminder_switch_and_each_channel_switch(): void
    {
        Notification::fake();
        $user = User::factory()->create(['status' => 'active']);
        $reminder = $this->deliverable($user);

        config(['app.off' => ['reminder']]);
        $this->runJob($reminder);

        Notification::assertNothingSent();
        Queue::assertNotPushed(SendReminderSms::class);
        $this->assertNull($reminder->fresh()->notified_at);

        config(['app.off' => ['notify:sms']]);
        $smsOnly = $this->deliverable($user, ['channels' => ['sms']]);
        $this->runJob($smsOnly);

        Queue::assertNotPushed(SendReminderSms::class);
        $this->assertNull($smsOnly->fresh()->notified_at);

        config(['app.off' => ['notify:mail']]);
        $emailOnly = $this->deliverable($user, ['channels' => ['email']]);
        $this->runJob($emailOnly);

        Notification::assertNothingSent();
        $this->assertNull($emailOnly->fresh()->notified_at);
    }

    public function test_an_inactive_owner_gets_nothing_and_the_reminder_stays_unstamped_until_the_owner_is_active(): void
    {
        Notification::fake();
        $user = User::factory()->create(['status' => 'inactive']);
        $reminder = $this->deliverable($user);

        $this->runJob($reminder);

        Notification::assertNothingSent();
        Queue::assertNotPushed(SendReminderSms::class);
        $this->assertNull($reminder->fresh()->notified_at);

        $user->forceFill(['status' => 'active'])->save();
        $this->runJob($reminder);
        $this->runJob($reminder);

        Notification::assertSentToTimes($user, ReminderDueNotification::class, 1);
        Queue::assertPushed(SendReminderSms::class, 1);
        $this->assertNotNull($reminder->fresh()->notified_at);
    }

    public function test_a_reminder_on_a_soft_deleted_host_is_not_delivered(): void
    {
        Notification::fake();
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->create();
        $reminder = $this->deliverable($user, ['remindable_type' => Task::class, 'remindable_id' => $task->id]);
        $task->delete();

        $this->runJob($reminder);

        Notification::assertNothingSent();
        Queue::assertNotPushed(SendReminderSms::class);
        $this->assertNull($reminder->fresh()->notified_at);
    }

    public function test_the_job_is_a_silent_no_op_for_a_missing_reminder(): void
    {
        $this->expectNotToPerformAssertions();

        (new ReconcileReminderDue(999999999))->handle();
    }
}
