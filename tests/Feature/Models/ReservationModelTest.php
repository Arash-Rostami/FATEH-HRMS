<?php

namespace Tests\Feature\Models;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReservationModelTest extends TestCase
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

    public function test_scope_for_user_filters_by_user_id(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $res = Reservation::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        $otherRes = Reservation::factory()->create(['user_id' => $other->id, 'status' => 'active']);

        $this->assertContains($res->id, Reservation::forUser($user->id)->pluck('id')->all());
        $this->assertNotContains($otherRes->id, Reservation::forUser($user->id)->pluck('id')->all());
    }

    public function test_scope_roots_filters_to_null_parent_id(): void
    {
        $root = Reservation::factory()->create(['status' => 'active', 'parent_id' => null]);
        $child = Reservation::factory()->create(['status' => 'active', 'parent_id' => $root->id]);

        $this->assertContains($root->id, Reservation::roots()->pluck('id')->all());
        $this->assertNotContains($child->id, Reservation::roots()->pluck('id')->all());
    }

    public function test_scope_cancelled_filters_to_cancelled_statuses(): void
    {
        $active = Reservation::factory()->create(['status' => ReservationStatus::Active->value]);
        $cancelled = Reservation::factory()->create(['status' => ReservationStatus::CancelledUser->value]);

        $this->assertContains($cancelled->id, Reservation::cancelled()->pluck('id')->all());
        $this->assertNotContains($active->id, Reservation::cancelled()->pluck('id')->all());
    }

    public function test_scopes_held_and_overlapping_select_blocking_rows_inside_the_window(): void
    {
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $user = User::factory()->create(['status' => 'active']);
        $make = fn(string $status, string $start, string $end) => Reservation::create([
            'user_id' => $user->id, 'resource_id' => $resource->id, 'start_time' => $start, 'end_time' => $end, 'is_full_day' => false, 'status' => $status,
        ]);
        $active = $make(ReservationStatus::Active->value, '2031-05-10 09:00:00', '2031-05-10 10:00:00');
        $released = $make(ReservationStatus::Released->value, '2031-05-10 11:00:00', '2031-05-10 12:00:00');
        $cancelled = $make(ReservationStatus::CancelledUser->value, '2031-05-10 13:00:00', '2031-05-10 14:00:00');
        $outside = $make(ReservationStatus::Active->value, '2031-05-12 09:00:00', '2031-05-12 10:00:00');

        $from = \Carbon\Carbon::parse('2031-05-10 00:00:00');
        $to = \Carbon\Carbon::parse('2031-05-10 23:59:59');
        $ids = Reservation::where('resource_id', $resource->id)->held()->overlapping($from, $to)->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$active->id, $released->id], $ids);
        $this->assertNotContains($cancelled->id, $ids);
        $this->assertNotContains($outside->id, $ids);
        $this->assertContains($outside->id, Reservation::where('resource_id', $resource->id)->overlapping($from->copy()->addDays(2), $to->copy()->addDays(2))->pluck('id')->all());
    }

    public function test_scope_released_filters_to_released_status(): void
    {
        $released = Reservation::factory()->create(['status' => ReservationStatus::Released->value]);
        $active = Reservation::factory()->create(['status' => ReservationStatus::Active->value]);

        $this->assertContains($released->id, Reservation::released()->pluck('id')->all());
        $this->assertNotContains($active->id, Reservation::released()->pluck('id')->all());
    }

    public function test_scope_upcoming_filters_to_active_and_future_end_time(): void
    {
        $upcoming = Reservation::factory()->create([
            'status' => 'active',
            'end_time' => now()->addDays(5),
            'start_time' => now()->addDays(1),
        ]);
        $past = Reservation::factory()->create([
            'status' => 'active',
            'end_time' => now()->subDays(5),
            'start_time' => now()->subDays(10),
        ]);

        $this->assertContains($upcoming->id, Reservation::upcoming()->pluck('id')->all());
        $this->assertNotContains($past->id, Reservation::upcoming()->pluck('id')->all());
    }

    public function test_scope_previous_filters_to_active_and_past_end_time(): void
    {
        $past = Reservation::factory()->create([
            'status' => 'active',
            'end_time' => now()->subDays(5),
            'start_time' => now()->subDays(10),
        ]);
        $upcoming = Reservation::factory()->create([
            'status' => 'active',
            'end_time' => now()->addDays(5),
            'start_time' => now()->addDays(1),
        ]);

        $this->assertContains($past->id, Reservation::previous()->pluck('id')->all());
        $this->assertNotContains($upcoming->id, Reservation::previous()->pluck('id')->all());
    }

    public function test_scope_upcoming_and_previous_exclude_non_active_statuses(): void
    {
        $releasedUpcoming = Reservation::factory()->create([
            'status' => ReservationStatus::Released->value,
            'end_time' => now()->addDays(5),
            'start_time' => now()->addDays(1),
        ]);
        $releasedPast = Reservation::factory()->create([
            'status' => ReservationStatus::Released->value,
            'end_time' => now()->subDays(5),
            'start_time' => now()->subDays(10),
        ]);

        $this->assertNotContains($releasedUpcoming->id, Reservation::upcoming()->pluck('id')->all());
        $this->assertNotContains($releasedPast->id, Reservation::previous()->pluck('id')->all());
    }

    public function test_display_time_accessor_returns_full_day_label_for_full_day_reservation(): void
    {
        $reservation = new Reservation();
        $reservation->is_full_day = true;
        $reservation->start_time = now();
        $reservation->end_time = now();

        $this->assertStringContainsString('تمام روز', $reservation->display_time);
    }

    public function test_display_time_accessor_includes_time_range_for_non_full_day(): void
    {
        $reservation = new Reservation();
        $reservation->is_full_day = false;
        $reservation->start_time = now()->setHour(9)->setMinute(0);
        $reservation->end_time = now()->setHour(17)->setMinute(0);

        $this->assertStringContainsString('تا', $reservation->display_time);
    }

    public function test_is_long_hold_returns_false_for_a_full_day_reservation(): void
    {
        $reservation = new Reservation();
        $reservation->is_full_day = true;
        $reservation->start_time = now();
        $reservation->end_time = now()->addDays(30);

        $this->assertFalse($reservation->isLongHold());
    }

    public function test_is_long_hold_returns_false_below_the_threshold(): void
    {
        $reservation = new Reservation();
        $reservation->is_full_day = false;
        $reservation->start_time = now();
        $reservation->end_time = now()->addDays(Reservation::LONG_HOLD_DAYS - 1);

        $this->assertTrue($reservation->isRange());
        $this->assertFalse($reservation->isLongHold());
    }

    public function test_is_long_hold_returns_true_at_and_above_the_threshold(): void
    {
        $atThreshold = new Reservation();
        $atThreshold->is_full_day = false;
        $atThreshold->start_time = now();
        $atThreshold->end_time = now()->addDays(Reservation::LONG_HOLD_DAYS);

        $wellAbove = new Reservation();
        $wellAbove->is_full_day = false;
        $wellAbove->start_time = now();
        $wellAbove->end_time = now()->addDays(180);

        $this->assertTrue($atThreshold->isLongHold());
        $this->assertTrue($wellAbove->isLongHold());
    }

    public function test_is_full_day_cast_round_trips_boolean(): void
    {
        $reservation = Reservation::factory()->create(['is_full_day' => true]);

        $this->assertTrue($reservation->fresh()->is_full_day);
    }

    public function test_start_time_and_end_time_are_cast_to_datetime(): void
    {
        $reservation = Reservation::factory()->create([
            'start_time' => '2026-03-24 09:00:00',
            'end_time' => '2026-03-24 17:00:00',
            'status' => 'active',
        ]);

        $this->assertInstanceOf(\Carbon\CarbonInterface::class, $reservation->fresh()->start_time);
        $this->assertInstanceOf(\Carbon\CarbonInterface::class, $reservation->fresh()->end_time);
    }

    public function test_resource_dropdown_label_includes_resource_name_and_user_name(): void
    {
        $user = User::factory()->create(['status' => 'active', 'name' => 'Alice']);
        $resource = Resource::factory()->create(['name' => 'Meeting Room A', 'type' => 'meeting']);
        $reservation = Reservation::factory()->create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => now(),
            'status' => 'active',
        ]);
        $reservation->load('resource', 'user');

        $label = $reservation->resourceDropdownLabel;

        $this->assertStringContainsString('Alice', $label);
        $this->assertStringContainsString('Meeting Room A', $label);
    }
}