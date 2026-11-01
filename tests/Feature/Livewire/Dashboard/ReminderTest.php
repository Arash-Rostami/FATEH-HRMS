<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Enums\ReminderRecurrence;
use App\Livewire\Dashboard\Reminder\Main;
use App\Livewire\Dashboard\Reminder\Presentation\ReminderPresenter;
use App\Models\Reminder;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Morilog\Jalali\CalendarUtils;
use Tests\TestCase;

class ReminderTest extends TestCase
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
            'recurs' => 'none',
            'channels' => ['inapp:edge,badge,nudge'],
        ], $overrides));
    }

    public function test_open_sets_show_to_true(): void
    {
        $user = $this->activeUser();

        Livewire::actingAs($user)->test(Main::class)
            ->assertSet('show', false)
            ->call('open')
            ->assertSet('show', true);
    }

    public function test_open_lands_on_create_form_when_unscoped(): void
    {
        $user = $this->activeUser();

        Livewire::actingAs($user)->test(Main::class)
            ->call('open')
            ->assertSet('activeTab', 'form')
            ->assertSet('editingId', null);
    }

    public function test_open_from_shortcut_opens_the_unscoped_widget_but_never_a_scoped_one(): void
    {
        $user = $this->activeUser();
        $task = Task::factory()->ownedBy($user)->create();

        Livewire::actingAs($user)->test(Main::class)
            ->call('openFromShortcut')
            ->assertSet('show', true)
            ->assertSet('activeTab', 'form');

        Livewire::actingAs($user)->test(Main::class, ['for' => $task])
            ->call('openFromShortcut')
            ->assertSet('show', false);
    }

    public function test_open_lands_directly_on_a_prefilled_form_when_scoped(): void
    {
        $user = $this->activeUser();
        $due = now()->addDays(5)->setTime(11, 0);
        $task = Task::factory()->ownedBy($user)->create(['deadline' => $due]);
        $jDue = \Morilog\Jalali\Jalalian::fromCarbon($due);

        Livewire::actingAs($user)->test(Main::class, ['for' => $task])
            ->call('open')
            ->assertSet('activeTab', 'form')
            ->assertSet('form.dueYear', (string) $jDue->getYear())
            ->assertSet('form.dueMonth', (string) $jDue->getMonth())
            ->assertSet('form.dueDay', (string) $jDue->getDay());
    }

    public function test_scoped_open_edits_the_existing_uncompleted_reminder_instead_of_offering_a_blank_create(): void
    {
        $user = $this->activeUser();
        $task = Task::factory()->ownedBy($user)->create();
        $existing = $this->makeReminder($user, [
            'remindable_type' => $task->getMorphClass(),
            'remindable_id' => $task->getKey(),
            'title' => 'یادآوری پیگیری',
            'snoozed_until' => now()->addHours(2),
        ]);
        $this->makeReminder($user, [
            'remindable_type' => $task->getMorphClass(),
            'remindable_id' => $task->getKey(),
            'due_at' => now()->addDays(3),
        ]);
        $this->makeReminder($user, [
            'remindable_type' => $task->getMorphClass(),
            'remindable_id' => $task->getKey(),
            'completed_at' => now(),
        ]);

        Livewire::actingAs($user)->test(Main::class, ['for' => $task])
            ->call('open')
            ->assertSet('show', true)
            ->assertSet('editingId', $existing->id)
            ->assertSet('form.title', 'یادآوری پیگیری')
            ->assertSet('activeTab', 'form');
    }

    public function test_scoped_open_falls_back_to_a_blank_create_when_only_completed_reminders_exist(): void
    {
        $user = $this->activeUser();
        $task = Task::factory()->ownedBy($user)->create();
        $this->makeReminder($user, [
            'remindable_type' => $task->getMorphClass(),
            'remindable_id' => $task->getKey(),
            'completed_at' => now(),
        ]);

        Livewire::actingAs($user)->test(Main::class, ['for' => $task])
            ->call('open')
            ->assertSet('editingId', null)
            ->assertSet('activeTab', 'form');
    }

    public function test_open_create_prefills_due_fields_from_the_scoped_hosts_own_deadline(): void
    {
        $user = $this->activeUser();
        $due = now()->addDays(3)->setTime(14, 30);
        $task = Task::factory()->ownedBy($user)->create(['deadline' => $due]);
        $jDue = \Morilog\Jalali\Jalalian::fromCarbon($due);

        Livewire::actingAs($user)->test(Main::class, ['for' => $task])
            ->call('openCreate')
            ->assertSet('form.dueYear', (string) $jDue->getYear())
            ->assertSet('form.dueMonth', (string) $jDue->getMonth())
            ->assertSet('form.dueDay', (string) $jDue->getDay())
            ->assertSet('form.dueTime', $due->format('H:i'));
    }

    public function test_switch_tab_to_form_also_prefills_like_open_create_when_not_editing(): void
    {
        $user = $this->activeUser();
        $due = now()->addDays(2)->setTime(10, 0);
        $task = Task::factory()->ownedBy($user)->create(['deadline' => $due]);
        $jDue = \Morilog\Jalali\Jalalian::fromCarbon($due);

        Livewire::actingAs($user)->test(Main::class, ['for' => $task])
            ->call('switchTab', 'form')
            ->assertSet('activeTab', 'form')
            ->assertSet('form.dueYear', (string) $jDue->getYear())
            ->assertSet('form.dueMonth', (string) $jDue->getMonth())
            ->assertSet('form.dueDay', (string) $jDue->getDay());
    }

    public function test_switch_tab_to_form_does_not_reset_an_in_progress_edit(): void
    {
        $user = $this->activeUser();
        $reminder = $this->makeReminder($user, ['title' => 'Being edited']);

        Livewire::actingAs($user)->test(Main::class)
            ->call('edit', $reminder->id)
            ->set('activeTab', 'list')
            ->call('switchTab', 'form')
            ->assertSet('activeTab', 'form')
            ->assertSet('editingId', $reminder->id)
            ->assertSet('form.title', 'Being edited');
    }

    public function test_open_create_leaves_due_fields_blank_when_host_has_no_deadline_or_is_unscoped(): void
    {
        $user = $this->activeUser();
        $task = Task::factory()->ownedBy($user)->create(['deadline' => null]);

        Livewire::actingAs($user)->test(Main::class, ['for' => $task])
            ->call('openCreate')
            ->assertSet('form.dueYear', '')
            ->assertSet('form.dueMonth', '')
            ->assertSet('form.dueDay', '');

        Livewire::actingAs($user)->test(Main::class)
            ->call('openCreate')
            ->assertSet('form.dueYear', '')
            ->assertSet('form.dueMonth', '')
            ->assertSet('form.dueDay', '');
    }

    public function test_unscoped_mount_lists_only_the_authed_users_own_reminders(): void
    {
        $user = $this->activeUser();
        $stranger = $this->activeUser();

        $mineA = $this->makeReminder($user);
        $mineB = $this->makeReminder($user);
        $foreign = $this->makeReminder($stranger);

        $ids = Livewire::actingAs($user)->test(Main::class)->instance()->reminders->pluck('id')->all();

        $this->assertCount(2, $ids);
        $this->assertContains($mineA->id, $ids);
        $this->assertContains($mineB->id, $ids);
        $this->assertNotContains($foreign->id, $ids);
    }

    public function test_scoped_mount_lists_only_the_authed_users_own_reminders_on_that_task(): void
    {
        $user = $this->activeUser();
        $stranger = $this->activeUser();
        $task = Task::factory()->ownedBy($user)->create();

        $mine = $this->makeReminder($user, ['remindable_type' => Task::class, 'remindable_id' => $task->id]);
        $othersOnSameTask = $this->makeReminder($stranger, ['remindable_type' => Task::class, 'remindable_id' => $task->id]);
        $unrelated = $this->makeReminder($user);

        $ids = Livewire::actingAs($user)->test(Main::class, ['for' => $task])->instance()->reminders->pluck('id')->all();

        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($othersOnSameTask->id, $ids);
        $this->assertNotContains($unrelated->id, $ids);
    }

    public function test_reminders_paginates_at_ten_and_load_more_appends_the_rest(): void
    {
        $user = $this->activeUser();
        foreach (range(1, 12) as $i) {
            $this->makeReminder($user, ['title' => "Pager probe {$i}"]);
        }

        $component = Livewire::actingAs($user)->test(Main::class);

        $this->assertCount(10, $component->instance()->reminders);
        $this->assertTrue($component->instance()->reminders->hasMorePages());

        $component->call('loadMore');

        $this->assertCount(12, $component->instance()->reminders);
        $this->assertFalse($component->instance()->reminders->hasMorePages());
    }

    public function test_set_filter_resets_per_page_to_ten(): void
    {
        $user = $this->activeUser();
        foreach (range(1, 12) as $i) {
            $this->makeReminder($user, ['title' => "Filter pager probe {$i}"]);
        }

        $component = Livewire::actingAs($user)->test(Main::class);
        $component->call('loadMore');

        $this->assertSame(20, $component->get('perPage'));
        $this->assertCount(12, $component->instance()->reminders);

        $component->call('setFilter', 'active');

        $this->assertSame(10, $component->get('perPage'));
        $this->assertCount(10, $component->instance()->reminders);
    }

    public function test_create_persists_a_reminder_with_null_morph_fields_when_unscoped(): void
    {
        $user = $this->activeUser();

        Livewire::actingAs($user)->test(Main::class)
            ->call('openCreate')
            ->assertSet('activeTab', 'form')
            ->set('form.title', 'Unscoped create probe')
            ->set('form.dueYear', '1403')
            ->set('form.dueMonth', '1')
            ->set('form.dueDay', '1')
            ->set('form.dueTime', '10:00')
            ->set('form.recurs', 'none')
            ->set('form.emailEnabled', true)
            ->set('form.inAppNudge', false)
            ->call('create')
            ->assertHasNoErrors()
            ->assertSet('activeTab', 'list');

        $reminder = Reminder::where('title', 'Unscoped create probe')->firstOrFail();
        $this->assertSame($user->id, $reminder->user_id);
        $this->assertNull($reminder->remindable_type);
        $this->assertNull($reminder->remindable_id);
        $this->assertSame(['inapp:edge', 'email'], $reminder->channels);
    }

    public function test_create_persists_a_reminder_with_task_morph_fields_when_scoped(): void
    {
        $user = $this->activeUser();
        $task = Task::factory()->ownedBy($user)->create();

        Livewire::actingAs($user)->test(Main::class, ['for' => $task])
            ->set('form.title', 'Scoped create probe')
            ->set('form.dueYear', '1403')
            ->set('form.dueMonth', '1')
            ->set('form.dueDay', '1')
            ->set('form.dueTime', '10:00')
            ->set('form.recurs', 'none')
            ->call('create')
            ->assertHasNoErrors();

        $reminder = Reminder::where('title', 'Scoped create probe')->firstOrFail();
        $this->assertSame(Task::class, $reminder->remindable_type);
        $this->assertSame($task->id, $reminder->remindable_id);
        $this->assertTrue(Reminder::channelEnabled($reminder->channels, 'badge'));
    }

    public function test_unscoped_update_strips_the_badge_channel_from_a_legacy_global_reminder(): void
    {
        $user = $this->activeUser();
        $legacy = $this->makeReminder($user, ['title' => 'Legacy global badge reminder']);

        $this->assertTrue(Reminder::channelEnabled($legacy->channels, 'badge'));

        Livewire::actingAs($user)->test(Main::class)
            ->call('edit', $legacy->id)
            ->set('form.title', 'Legacy global badge reminder')
            ->set('form.dueYear', '1403')
            ->set('form.dueMonth', '1')
            ->set('form.dueDay', '1')
            ->set('form.dueTime', '10:00')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertFalse(Reminder::channelEnabled($legacy->fresh()->channels, 'badge'));
        $this->assertTrue(Reminder::channelEnabled($legacy->fresh()->channels, 'edge'));
    }

    public function test_update_gates_the_badge_channel_on_the_rows_own_scope_not_the_open_widget(): void
    {
        $user = $this->activeUser();
        $task = Task::factory()->ownedBy($user)->create();
        $scopedRow = $this->makeReminder($user, [
            'title' => 'Scoped row edited from navbar',
            'remindable_type' => Task::class,
            'remindable_id' => $task->id,
        ]);
        $globalRow = $this->makeReminder($user, ['title' => 'Global row edited from scoped widget']);

        Livewire::actingAs($user)->test(Main::class)
            ->call('edit', $scopedRow->id)
            ->set('form.title', 'Scoped row edited from navbar')
            ->set('form.dueYear', '1403')
            ->set('form.dueMonth', '1')
            ->set('form.dueDay', '1')
            ->set('form.dueTime', '10:00')
            ->set('form.inAppBadge', true)
            ->call('update')
            ->assertHasNoErrors();

        $this->assertTrue(Reminder::channelEnabled($scopedRow->fresh()->channels, 'badge'));

        Livewire::actingAs($user)->test(Main::class, ['for' => $task])
            ->call('edit', $globalRow->id)
            ->set('form.title', 'Global row edited from scoped widget')
            ->set('form.dueYear', '1403')
            ->set('form.dueMonth', '1')
            ->set('form.dueDay', '1')
            ->set('form.dueTime', '10:00')
            ->set('form.inAppBadge', true)
            ->call('update')
            ->assertHasNoErrors();

        $this->assertFalse(Reminder::channelEnabled($globalRow->fresh()->channels, 'badge'));
    }

    public function test_editing_the_due_time_clears_notified_at_so_the_new_moment_re_notifies(): void
    {
        $user = $this->activeUser();
        $dueAt = CalendarUtils::createCarbonFromFormat('Y/m/d H:i:s', '1403/01/01 10:00:00');
        $reminder = $this->makeReminder($user, ['title' => 'Notified', 'due_at' => $dueAt, 'notified_at' => now()->subHour()]);

        Livewire::actingAs($user)->test(Main::class)
            ->call('edit', $reminder->id)
            ->set('form.dueTime', '11:30')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertSame('11:30', $reminder->fresh()->due_at->format('H:i'));
        $this->assertNull($reminder->fresh()->notified_at);
    }

    public function test_a_title_only_edit_keeps_notified_at_because_the_due_time_is_unchanged(): void
    {
        $user = $this->activeUser();
        $dueAt = CalendarUtils::createCarbonFromFormat('Y/m/d H:i:s', '1403/01/01 10:00:00');
        $stamp = now()->subHour()->startOfSecond();
        $reminder = $this->makeReminder($user, ['title' => 'Notified', 'due_at' => $dueAt, 'notified_at' => $stamp]);

        Livewire::actingAs($user)->test(Main::class)
            ->call('edit', $reminder->id)
            ->set('form.title', 'Notified, renamed')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertSame('Notified, renamed', $reminder->fresh()->title);
        $this->assertTrue($stamp->eq($reminder->fresh()->notified_at));
    }

    public function test_a_global_recurring_reminders_successor_is_spawned_without_the_badge_channel(): void
    {
        $user = $this->activeUser();
        $reminder = $this->makeReminder($user, [
            'title' => 'Legacy recurring global',
            'due_at' => now()->subDay(),
            'recurs' => ReminderRecurrence::Daily,
        ]);

        $this->assertTrue(Reminder::channelEnabled($reminder->channels, 'badge'));

        Livewire::actingAs($user)->test(Main::class)->call('complete', $reminder->id);

        $successor = Reminder::where('title', 'Legacy recurring global')->where('id', '!=', $reminder->id)->firstOrFail();
        $this->assertFalse(Reminder::channelEnabled($successor->channels, 'badge'));
        $this->assertTrue(Reminder::channelEnabled($successor->channels, 'nudge'));
    }

    public function test_channel_toggles_disable_the_badge_row_only_when_unscoped(): void
    {
        $unscopedRow = collect(ReminderPresenter::channelToggles(false))->firstWhere('name', 'form.inAppBadge');
        $scopedRow = collect(ReminderPresenter::channelToggles(true))->firstWhere('name', 'form.inAppBadge');

        $this->assertTrue($unscopedRow['disabled']);
        $this->assertStringContainsString('فقط رکورد متصل', $unscopedRow['label']);
        $this->assertFalse($scopedRow['disabled']);
    }

    public function test_channel_toggles_keep_email_always_hide_sms_when_the_tenant_switches_it_off_and_keep_the_in_app_rows(): void
    {
        $names = fn () => collect(ReminderPresenter::channelToggles())->pluck('name')->all();

        $this->assertContains('form.emailEnabled', $names());
        $this->assertContains('form.smsEnabled', $names());

        config(['app.off' => ['notify:mail']]);
        $this->assertContains('form.emailEnabled', $names());
        $this->assertContains('form.inAppNudge', $names());
        $this->assertCount(5, ReminderPresenter::channelToggles());

        config(['app.off' => ['notify:sms']]);
        $this->assertContains('form.emailEnabled', $names());
        $this->assertNotContains('form.smsEnabled', $names());
        $this->assertCount(4, ReminderPresenter::channelToggles());

        config(['app.off' => ['notify']]);
        $this->assertContains('form.emailEnabled', $names());
        $this->assertNotContains('form.smsEnabled', $names());
        $this->assertCount(4, ReminderPresenter::channelToggles());
    }

    public function test_complete_marks_it_done_and_a_recurring_reminder_spawns_a_successor(): void
    {
        $user = $this->activeUser();
        $reminder = $this->makeReminder($user, [
            'title' => 'Recurring completion probe',
            'due_at' => now()->subDay(),
            'recurs' => ReminderRecurrence::Daily,
        ]);

        Livewire::actingAs($user)->test(Main::class)->call('complete', $reminder->id);

        $this->assertNotNull($reminder->fresh()->completed_at);
        $this->assertTrue(Reminder::where('title', 'Recurring completion probe')->where('id', '!=', $reminder->id)->exists());
    }

    public function test_snooze_presets_set_the_expected_relative_future_time(): void
    {
        Carbon::setTestNow('2026-01-15 10:00:00');
        $user = $this->activeUser();

        $presets = [
            '1h' => '2026-01-15 11:00:00',
            'tonight' => '2026-01-15 18:00:00',
            'tomorrow' => '2026-01-16 09:00:00',
            'nextweek' => '2026-01-22 10:00:00',
        ];

        foreach ($presets as $preset => $expected) {
            $reminder = $this->makeReminder($user, ['title' => "Snooze probe $preset"]);

            Livewire::actingAs($user)->test(Main::class)->call('snooze', $reminder->id, $preset);

            $this->assertSame($expected, $reminder->fresh()->snoozed_until->format('Y-m-d H:i:s'));
        }
    }

    public function test_snooze_hours_sets_a_custom_relative_future_time_clamped_between_1_and_12(): void
    {
        Carbon::setTestNow('2026-01-15 10:00:00');
        $user = $this->activeUser();

        $reminder = $this->makeReminder($user, ['title' => 'Custom snooze probe']);
        Livewire::actingAs($user)->test(Main::class)->call('snoozeHours', $reminder->id, 5);
        $this->assertSame('2026-01-15 15:00:00', $reminder->fresh()->snoozed_until->format('Y-m-d H:i:s'));

        $overCap = $this->makeReminder($user, ['title' => 'Over cap probe']);
        Livewire::actingAs($user)->test(Main::class)->call('snoozeHours', $overCap->id, 999);
        $this->assertSame('2026-01-15 22:00:00', $overCap->fresh()->snoozed_until->format('Y-m-d H:i:s'));

        $underFloor = $this->makeReminder($user, ['title' => 'Under floor probe']);
        Livewire::actingAs($user)->test(Main::class)->call('snoozeHours', $underFloor->id, 0);
        $this->assertSame('2026-01-15 11:00:00', $underFloor->fresh()->snoozed_until->format('Y-m-d H:i:s'));
    }

    public function test_snoozed_filter_shows_only_reminders_snoozed_into_the_future_soonest_first(): void
    {
        $user = $this->activeUser();
        $notSnoozed = $this->makeReminder($user, ['title' => 'Not snoozed']);
        $snoozedSoon = $this->makeReminder($user, ['title' => 'Snoozed soon', 'snoozed_until' => now()->addHour()]);
        $snoozedLater = $this->makeReminder($user, ['title' => 'Snoozed later', 'snoozed_until' => now()->addDay()]);
        $snoozeExpired = $this->makeReminder($user, ['title' => 'Snooze expired', 'snoozed_until' => now()->subHour()]);
        $completedAndSnoozed = $this->makeReminder($user, ['title' => 'Completed', 'snoozed_until' => now()->addDay(), 'completed_at' => now()]);

        $ids = Livewire::actingAs($user)->test(Main::class)
            ->call('setFilter', 'snoozed')
            ->instance()->reminders->pluck('id')->all();

        $this->assertSame([$snoozedSoon->id, $snoozedLater->id], $ids);
        $this->assertNotContains($notSnoozed->id, $ids);
        $this->assertNotContains($snoozeExpired->id, $ids);
        $this->assertNotContains($completedAndSnoozed->id, $ids);
    }

    public function test_stop_recurrence_sets_recurs_to_none_without_touching_completed_at(): void
    {
        $user = $this->activeUser();
        $reminder = $this->makeReminder($user, ['recurs' => ReminderRecurrence::Weekly]);

        Livewire::actingAs($user)->test(Main::class)->call('stopRecurrence', $reminder->id);

        $fresh = $reminder->fresh();
        $this->assertSame(ReminderRecurrence::None, $fresh->recurs);
        $this->assertNull($fresh->completed_at);
    }

    public function test_delete_removes_the_reminder(): void
    {
        $user = $this->activeUser();
        $reminder = $this->makeReminder($user);

        Livewire::actingAs($user)->test(Main::class)->call('delete', $reminder->id);

        $this->assertNull(Reminder::find($reminder->id));
    }

    public function test_cross_user_actions_abort_with_403(): void
    {
        $owner = $this->activeUser();
        $stranger = $this->activeUser();

        $completeTarget = $this->makeReminder($owner, ['due_at' => now()->subDay(), 'recurs' => ReminderRecurrence::None]);
        Livewire::actingAs($stranger)->test(Main::class)->call('complete', $completeTarget->id)->assertStatus(403);
        $this->assertNull($completeTarget->fresh()->completed_at);

        $snoozeTarget = $this->makeReminder($owner);
        Livewire::actingAs($stranger)->test(Main::class)->call('snooze', $snoozeTarget->id, '1h')->assertStatus(403);
        $this->assertNull($snoozeTarget->fresh()->snoozed_until);

        $deleteTarget = $this->makeReminder($owner);
        Livewire::actingAs($stranger)->test(Main::class)->call('delete', $deleteTarget->id)->assertStatus(403);
        $this->assertNotNull(Reminder::find($deleteTarget->id));

        $updateTarget = $this->makeReminder($owner, ['title' => 'Original title']);
        Livewire::actingAs($stranger)->test(Main::class)
            ->set('editingId', $updateTarget->id)
            ->call('update')
            ->assertStatus(403);
        $this->assertSame('Original title', $updateTarget->fresh()->title);

        $editTarget = $this->makeReminder($owner, ['title' => 'Private title']);
        Livewire::actingAs($stranger)->test(Main::class)->call('edit', $editTarget->id)->assertStatus(403);
    }

    public function test_due_count_respects_the_per_reminder_badge_channel_toggle(): void
    {
        $user = $this->activeUser();
        $this->makeReminder($user, ['due_at' => now()->subDay(), 'channels' => ['inapp:edge,nudge']]);

        $this->assertSame(0, Livewire::actingAs($user)->test(Main::class)->instance()->dueCount);

        $this->makeReminder($user, ['due_at' => now()->subDay(), 'channels' => ['inapp:edge,badge,nudge']]);

        $this->assertSame(1, Livewire::actingAs($user)->test(Main::class)->instance()->dueCount);
    }

    public function test_has_active_reminder_reflects_an_uncompleted_host_scoped_reminder(): void
    {
        $user = $this->activeUser();
        $task = Task::factory()->ownedBy($user)->create();

        $this->assertFalse(Livewire::actingAs($user)->test(Main::class, ['for' => $task])->instance()->hasActiveReminder);

        $reminder = $this->makeReminder($user, [
            'remindable_type' => $task->getMorphClass(),
            'remindable_id' => $task->getKey(),
        ]);

        $this->assertTrue(Livewire::actingAs($user)->test(Main::class, ['for' => $task])->instance()->hasActiveReminder);

        $reminder->update(['completed_at' => now()]);

        $this->assertFalse(Livewire::actingAs($user)->test(Main::class, ['for' => $task])->instance()->hasActiveReminder);
    }

    public function test_set_filter_narrows_reminders_to_overdue_today_or_this_week(): void
    {
        Carbon::setTestNow('2026-01-15 12:00:00');
        $weekStart = Carbon::now()->copy()->startOfWeek(Carbon::SATURDAY);
        Carbon::setTestNow($weekStart->copy()->addDays(3)->setTime(12, 0));

        $user = $this->activeUser();
        $overdue = $this->makeReminder($user, ['title' => 'Overdue probe', 'due_at' => now()->copy()->subDay()]);
        $today = $this->makeReminder($user, ['title' => 'Today probe', 'due_at' => now()->copy()]);
        $midWeek = $this->makeReminder($user, ['title' => 'Mid-week probe', 'due_at' => now()->copy()->addDay()]);
        $nextWeek = $this->makeReminder($user, ['title' => 'Next week probe', 'due_at' => $weekStart->copy()->addDays(9)]);

        $component = Livewire::actingAs($user)->test(Main::class);

        $overdueIds = $component->call('setFilter', 'overdue')->instance()->reminders->pluck('id')->all();
        $this->assertSame([$overdue->id], $overdueIds);

        $todayIds = $component->call('setFilter', 'today')->instance()->reminders->pluck('id')->all();
        $this->assertSame([$today->id], $todayIds);

        $weekIds = $component->call('setFilter', 'week')->instance()->reminders->pluck('id')->all();
        $this->assertContains($overdue->id, $weekIds);
        $this->assertContains($today->id, $weekIds);
        $this->assertContains($midWeek->id, $weekIds);
        $this->assertNotContains($nextWeek->id, $weekIds);
    }

    public function test_completed_filter_shows_only_completed_reminders_newest_first(): void
    {
        $user = $this->activeUser();
        $active = $this->makeReminder($user, ['title' => 'Still active']);
        $completedOld = $this->makeReminder($user, ['title' => 'Completed old', 'completed_at' => now()->subDays(2)]);
        $completedNew = $this->makeReminder($user, ['title' => 'Completed new', 'completed_at' => now()->subHour()]);

        $ids = Livewire::actingAs($user)->test(Main::class)
            ->call('setFilter', 'completed')
            ->instance()->reminders->pluck('id')->all();

        $this->assertSame([$completedNew->id, $completedOld->id], $ids);
        $this->assertNotContains($active->id, $ids);
    }

    public function test_closed_widget_renders_no_modal_body_until_it_is_opened(): void
    {
        $user = $this->activeUser();
        $this->makeReminder($user);

        Livewire::actingAs($user)->test(Main::class)
            ->assertDontSeeHtml('reminder-modal-body')
            ->call('open')
            ->assertSeeHtml('reminder-modal-body');
    }

    public function test_the_shared_open_list_is_refreshed_by_create_snooze_complete_and_delete_and_time_rules_apply_on_each_read(): void
    {
        $user = $this->activeUser();
        $this->assertSame(0, Reminder::dueBadgeCount($user->id));

        $reminder = $this->makeReminder($user, ['due_at' => now()->subDay()]);
        $this->assertSame(1, Reminder::dueBadgeCount($user->id));

        $reminder->snooze(now()->addHours(2));
        $this->assertSame(0, Reminder::dueBadgeCount($user->id));

        Carbon::setTestNow(now()->addHours(3));
        $this->assertSame(1, Reminder::dueBadgeCount($user->id));
        Carbon::setTestNow();

        $this->assertTrue($reminder->fresh()->complete());
        $this->assertSame(0, Reminder::dueBadgeCount($user->id));

        $second = $this->makeReminder($user, ['due_at' => now()]);
        $this->assertSame(1, Reminder::dueBadgeCount($user->id));

        $second->delete();
        $this->assertSame(0, Reminder::dueBadgeCount($user->id));
    }

    public function test_host_url_resolves_when_scoped_and_null_when_unscoped(): void
    {
        $user = $this->activeUser();
        $task = Task::factory()->ownedBy($user)->create();

        $scoped = Livewire::actingAs($user)->test(Main::class, ['for' => $task])->instance()->hostUrl();
        $unscoped = Livewire::actingAs($user)->test(Main::class)->instance()->hostUrl();

        $this->assertStringContainsString('/tasks', $scoped);
        $this->assertStringContainsString('open=' . $task->id, $scoped);
        $this->assertNull($unscoped);
    }
}
