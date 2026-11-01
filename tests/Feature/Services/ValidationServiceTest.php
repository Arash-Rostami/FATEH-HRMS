<?php

namespace Tests\Feature\Services;

use App\Enums\ReservationError;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\ReservationPolicy;
use App\Models\Resource;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use App\Services\Reservation\ValidationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ValidationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        ModelCacheVersion::bump(ReservationPolicy::class);
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        ModelCacheVersion::bump(ReservationPolicy::class);
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

    private function setPolicy(string $type, string $key, mixed $value): void
    {
        $now = now();
        ReservationPolicy::upsert([
            ['resource_type' => $type, 'key' => $key, 'value' => json_encode($value), 'created_at' => $now, 'updated_at' => $now],
        ], ['resource_type', 'key'], ['value', 'updated_at']);

        app(ValidationService::class)->flushPolicyCache();
    }

    private function assertThrowsError(ReservationError $error, callable $fn): void
    {
        try {
            $fn();
            $this->fail("Expected ReservationError {$error->value} to be thrown.");
        } catch (\Exception $e) {
            $this->assertStringContainsString($error->value, $e->getMessage());
        }
    }

    public function test_flush_policy_cache_makes_previously_cached_policy_and_disabled_types_unreachable(): void
    {
        $policiesKey = ModelCacheVersion::key(ReservationPolicy::class, 'reservation_policies_seat');
        $disabledKey = ModelCacheVersion::key(ReservationPolicy::class, 'reservation_disabled_types');
        Cache::forever($policiesKey, ['window_days' => 5]);
        Cache::forever($disabledKey, ['seat']);

        app(ValidationService::class)->flushPolicyCache();

        $this->assertNotSame($policiesKey, ModelCacheVersion::key(ReservationPolicy::class, 'reservation_policies_seat'));
        $this->assertNotSame($disabledKey, ModelCacheVersion::key(ReservationPolicy::class, 'reservation_disabled_types'));
    }

    public function test_get_policies_caches_and_returns_key_value_map(): void
    {
        $this->setPolicy('seat', 'window_days', 7);
        $this->setPolicy('seat', 'min_duration_minutes', 30);

        $policies = app(ValidationService::class)->getPolicies('seat');

        $this->assertSame(7, $policies['window_days']);
        $this->assertSame(30, $policies['min_duration_minutes']);
        $this->assertSame($policies, Cache::get(ModelCacheVersion::key(ReservationPolicy::class, 'reservation_policies_seat')));
    }

    public function test_disabled_types_returns_types_whose_policy_is_inactive(): void
    {
        $this->setPolicy('meeting', 'window_days', 0);
        $this->setPolicy('meeting', 'window_hours', 0);

        $disabled = app(ValidationService::class)->disabledTypes();

        $this->assertContains('meeting', $disabled);
        $this->assertNotContains('seat', $disabled);
    }

    public function test_validate_cancellation_throws_not_active_when_reservation_is_not_active(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $reservation = Reservation::create([
            'user_id' => $owner->id,
            'resource_id' => $resource->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'is_full_day' => false,
            'status' => ReservationStatus::Released->value,
        ]);

        $this->assertThrowsError(ReservationError::NotActive, fn() => app(ValidationService::class)->validateCancellation($reservation->load('resource'), $owner));
    }

    public function test_validate_cancellation_blocks_user_once_hourly_booking_has_started_but_not_admin(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        $resource = Resource::factory()->create(['type' => 'meeting', 'status' => 'active']);
        $reservation = Reservation::create([
            'user_id' => $owner->id,
            'resource_id' => $resource->id,
            'start_time' => now()->subMinutes(10),
            'end_time' => now()->addHour(),
            'is_full_day' => false,
            'status' => ReservationStatus::Active->value,
        ]);

        $this->assertThrowsError(ReservationError::NotActive, fn() => app(ValidationService::class)->validateCancellation($reservation->load('resource'), $owner));

        app(ValidationService::class)->validateCancellation($reservation, $admin);
        $this->addToAssertionCount(1);
    }

    public function test_validate_cancellation_allows_full_day_booking_cancel_until_the_day_ends(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $reservation = Reservation::create([
            'user_id' => $owner->id,
            'resource_id' => $resource->id,
            'start_time' => now()->startOfDay(),
            'end_time' => now()->endOfDay(),
            'is_full_day' => true,
            'status' => ReservationStatus::Active->value,
        ]);

        app(ValidationService::class)->validateCancellation($reservation->load('resource'), $owner);
        $this->addToAssertionCount(1);
    }

    public function test_cancel_limit_stats_counts_a_series_cancel_once(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $stamp = now()->subDay();
        $master = Reservation::create([
            'user_id' => $owner->id,
            'resource_id' => $resource->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'is_full_day' => false,
            'status' => ReservationStatus::CancelledUser->value,
            'cancelled_at' => $stamp,
        ]);
        foreach ([2, 3] as $days) {
            Reservation::create([
                'user_id' => $owner->id,
                'resource_id' => $resource->id,
                'start_time' => now()->addDays($days),
                'end_time' => now()->addDays($days)->addHour(),
                'is_full_day' => false,
                'status' => ReservationStatus::CancelledUser->value,
                'cancelled_at' => $stamp,
                'parent_id' => $master->id,
            ]);
        }

        $this->assertSame(1, app(ValidationService::class)->cancelLimitStats($owner->id, 'seat')['count']);
    }

    public function test_validate_cancellation_throws_cancel_forbidden_for_non_owner_non_elevated_user(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $stranger = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $reservation = Reservation::create([
            'user_id' => $owner->id,
            'resource_id' => $resource->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'is_full_day' => false,
            'status' => ReservationStatus::Active->value,
        ]);

        $this->assertThrowsError(ReservationError::CancelForbidden, fn() => app(ValidationService::class)->validateCancellation($reservation->load('resource'), $stranger));
    }

    public function test_validate_cancellation_allows_elevated_user_to_bypass_ownership(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $reservation = Reservation::create([
            'user_id' => $owner->id,
            'resource_id' => $resource->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'is_full_day' => false,
            'status' => ReservationStatus::Active->value,
        ]);

        app(ValidationService::class)->validateCancellation($reservation->load('resource'), $admin);
        $this->assertTrue(true);
    }

    public function test_validate_cancellation_throws_cancel_limit_reached_when_prior_cancels_hit_policy_limit(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->setPolicy('seat', 'max_cancel_count', 1);

        Reservation::create([
            'user_id' => $owner->id,
            'resource_id' => $resource->id,
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHour(),
            'is_full_day' => false,
            'status' => ReservationStatus::CancelledUser->value,
            'cancelled_at' => now()->subDay(),
        ]);

        $reservation = Reservation::create([
            'user_id' => $owner->id,
            'resource_id' => $resource->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'is_full_day' => false,
            'status' => ReservationStatus::Active->value,
        ]);

        $this->assertThrowsError(ReservationError::CancelLimitReached, fn() => app(ValidationService::class)->validateCancellation($reservation->load('resource'), $owner));
    }

    public function test_validate_cancellation_window_follows_cancel_window_days_and_message_names_it(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->setPolicy('seat', 'max_cancel_count', 1);
        $this->setPolicy('seat', 'cancel_window_days', 60);

        Reservation::create([
            'user_id' => $owner->id,
            'resource_id' => $resource->id,
            'start_time' => now()->subDays(46),
            'end_time' => now()->subDays(46)->addHour(),
            'is_full_day' => false,
            'status' => ReservationStatus::CancelledUser->value,
            'cancelled_at' => now()->subDays(45),
        ]);

        $reservation = Reservation::create([
            'user_id' => $owner->id,
            'resource_id' => $resource->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'is_full_day' => false,
            'status' => ReservationStatus::Active->value,
        ]);

        try {
            app(ValidationService::class)->validateCancellation($reservation->load('resource'), $owner);
            $this->fail('A cancel 45 days ago must still count inside a 60-day window.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('۶۰', $e->getMessage());
        }
    }

    public function test_validate_cancellation_is_always_refused_when_the_cap_is_zero(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->setPolicy('seat', 'max_cancel_count', 0);

        $reservation = Reservation::create([
            'user_id' => $owner->id,
            'resource_id' => $resource->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'is_full_day' => false,
            'status' => ReservationStatus::Active->value,
        ]);

        $this->assertThrowsError(ReservationError::CancelLimitReached, fn() => app(ValidationService::class)->validateCancellation($reservation->load('resource'), $owner));
    }

    public function test_validate_cancellation_passes_under_the_cancel_limit(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->setPolicy('seat', 'max_cancel_count', 5);

        $reservation = Reservation::create([
            'user_id' => $owner->id,
            'resource_id' => $resource->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'is_full_day' => false,
            'status' => ReservationStatus::Active->value,
        ]);

        app(ValidationService::class)->validateCancellation($reservation->load('resource'), $owner);
        $this->assertTrue(true);
    }

    public function test_validate_booking_passes_for_valid_window_and_active_resource(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user', 'booking' => ['all' => true]]);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $start = Carbon::now()->addDays(2)->setHour(9)->setMinute(0)->setSecond(0);

        app(ValidationService::class)->validateBooking($user, $resource, $start, $start->copy()->addHour(), false);
        $this->assertTrue(true);
    }

    public function test_validate_booking_throws_invalid_time_range_when_start_is_not_before_end(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user', 'booking' => ['all' => true]]);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $start = Carbon::now()->addDays(2)->setHour(10);

        $this->assertThrowsError(ReservationError::InvalidTimeRange, fn() => app(ValidationService::class)->validateBooking($user, $resource, $start, $start->copy()->subMinutes(10), false));
    }

    public function test_validate_edit_throws_invalid_time_range_when_start_is_not_before_end(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user', 'booking' => ['all' => true]]);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $start = Carbon::now()->addDays(2)->setHour(10);
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'is_full_day' => false,
            'status' => ReservationStatus::Active->value,
        ]);

        $this->assertThrowsError(ReservationError::InvalidTimeRange, fn() => app(ValidationService::class)->validateEdit($reservation->load('resource'), $user, $start, $start->copy()->subMinutes(10), false));
    }
}