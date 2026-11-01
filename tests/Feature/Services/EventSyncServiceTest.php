<?php

namespace Tests\Feature\Services;

use App\Enums\ReservationStatus;
use App\Models\Event;
use App\Models\EventShare;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\User;
use App\Services\Menu\Notifications\SharedEventsNudge;
use App\Services\Reservation\EventSyncService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EventSyncServiceTest extends TestCase
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
        parent::tearDown();
    }

    private function useMysql(): void
    {
        $env = base_path('.env');
        if (is_file($env)) {
            $vals = [];
            foreach (explode("\n", (string)file_get_contents($env)) as $line) {
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

    private function skipUnlessSharesCascade(): void
    {
        if (!DB::selectOne("select 1 from information_schema.REFERENTIAL_CONSTRAINTS where CONSTRAINT_SCHEMA = database() and TABLE_NAME = 'event_shares' and REFERENCED_TABLE_NAME = 'events' and DELETE_RULE = 'CASCADE'")) {
            $this->markTestSkipped('dev DB lacks the event_shares.event_id ON DELETE CASCADE foreign key declared in the migration');
        }
    }

    private function description(Reservation $r): string
    {
        return "جلسه برنامه‌ریزی شده از طریق سیستم رزرواسیون #{$r->id}";
    }

    private function makeActiveMeetingReservation(User $booker, User $related, ?Resource $resource = null): Reservation
    {
        $resource ??= Resource::factory()->create(['type' => 'meeting', 'status' => 'active', 'name' => $related->name]);
        $start = Carbon::now()->addDay()->setHour(9)->setMinute(0)->setSecond(0);
        $reservation = new Reservation([
            'user_id' => $booker->id,
            'resource_id' => $resource->id,
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'is_full_day' => false,
            'status' => ReservationStatus::Active->value,
        ]);
        $reservation->saveQuietly();
        $reservation->loadMissing(['user', 'resource.relatedUser']);

        return $reservation;
    }

    public function test_sync_creates_one_shared_event_owned_by_booker_with_share_for_related(): void
    {
        $booker = User::factory()->create(['status' => 'active']);
        $related = User::factory()->create(['status' => 'active']);
        $reservation = $this->makeActiveMeetingReservation($booker, $related);

        app(EventSyncService::class)->sync($reservation);

        $description = $this->description($reservation);
        $events = Event::where('description', $description)->get();
        $this->assertCount(1, $events, 'exactly one Event row must exist, not a mirrored copy per person');

        $event = $events->first();
        $this->assertSame($booker->id, $event->user_id);
        $this->assertSame("جلسه {$booker->name} و {$related->name}", $event->title);
        $this->assertTrue($event->private);
        $this->assertSame($reservation->start_time->format('Y-m-d H:i'), $event->date->format('Y-m-d H:i'));

        $shares = EventShare::where('event_id', $event->id)->get();
        $this->assertCount(1, $shares);
        $this->assertSame($related->id, $shares->first()->user_id);
        $this->assertSame($booker->id, $shares->first()->shared_by);
    }

    public function test_sync_is_idempotent_and_does_not_duplicate_events_or_shares_on_repeat_calls(): void
    {
        $booker = User::factory()->create(['status' => 'active']);
        $related = User::factory()->create(['status' => 'active']);
        $reservation = $this->makeActiveMeetingReservation($booker, $related);

        $service = app(EventSyncService::class);
        $service->sync($reservation);
        $service->sync($reservation);
        $service->sync($reservation);

        $description = $this->description($reservation);
        $this->assertSame(1, Event::where('description', $description)->count());
        $event = Event::where('description', $description)->first();
        $this->assertSame(1, EventShare::where('event_id', $event->id)->count());
    }

    public function test_sync_purges_pre_existing_event_and_share_when_reservation_is_not_active_meeting(): void
    {
        $this->skipUnlessSharesCascade();

        $booker = User::factory()->create(['status' => 'active']);
        $related = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'meeting', 'status' => 'active', 'name' => $related->name]);
        $start = Carbon::now()->addDay()->setHour(9)->setMinute(0)->setSecond(0);
        $reservation = new Reservation([
            'user_id' => $booker->id,
            'resource_id' => $resource->id,
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'is_full_day' => false,
            'status' => ReservationStatus::CancelledUser->value,
        ]);
        $reservation->saveQuietly();
        $reservation->loadMissing(['user', 'resource.relatedUser']);

        $description = $this->description($reservation);
        $event = Event::create(['user_id' => $booker->id, 'title' => 'x', 'description' => $description, 'date' => $start, 'private' => true]);
        EventShare::create(['event_id' => $event->id, 'user_id' => $related->id, 'shared_by' => $booker->id]);

        app(EventSyncService::class)->sync($reservation);

        $this->assertSame(0, Event::where('description', $description)->count());
        $this->assertSame(0, EventShare::where('event_id', $event->id)->count());
    }

    public function test_sync_purges_events_for_non_meeting_resource_type(): void
    {
        $booker = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $start = Carbon::now()->addDay()->setHour(9)->setMinute(0)->setSecond(0);
        $reservation = new Reservation([
            'user_id' => $booker->id,
            'resource_id' => $resource->id,
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'is_full_day' => false,
            'status' => ReservationStatus::Active->value,
        ]);
        $reservation->saveQuietly();
        $reservation->loadMissing(['user', 'resource.relatedUser']);

        $description = $this->description($reservation);
        Event::create(['user_id' => $booker->id, 'title' => 'x', 'description' => $description, 'date' => $start, 'private' => true]);

        app(EventSyncService::class)->sync($reservation);

        $this->assertSame(0, Event::where('description', $description)->count());
    }

    public function test_purge_directly_removes_matching_event_and_cascades_its_shares(): void
    {
        $this->skipUnlessSharesCascade();

        $booker = User::factory()->create(['status' => 'active']);
        $related = User::factory()->create(['status' => 'active']);
        $reservation = $this->makeActiveMeetingReservation($booker, $related);

        $description = $this->description($reservation);
        $event = Event::create(['user_id' => $booker->id, 'title' => 'x', 'description' => $description, 'date' => $reservation->start_time, 'private' => true]);
        EventShare::create(['event_id' => $event->id, 'user_id' => $related->id, 'shared_by' => $booker->id]);
        $unrelated = Event::create(['user_id' => $booker->id, 'title' => 'unrelated', 'description' => 'other description', 'date' => $reservation->start_time, 'private' => true]);

        app(EventSyncService::class)->purge($reservation);

        $this->assertSame(0, Event::where('description', $description)->count());
        $this->assertSame(0, EventShare::where('event_id', $event->id)->count());
        $this->assertSame(1, Event::where('id', $unrelated->id)->count());
    }

    public function test_sync_cleans_up_legacy_per_user_mirror_row_from_old_two_event_scheme(): void
    {
        $booker = User::factory()->create(['status' => 'active']);
        $related = User::factory()->create(['status' => 'active']);
        $reservation = $this->makeActiveMeetingReservation($booker, $related);
        $description = $this->description($reservation);

        $legacy = Event::create(['user_id' => $related->id, 'title' => 'جلسه با قدیمی', 'description' => $description, 'date' => $reservation->start_time, 'private' => true]);

        app(EventSyncService::class)->sync($reservation);

        $this->assertSame(0, Event::where('id', $legacy->id)->count());
        $this->assertSame(1, Event::where('description', $description)->count());
    }

    public function test_sync_replaces_stale_share_when_related_user_changes(): void
    {
        $booker = User::factory()->create(['status' => 'active']);
        $oldRelated = User::factory()->create(['status' => 'active']);
        $newRelated = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'meeting', 'status' => 'active', 'name' => $oldRelated->name]);
        $reservation = $this->makeActiveMeetingReservation($booker, $oldRelated, $resource);

        app(EventSyncService::class)->sync($reservation);

        $resource->update(['name' => $newRelated->name]);
        $reservation->unsetRelation('resource');
        $reservation->loadMissing(['user', 'resource.relatedUser']);

        app(EventSyncService::class)->sync($reservation);

        $event = Event::where('description', $this->description($reservation))->first();
        $shareUserIds = EventShare::where('event_id', $event->id)->pluck('user_id')->all();

        $this->assertSame([$newRelated->id], $shareUserIds);
    }

    public function test_sync_wires_share_into_the_existing_shared_events_indicators(): void
    {
        $booker = User::factory()->create(['status' => 'active']);
        $related = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'meeting', 'status' => 'active', 'name' => $related->name]);
        $start = Carbon::now()->addHours(12);
        $reservation = new Reservation([
            'user_id' => $booker->id,
            'resource_id' => $resource->id,
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'is_full_day' => false,
            'status' => ReservationStatus::Active->value,
        ]);
        $reservation->saveQuietly();
        $reservation->loadMissing(['user', 'resource.relatedUser']);

        app(EventSyncService::class)->sync($reservation);

        $this->assertTrue(EventShare::hasImminentFor($related), 'recipient badge helper should light for the booked colleague');
        $this->assertTrue(Event::hasImminentSharedFor($booker), 'owner badge helper should light for the booker');
    }

    public function test_purge_removes_stray_other_owner_event_even_when_related_user_no_longer_resolves(): void
    {
        $booker = User::factory()->create(['status' => 'active']);
        $related = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'meeting', 'status' => 'active', 'name' => $related->name]);
        $reservation = $this->makeActiveMeetingReservation($booker, $related, $resource);
        $description = $this->description($reservation);

        $stray = Event::create(['user_id' => $related->id, 'title' => 'جلسه با قدیمی', 'description' => $description, 'date' => $reservation->start_time, 'private' => true]);

        $resource->update(['name' => 'no-longer-matches-anyone']);
        $reservation->unsetRelation('resource');
        $reservation->loadMissing(['resource.relatedUser']);
        $this->assertNull($reservation->resource->relatedUser, 'sanity check: the rename must break the name-match');

        app(EventSyncService::class)->purge($reservation);

        $this->assertSame(0, Event::where('id', $stray->id)->count());
    }

    public function test_badge_helper_stays_dark_when_meeting_is_more_than_a_day_out(): void
    {
        $booker = User::factory()->create(['status' => 'active']);
        $related = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'meeting', 'status' => 'active', 'name' => $related->name]);
        $start = Carbon::now()->addDays(5);
        $reservation = new Reservation([
            'user_id' => $booker->id,
            'resource_id' => $resource->id,
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'is_full_day' => false,
            'status' => ReservationStatus::Active->value,
        ]);
        $reservation->saveQuietly();
        $reservation->loadMissing(['user', 'resource.relatedUser']);

        app(EventSyncService::class)->sync($reservation);

        $this->assertFalse(EventShare::hasImminentFor($related), 'badge must stay dark outside the 24h proximity window');
        $this->assertFalse(Event::hasImminentSharedFor($booker), 'owner badge must stay dark outside the 24h proximity window');
    }

    public function test_share_nudge_condition_fires_immediately_regardless_of_how_far_the_meeting_is(): void
    {
        $booker = User::factory()->create(['status' => 'active']);
        $related = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'meeting', 'status' => 'active', 'name' => $related->name]);
        $start = Carbon::now()->addDays(5);
        $reservation = new Reservation([
            'user_id' => $booker->id,
            'resource_id' => $resource->id,
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'is_full_day' => false,
            'status' => ReservationStatus::Active->value,
        ]);
        $reservation->saveQuietly();
        $reservation->loadMissing(['user', 'resource.relatedUser']);

        app(EventSyncService::class)->sync($reservation);

        $event = Event::where('description', $this->description($reservation))->first();
        $nudge = new SharedEventsNudge();
        $recipients = $nudge->for($event);

        $this->assertTrue($recipients->contains('id', $related->id), 'the invited colleague must be a nudge recipient right away, not only within 24h');
        $this->assertTrue($nudge->show($event, $related), 'the nudge condition for the recipient is date >= now, decoupled from the 24h badge window');
    }
}
