<?php

namespace Tests\Feature\Models;

use App\Models\Event;
use App\Models\EventShare;
use App\Models\User;
use App\Services\ProjectTask\EventSyncService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EventModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Cache::forget('countdown:active');
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Cache::forget('countdown:active');
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

    public function test_scope_private_filters_to_private_events(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $private = Event::factory()->create(['user_id' => $user->id, 'private' => true]);
        $public = Event::factory()->create(['user_id' => $user->id, 'private' => false]);

        $this->assertContains($private->id, Event::private()->pluck('id')->all());
        $this->assertNotContains($public->id, Event::private()->pluck('id')->all());
    }

    public function test_a_task_synced_event_refuses_manual_update_and_delete_but_not_inside_the_task_sync(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'title' => 'original',
            'description' => EventSyncService::descriptionPrefix() . '1',
        ]);

        $this->assertFalse($event->update(['title' => 'manual']));
        $this->assertFalse($event->delete());
        $this->assertSame('original', $event->fresh()->title);

        Event::syncingTask(fn () => $this->assertTrue($event->update(['title' => 'synced'])));

        $this->assertSame('synced', $event->fresh()->title);
    }

    public function test_a_plain_event_updates_and_deletes_normally(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $event = Event::factory()->create(['user_id' => $user->id, 'title' => 'original']);

        $this->assertTrue($event->update(['title' => 'edited']));
        $this->assertTrue($event->delete());
        $this->assertNull(Event::find($event->id));
    }

    public function test_scope_public_filters_to_non_private_events(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $private = Event::factory()->create(['user_id' => $user->id, 'private' => true]);
        $public = Event::factory()->create(['user_id' => $user->id, 'private' => false]);

        $this->assertContains($public->id, Event::public()->pluck('id')->all());
        $this->assertNotContains($private->id, Event::public()->pluck('id')->all());
    }

    public function test_scope_upcoming_filters_to_future_dates(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $upcoming = Event::factory()->create(['user_id' => $user->id, 'date' => now()->addDays(5)]);
        $past = Event::factory()->create(['user_id' => $user->id, 'date' => now()->subDays(5)]);

        $this->assertContains($upcoming->id, Event::upcoming()->pluck('id')->all());
        $this->assertNotContains($past->id, Event::upcoming()->pluck('id')->all());
    }

    public function test_private_cast_round_trips_boolean(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $event = Event::factory()->create(['user_id' => $user->id, 'private' => true]);

        $this->assertTrue($event->fresh()->private);
    }

    public function test_countdown_cast_round_trips_array(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'countdown' => ['enabled' => true, 'mood' => 'happy'],
        ]);

        $countdown = $event->fresh()->countdown;

        $this->assertSame(true, $countdown['enabled']);
        $this->assertSame('happy', $countdown['mood']);
    }

    public function test_date_jalali_get_returns_formatted_date_string(): void
    {
        $event = new Event();
        $event->date = '2026-03-24 10:00:00';

        $this->assertSame('2026-03-24', $event->date_jalali);
    }

    public function test_date_jalali_get_returns_null_when_no_date(): void
    {
        $event = new Event();

        $this->assertNull($event->date_jalali);
    }

    public function test_date_time_part_get_returns_time_when_date_set(): void
    {
        $event = new Event();
        $event->date = '2026-03-24 10:30:00';

        $this->assertSame('10:30', $event->date_time_part);
    }

    public function test_date_time_part_get_returns_default_when_no_date(): void
    {
        $event = new Event();

        $this->assertSame('08:00', $event->date_time_part);
    }

    public function test_date_jalali_set_updates_date_keeping_existing_time(): void
    {
        $event = new Event();
        $event->date = '2026-03-24 10:30:00';
        $event->date_jalali = '2026-06-15';

        $this->assertSame('2026-06-15 10:30:00', $event->getAttributes()['date']);
    }

    public function test_date_jalali_set_with_blank_does_not_change_date(): void
    {
        $event = new Event();
        $event->date = '2026-03-24 10:30:00';
        $event->date_jalali = '';

        $this->assertSame('2026-03-24 10:30:00', $event->getAttributes()['date']);
    }

    public function test_date_time_part_set_updates_time_keeping_existing_date(): void
    {
        $event = new Event();
        $event->date = '2026-03-24 10:30:00';
        $event->date_time_part = '14:00';

        $this->assertSame('2026-03-24 14:00:00', $event->getAttributes()['date']);
    }

    public function test_has_imminent_shared_for_returns_true_when_share_within_one_day(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'date' => now()->addHours(12),
            'private' => false,
        ]);
        EventShare::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'shared_by' => $user->id,
        ]);

        $this->assertTrue(Event::hasImminentSharedFor($user));
    }

    public function test_has_imminent_shared_for_returns_false_when_no_share_exists(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Event::factory()->create([
            'user_id' => $user->id,
            'date' => now()->addHours(12),
            'private' => false,
        ]);

        $this->assertFalse(Event::hasImminentSharedFor($user));
    }

    public function test_next_reminder_for_returns_own_private_event_within_window(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'date' => now()->addHours(2),
            'private' => true,
            'remind_hours' => 3,
        ]);

        $result = Event::nextReminderFor($user);

        $this->assertSame($event->id, $result['id']);
        $this->assertSame($event->title, $result['title']);
        $this->assertSame($event->date->toIso8601String(), $result['event_at_iso']);
    }

    public function test_next_reminder_for_excludes_other_users_private_event(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        Event::factory()->create([
            'user_id' => $owner->id,
            'date' => now()->addHours(2),
            'private' => true,
            'remind_hours' => 3,
        ]);

        $this->assertNull(Event::nextReminderFor($other));
    }

    public function test_next_reminder_for_includes_private_event_shared_with_user(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $recipient = User::factory()->create(['status' => 'active']);
        $event = Event::factory()->create([
            'user_id' => $owner->id,
            'date' => now()->addHours(2),
            'private' => true,
            'remind_hours' => 3,
        ]);
        EventShare::create(['event_id' => $event->id, 'user_id' => $recipient->id, 'shared_by' => $owner->id]);

        $result = Event::nextReminderFor($recipient);

        $this->assertSame($event->id, $result['id']);
    }

    public function test_next_reminder_for_includes_public_event_for_any_user(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $anyone = User::factory()->create(['status' => 'active']);
        $event = Event::factory()->create([
            'user_id' => $owner->id,
            'date' => now()->addHours(2),
            'private' => false,
            'remind_hours' => 3,
        ]);

        $result = Event::nextReminderFor($anyone);

        $this->assertSame($event->id, $result['id']);
    }

    public function test_next_reminder_for_skips_event_without_remind_hours(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Event::factory()->create([
            'user_id' => $user->id,
            'date' => now()->addHours(2),
            'private' => true,
            'remind_hours' => null,
        ]);

        $this->assertNull(Event::nextReminderFor($user));
    }

    public function test_next_reminder_for_returns_null_before_window_opens(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Event::factory()->create([
            'user_id' => $user->id,
            'date' => now()->addHours(10),
            'private' => true,
            'remind_hours' => 3,
        ]);

        $this->assertNull(Event::nextReminderFor($user));
    }

    public function test_next_reminder_for_returns_null_once_event_has_started(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Event::factory()->create([
            'user_id' => $user->id,
            'date' => now()->subMinutes(5),
            'private' => true,
            'remind_hours' => 3,
        ]);

        $this->assertNull(Event::nextReminderFor($user));
    }

    public function test_next_reminder_for_fires_immediately_when_user_logs_in_late_within_window(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'date' => now()->addMinutes(30),
            'private' => true,
            'remind_hours' => 3,
        ]);

        $result = Event::nextReminderFor($user);

        $this->assertSame($event->id, $result['id']);
        $this->assertSame($event->date->toIso8601String(), $result['event_at_iso']);
    }

    public function test_next_reminder_for_returns_nearest_event_across_multiple_armed_events(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $farther = Event::factory()->create([
            'user_id' => $user->id,
            'date' => now()->addHours(4),
            'private' => true,
            'remind_hours' => 5,
        ]);
        $nearer = Event::factory()->create([
            'user_id' => $user->id,
            'date' => now()->addHours(2),
            'private' => true,
            'remind_hours' => 3,
        ]);

        $result = Event::nextReminderFor($user);

        $this->assertSame($nearer->id, $result['id']);
    }

    public function test_active_countdown_event_returns_array_with_id_title_date_iso_keys_when_match_exists(): void
    {
        $existingSoonest = Event::query()
            ->where('private', false)
            ->where('date', '>=', now())
            ->where('countdown->enabled', true)
            ->orderBy('date', 'asc')
            ->value('date');

        $date = $existingSoonest
            ? Carbon::parse($existingSoonest)->subSecond()->max(now())
            : now()->addDays(3);

        $user = User::factory()->create(['status' => 'active']);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'date' => $date,
            'private' => false,
            'countdown' => ['enabled' => true, 'mood' => 'happy', 'messages' => ['hello']],
        ]);
        Cache::forget('countdown:active');

        $result = Event::activeCountdownEvent();

        $this->assertSame($event->id, $result['id']);
        $this->assertSame($event->title, $result['title']);
        $this->assertArrayHasKey('date_iso', $result);
        $this->assertArrayHasKey('mood', $result);
    }
}