<?php

namespace Tests\Feature\Services;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\User;
use App\Livewire\Dashboard\Reservation\Actions\BookAction;
use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\EventSyncService;
use App\Services\Reservation\Validators\AllowedDays;
use App\Services\Reservation\Validators\RangeDuration;
use App\Services\Reservation\Validators\AllowedHours;
use App\Services\Reservation\Validators\Duration;
use App\Services\Reservation\Validators\ResourceAvailability;
use App\Services\Reservation\Validators\TimeWindow;
use App\Services\Reservation\Validators\UserConflict;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RangeBookingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-08-01 12:00:00'));
    }

    protected function tearDown(): void
    {
        DB::rollBack();
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

    private function context(Carbon $start, Carbon $end, bool $isFullDay = false, array $policies = [], ?int $excludeId = null): BookingContext
    {
        return new BookingContext(
            user: new User(),
            resource: new Resource(),
            start: $start,
            end: $end,
            isFullDay: $isFullDay,
            policies: $policies,
            excludeId: $excludeId,
        );
    }

    private function rangeContext(array $policies = []): BookingContext
    {
        return $this->context(
            Carbon::now(),
            Carbon::now()->addYear(),
            false,
            $policies,
        );
    }

    public function test_isRange_false_for_intraday_slot(): void
    {
        $this->assertFalse($this->context(Carbon::now(), Carbon::now()->addHour())->isRange());
    }

    public function test_isRange_false_for_short_overnight_under_24h(): void
    {
        $start = Carbon::parse('2026-08-01 22:00:00');
        $end = Carbon::parse('2026-08-02 01:00:00');
        $this->assertFalse($this->context($start, $end)->isRange());
    }

    public function test_isRange_true_for_year_long_span(): void
    {
        $this->assertTrue($this->rangeContext()->isRange());
    }

    public function test_isRange_false_when_full_day_even_if_end_far_ahead(): void
    {
        $this->assertFalse($this->context(Carbon::now(), Carbon::now()->addYear(), true)->isRange());
    }

    public function test_reservation_model_isRange_matches_context_semantics(): void
    {
        $range = Reservation::factory()->make([
            'start_time' => Carbon::now(),
            'end_time' => Carbon::now()->addYear(),
            'is_full_day' => false,
        ]);
        $this->assertTrue($range->isRange());

        $intraday = Reservation::factory()->make([
            'start_time' => Carbon::now(),
            'end_time' => Carbon::now()->addHour(),
            'is_full_day' => false,
        ]);
        $this->assertFalse($intraday->isRange());
    }

    public function test_duration_skipped_in_range_so_max_duration_minutes_does_not_block(): void
    {
        $rule = new Duration();
        $rule->validate($this->rangeContext(['max_duration_minutes' => 60]));
        $this->assertTrue(true);
    }

    public function test_allowed_hours_skipped_in_range(): void
    {
        $rule = new AllowedHours();
        $rule->validate($this->rangeContext([
            'allowed_hours' => ['start' => '09:00:00', 'end' => '17:00:00'],
        ]));
        $this->assertTrue(true);
    }

    public function test_allowed_days_skipped_in_range(): void
    {
        $rule = new AllowedDays();
        $rule->validate($this->rangeContext(['allowed_days' => ['monday', 'tuesday']]));
        $this->assertTrue(true);
    }

    public function test_time_window_future_cap_skipped_in_range_but_past_start_still_blocked(): void
    {
        $rule = new TimeWindow();

        $rule->validate($this->context(
            Carbon::now()->addMonths(6),
            Carbon::now()->addMonths(6)->addYear(),
            false,
            ['window_days' => 30],
        ));
        $this->assertTrue(true);

        $thrown = null;
        try {
            $rule->validate($this->context(
                Carbon::now()->subDay(),
                Carbon::now()->subDay()->addYear(),
                false,
                ['window_days' => 30],
            ));
        } catch (\Throwable $e) {
            $thrown = $e;
        }
        $this->assertNotNull($thrown, 'Past start must still be rejected in range mode.');
    }

    public function test_user_conflict_skipped_in_range(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'desk', 'status' => 'active']);
        Reservation::factory()->create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => Carbon::now()->addMonth(),
            'end_time' => Carbon::now()->addMonth()->addHour(),
            'is_full_day' => false,
            'status' => 'active',
        ]);

        $rule = new UserConflict();
        $rule->validate(new BookingContext(
            user: $user,
            resource: $resource,
            start: Carbon::now(),
            end: Carbon::now()->addYear(),
            isFullDay: false,
            policies: [],
        ));
        $this->assertTrue(true);
    }

    public function test_resource_availability_still_blocks_overlapping_range(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'desk', 'status' => 'active']);
        Reservation::factory()->create([
            'user_id' => $owner->id,
            'resource_id' => $resource->id,
            'start_time' => Carbon::now()->addMonth(),
            'end_time' => Carbon::now()->addMonth()->addHour(),
            'is_full_day' => false,
            'status' => 'active',
        ]);

        $rule = new ResourceAvailability();
        $thrown = null;
        try {
            $rule->validate(new BookingContext(
                user: User::factory()->create(['status' => 'active']),
                resource: $resource,
                start: Carbon::now(),
                end: Carbon::now()->addYear(),
                isFullDay: false,
                policies: [],
            ));
        } catch (\Throwable $e) {
            $thrown = $e;
        }
        $this->assertNotNull($thrown, 'ResourceAvailability must block an overlapping range.');
    }

    public function test_event_sync_creates_no_event_for_range_hold(): void
    {
        $booker = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'meeting', 'status' => 'active']);
        $reservation = Reservation::factory()->create([
            'user_id' => $booker->id,
            'resource_id' => $resource->id,
            'start_time' => Carbon::now(),
            'end_time' => Carbon::now()->addYear(),
            'is_full_day' => false,
            'status' => 'active',
        ]);

        app(EventSyncService::class)->sync($reservation->loadMissing(['user', 'resource']));

        $this->assertSame(0, Event::where('user_id', $booker->id)->count());
    }

    public function test_display_time_shows_end_date_when_cross_day(): void
    {
        $range = Reservation::factory()->make([
            'start_time' => Carbon::parse('2026-08-01 09:00:00'),
            'end_time' => Carbon::parse('2027-08-01 17:00:00'),
            'is_full_day' => false,
        ]);
        $intraday = Reservation::factory()->make([
            'start_time' => Carbon::parse('2026-08-01 09:00:00'),
            'end_time' => Carbon::parse('2026-08-01 17:00:00'),
            'is_full_day' => false,
        ]);

        $this->assertNotSame($range->display_time, $intraday->display_time);
        $this->assertSame(4, substr_count($range->display_time, '/'));
        $this->assertSame(2, substr_count($intraday->display_time, '/'));
    }

    public function test_book_action_rejects_range_for_non_admin(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $resource = Resource::factory()->create(['type' => 'desk', 'status' => 'active']);

        $thrown = null;
        try {
            app(BookAction::class)->execute($user, $resource, Carbon::now(), Carbon::now()->addYear(), false);
        } catch (\Throwable $e) {
            $thrown = $e;
        }

        $this->assertNotNull($thrown, 'Non-admin must not create a range booking.');
        $this->assertStringContainsString('ادمین', $thrown->getMessage());
    }

    public function test_range_duration_blocks_range_exceeding_max_range_days(): void
    {
        $rule = new RangeDuration();
        $thrown = null;
        try {
            $rule->validate($this->context(
                Carbon::now(),
                Carbon::now()->addDays(20),
                false,
                ['max_range_days' => 10],
            ));
        } catch (\Throwable $e) {
            $thrown = $e;
        }
        $this->assertNotNull($thrown);
        $this->assertStringContainsString('ERR-025', $thrown->getMessage());
    }

    public function test_range_duration_allows_range_within_max_range_days(): void
    {
        $rule = new RangeDuration();
        $rule->validate($this->context(
            Carbon::now(),
            Carbon::now()->addDays(5),
            false,
            ['max_range_days' => 10],
        ));
        $this->assertTrue(true);
    }

    public function test_range_duration_no_cap_when_policy_unset(): void
    {
        $rule = new RangeDuration();
        $rule->validate($this->context(Carbon::now(), Carbon::now()->addYear(), false, []));
        $this->assertTrue(true);
    }

    public function test_range_duration_ignored_for_non_range(): void
    {
        $rule = new RangeDuration();
        $rule->validate($this->context(Carbon::now(), Carbon::now()->addHour(), false, ['max_range_days' => 1]));
        $this->assertTrue(true);
    }
}