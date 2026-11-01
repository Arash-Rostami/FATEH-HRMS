<?php

namespace Tests\Feature\Services;

use App\Enums\ReservationError;
use App\Enums\ReservationStatus;
use App\Enums\ResourceType;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\User;
use App\Services\Reservation\Contracts\BookingContext;
use App\Services\Reservation\Validators\ActiveLimit;
use App\Services\Reservation\Validators\AllowedDays;
use App\Services\Reservation\Validators\AllowedHours;
use App\Services\Reservation\Validators\BookingPermission;
use App\Services\Reservation\Validators\CancellationLimit;
use App\Services\Reservation\Validators\Duration;
use App\Services\Reservation\Validators\FullDay;
use App\Services\Reservation\Validators\RangeDuration;
use App\Services\Reservation\Validators\Recurrence;
use App\Services\Reservation\Validators\ResourceActive;
use App\Services\Reservation\Validators\ResourceAvailability;
use App\Services\Reservation\Validators\ResourceSchedule;
use App\Services\Reservation\Validators\TimeWindow;
use App\Services\Reservation\Validators\TypeActive;
use App\Services\Reservation\Validators\UserActive;
use App\Services\Reservation\Validators\UserConflict;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReservationValidatorsTest extends TestCase
{
    private Carbon $activeLimitStart;

    private AllowedDays $allowedDaysRule;

    private AllowedHours $allowedHoursValidator;

    private Duration $durationRule;

    private FullDay $fullDayRule;

    private Recurrence $recurrenceValidator;

    private TypeActive $typeActiveValidator;

    private UserActive $userActiveValidator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-08-01 12:00:30'));
        $this->activeLimitStart = Carbon::now()->addMonth()->setDay(15)->setTime(10, 0, 0);
        $this->allowedDaysRule = new AllowedDays();
        $this->allowedHoursValidator = new AllowedHours();
        $this->durationRule = new Duration();
        $this->fullDayRule = new FullDay();
        $this->recurrenceValidator = new Recurrence();
        $this->typeActiveValidator = new TypeActive();
        $this->userActiveValidator = new UserActive();
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

    private function assertThrowsReservationError(ReservationError $error, callable $fn): void
    {
        $thrown = null;
        try {
            $fn();
        } catch (\Throwable $e) {
            $thrown = $e;
        }
        $this->assertNotNull($thrown, "Expected ReservationError {$error->value} to be thrown.");
        $this->assertStringContainsString($error->value, $thrown->getMessage());
    }

    private function assertThrowsReservationErrorStartsWith(ReservationError $error, callable $fn): void
    {
        $thrown = null;
        try {
            $fn();
        } catch (\Throwable $e) {
            $thrown = $e;
        }
        $this->assertNotNull($thrown, "Expected ReservationError {$error->value} to be thrown.");
        $this->assertStringStartsWith("[{$error->value}]", $thrown->getMessage());
    }

    private function assertThrowsReservationErrorWithArg(ReservationError $error, ?string $expectedArg, callable $fn): void
    {
        $thrown = null;
        try {
            $fn();
        } catch (\Throwable $e) {
            $thrown = $e;
        }
        $this->assertNotNull($thrown, "Expected ReservationError {$error->value} to be thrown.");
        $this->assertStringContainsString($error->value, $thrown->getMessage());
        if ($expectedArg !== null) {
            $this->assertStringContainsString((string) $expectedArg, $thrown->getMessage());
        }
    }

    private function assertThrowsDayNotAllowed(Carbon $start, callable $fn): void
    {
        $thrown = null;
        try {
            $fn();
        } catch (\Throwable $e) {
            $thrown = $e;
        }
        $this->assertNotNull($thrown, 'Expected ReservationError ERR-005 (DayNotAllowed) to be thrown.');
        $this->assertStringContainsString(ReservationError::DayNotAllowed->value, $thrown->getMessage());
        $this->assertStringContainsString($start->dayName, $thrown->getMessage());
    }

    private function assertThrowsNoPermission(callable $fn): void
    {
        $thrown = null;
        try {
            $fn();
        } catch (\Throwable $e) {
            $thrown = $e;
        }
        $this->assertNotNull($thrown, 'Expected ReservationError NoPermission to be thrown.');
        $this->assertStringContainsString(ReservationError::NoPermission->value, $thrown->getMessage());
    }

    private function assertThrowsFullDayForbidden(callable $fn): void
    {
        $thrown = null;
        try {
            $fn();
        } catch (\Throwable $e) {
            $thrown = $e;
        }
        $this->assertNotNull($thrown, 'Expected ReservationError ERR-006 to be thrown.');
        $this->assertStringContainsString(ReservationError::FullDayForbidden->value, $thrown->getMessage());
    }

    private function assertThrowsRecurForbidden(callable $fn): void
    {
        $thrown = null;
        try {
            $fn();
        } catch (\Throwable $e) {
            $thrown = $e;
        }
        $this->assertNotNull($thrown, 'Expected ReservationError ERR-010 (RecurForbidden) to be thrown.');
        $this->assertStringContainsString(ReservationError::RecurForbidden->value, $thrown->getMessage());
    }

    private function assertThrowsResourceTaken(callable $fn): void
    {
        $thrown = null;
        try {
            $fn();
        } catch (\Throwable $e) {
            $thrown = $e;
        }
        $this->assertNotNull($thrown, 'Expected ReservationError ' . ReservationError::ResourceTaken->value . ' to be thrown.');
        $prefix = '[' . ReservationError::ResourceTaken->value . ']';
        $this->assertSame($prefix, substr($thrown->getMessage(), 0, strlen($prefix)));
        $this->assertStringContainsString(ReservationError::ResourceTaken->value, $thrown->getMessage());
    }

    private function assertThrowsTimeConflict(callable $fn): void
    {
        $thrown = null;
        try {
            $fn();
        } catch (\Throwable $e) {
            $thrown = $e;
        }
        $this->assertNotNull($thrown, 'Expected ReservationError ERR-014 (TimeConflict) to be thrown.');
        $this->assertStringContainsString(ReservationError::TimeConflict->value, $thrown->getMessage());
    }

    private function assertThrowsTypeInactive(array $policies): void
    {
        $thrown = null;
        try {
            $this->typeActiveValidator->validate($this->typeActiveContext($policies));
        } catch (\Throwable $e) {
            $thrown = $e;
        }
        $this->assertNotNull($thrown, 'Expected ReservationError TypeInactive to be thrown.');
        $this->assertStringContainsString(ReservationError::TypeInactive->value, $thrown->getMessage());
    }

    private function assertTypeActivePasses(array $policies): void
    {
        $this->typeActiveValidator->validate($this->typeActiveContext($policies));
        $this->assertTrue(true);
    }

    private function durationPasses(BookingContext $context): bool
    {
        try {
            $this->durationRule->validate($context);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function allowedHoursPasses(Carbon $start, Carbon $end, array $policies = [], bool $isFullDay = false): void
    {
        $this->allowedHoursValidator->validate($this->allowedHoursContext($start, $end, $policies, $isFullDay));
        $this->assertTrue(true);
    }

    private function allowedHoursFails(ReservationError $error, Carbon $start, Carbon $end, array $policies = [], bool $isFullDay = false): void
    {
        $thrown = null;
        try {
            $this->allowedHoursValidator->validate($this->allowedHoursContext($start, $end, $policies, $isFullDay));
        } catch (\Throwable $e) {
            $thrown = $e;
        }
        $this->assertNotNull($thrown, "Expected ReservationError {$error->value} to be thrown.");
        $this->assertStringContainsString($error->value, $thrown->getMessage());
    }

    private function activeLimitContext(User $user, Resource $resource, array $policies, ?int $excludeId = null): BookingContext
    {
        return new BookingContext(
            user: $user,
            resource: $resource,
            start: $this->activeLimitStart->copy(),
            end: $this->activeLimitStart->copy()->addHour(),
            isFullDay: false,
            policies: $policies,
            recurrence: null,
            excludeId: $excludeId,
        );
    }

    private function activeLimitReserve(User $user, Resource $resource, ReservationStatus $status, ?Carbon $startTime = null): Reservation
    {
        $start = $startTime ?? $this->activeLimitStart->copy()->subDays(2)->setHour(9);
        return Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'is_full_day' => false,
            'status' => $status->value,
        ]);
    }

    private function allowedDaysContext(Carbon $start, array $policies, bool $isFullDay = false): BookingContext
    {
        return new BookingContext(
            user: new User(),
            resource: new Resource(),
            start: $start,
            end: $start->copy()->addHour(),
            isFullDay: $isFullDay,
            policies: $policies,
        );
    }

    private function allowedHoursContext(Carbon $start, Carbon $end, array $policies = [], bool $isFullDay = false): BookingContext
    {
        return new BookingContext(
            user: new User(['id' => 1, 'status' => 'active']),
            resource: new Resource(['id' => 1, 'status' => 'active']),
            start: $start,
            end: $end,
            isFullDay: $isFullDay,
            policies: $policies,
        );
    }

    private function bookingPermissionContext(User $user, Resource $resource): BookingContext
    {
        return new BookingContext(
            user: $user,
            resource: $resource,
            start: Carbon::now()->addDay(),
            end: Carbon::now()->addDay()->addHour(),
            isFullDay: false,
            policies: [],
        );
    }

    private function validateBookingPermission(BookingContext $context): void
    {
        app(BookingPermission::class)->validate($context);
    }

    private function cancellationContext(User $user, Resource $resource, array $policies = [], bool $isFullDay = false, ?array $recurrence = null): BookingContext
    {
        return new BookingContext(
            user: $user,
            resource: $resource,
            start: Carbon::now()->addDay()->setHour(9)->setMinute(0)->setSecond(0),
            end: Carbon::now()->addDay()->setHour(10)->setMinute(0)->setSecond(0),
            isFullDay: $isFullDay,
            policies: $policies,
            recurrence: $recurrence,
        );
    }

    private function seedCancel(User $user, Resource $resource, ReservationStatus $status, ?Carbon $cancelledAt = null): Reservation
    {
        return Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => Carbon::now()->subDays(2)->setHour(9),
            'end_time' => Carbon::now()->subDays(2)->setHour(10),
            'is_full_day' => false,
            'status' => $status->value,
            'cancelled_at' => $cancelledAt,
        ]);
    }

    private function durationContext(Carbon $start, Carbon $end, bool $isFullDay = false, array $policies = [], ?array $recurrence = null, ?int $excludeId = null): BookingContext
    {
        return new BookingContext(
            user: new User(),
            resource: new Resource(),
            start: $start,
            end: $end,
            isFullDay: $isFullDay,
            policies: $policies,
            recurrence: $recurrence,
            excludeId: $excludeId,
        );
    }

    private function rangeDurationContext(Carbon $start, Carbon $end, array $policies = [], bool $isFullDay = false): BookingContext
    {
        return new BookingContext(
            user: new User(),
            resource: new Resource(),
            start: $start,
            end: $end,
            isFullDay: $isFullDay,
            policies: $policies,
        );
    }

    private function fullDayContext(bool $isFullDay, array $policies = []): BookingContext
    {
        return new BookingContext(
            user: new User([]),
            resource: new Resource([]),
            start: Carbon::now()->addDay()->startOfDay(),
            end: Carbon::now()->addDay()->endOfDay(),
            isFullDay: $isFullDay,
            policies: $policies,
        );
    }

    private function recurrenceContext(array $policies, ?array $recurrence = null, bool $isFullDay = false): BookingContext
    {
        $start = Carbon::now()->addDay();
        return new BookingContext(
            user: new User(),
            resource: new Resource(),
            start: $start,
            end: $start->copy()->addHour(),
            isFullDay: $isFullDay,
            policies: $policies,
            recurrence: $recurrence,
        );
    }

    private function resourceActiveContext(User $user, Resource $resource, array $policies = []): BookingContext
    {
        return new BookingContext(
            user: $user,
            resource: $resource,
            start: Carbon::now()->addDay(),
            end: Carbon::now()->addDay()->addHour(),
            isFullDay: false,
            policies: $policies,
        );
    }

    private function resourceAvailabilityContext(Resource $resource, Carbon $start, Carbon $end, array $policies = [], ?int $excludeId = null, bool $isFullDay = false): BookingContext
    {
        return new BookingContext(
            user: User::factory()->create(['status' => 'active']),
            resource: $resource,
            start: $start,
            end: $end,
            isFullDay: $isFullDay,
            policies: $policies,
            recurrence: null,
            excludeId: $excludeId,
        );
    }

    private function resourceAvailabilityReserve(Resource $resource, ReservationStatus $status, ?Carbon $start, ?Carbon $end, bool $isFullDay = false): Reservation
    {
        return Reservation::create([
            'user_id' => User::factory()->create(['status' => 'active'])->id,
            'resource_id' => $resource->id,
            'start_time' => $start,
            'end_time' => $end,
            'is_full_day' => $isFullDay,
            'status' => $status->value,
        ]);
    }

    private function resourceAvailabilityValidator(): ResourceAvailability
    {
        return app(ResourceAvailability::class);
    }

    private function resourceScheduleContext(Resource $resource, Carbon $start, Carbon $end, bool $isFullDay = false): BookingContext
    {
        return new BookingContext(
            user: new User(),
            resource: $resource,
            start: $start,
            end: $end,
            isFullDay: $isFullDay,
            policies: [],
        );
    }

    private function timeWindowContext(Carbon $start, array $policies = [], bool $isFullDay = false): BookingContext
    {
        return new BookingContext(
            user: new User(['status' => 'active']),
            resource: new Resource(['type' => 'seat', 'status' => 'active']),
            start: $start,
            end: $start->copy()->addHour(),
            isFullDay: $isFullDay,
            policies: $policies,
        );
    }

    private function validateTimeWindow(BookingContext $context): void
    {
        app(TimeWindow::class)->validate($context);
    }

    private function typeActiveContext(array $policies): BookingContext
    {
        $start = Carbon::now()->addDay();
        return new BookingContext(
            user: new User(),
            resource: new Resource(),
            start: $start,
            end: $start->copy()->addHour(),
            isFullDay: false,
            policies: $policies,
        );
    }

    private function userActiveContext(User $user, array $policies = []): BookingContext
    {
        return new BookingContext(
            user: $user,
            resource: new Resource(),
            start: Carbon::now()->addDay()->setHour(9)->setMinute(0)->setSecond(0),
            end: Carbon::now()->addDay()->setHour(10)->setMinute(0)->setSecond(0),
            isFullDay: false,
            policies: $policies,
        );
    }

    private function userConflictContext(User $user, Resource $resource, Carbon $start, Carbon $end, bool $isFullDay = false, ?int $excludeId = null): BookingContext
    {
        return new BookingContext(
            user: $user,
            resource: $resource,
            start: $start,
            end: $end,
            isFullDay: $isFullDay,
            policies: [],
            recurrence: null,
            excludeId: $excludeId,
        );
    }

    private function userConflictReserve(User $user, Resource $resource, ?Carbon $start, ?Carbon $end, string $status, bool $isFullDay = false): Reservation
    {
        return Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => $start,
            'end_time' => $end,
            'is_full_day' => $isFullDay,
            'status' => $status,
        ]);
    }

    public function test_active_limit_passes_when_max_per_user_key_missing(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);

        (new ActiveLimit())->validate($this->activeLimitContext($user, $resource, []));
        $this->assertTrue(true);
    }

    public function test_active_limit_passes_when_max_per_user_is_null(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->activeLimitReserve($user, $resource, ReservationStatus::Active);

        (new ActiveLimit())->validate($this->activeLimitContext($user, $resource, ['max_per_user' => null]));
        $this->assertTrue(true);
    }

    public function test_active_limit_passes_when_active_count_one_below_max(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->activeLimitReserve($user, $resource, ReservationStatus::Active);

        (new ActiveLimit())->validate($this->activeLimitContext($user, $resource, ['max_per_user' => 2]));
        $this->assertTrue(true);
    }

    public function test_active_limit_throws_when_count_equals_max(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->activeLimitReserve($user, $resource, ReservationStatus::Active);
        $this->activeLimitReserve($user, $resource, ReservationStatus::Active);

        $this->assertThrowsReservationErrorStartsWith(
            ReservationError::ActiveLimitReached,
            fn() => (new ActiveLimit())->validate($this->activeLimitContext($user, $resource, ['max_per_user' => 2])),
        );
    }

    public function test_active_limit_throws_when_count_exceeds_max(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->activeLimitReserve($user, $resource, ReservationStatus::Active);

        $this->assertThrowsReservationErrorStartsWith(
            ReservationError::ActiveLimitReached,
            fn() => (new ActiveLimit())->validate($this->activeLimitContext($user, $resource, ['max_per_user' => 1])),
        );
    }

    public function test_active_limit_throws_when_max_is_zero(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);

        $this->assertThrowsReservationErrorStartsWith(
            ReservationError::ActiveLimitReached,
            fn() => (new ActiveLimit())->validate($this->activeLimitContext($user, $resource, ['max_per_user' => 0])),
        );
    }

    public function test_active_limit_exclude_id_excludes_reservation_from_count(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $own = $this->activeLimitReserve($user, $resource, ReservationStatus::Active);

        (new ActiveLimit())->validate($this->activeLimitContext($user, $resource, ['max_per_user' => 1], $own->id));
        $this->assertTrue(true);
    }

    public function test_active_limit_only_counts_reservations_matching_resource_type(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $other = Resource::factory()->create(['type' => 'meeting', 'status' => 'active']);
        $target = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->activeLimitReserve($user, $other, ReservationStatus::Active);

        (new ActiveLimit())->validate($this->activeLimitContext($user, $target, ['max_per_user' => 1]));
        $this->assertTrue(true);
    }

    public function test_active_limit_only_counts_reservations_in_start_month_and_year(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $lastMonth = $this->activeLimitStart->copy()->subMonth();
        $this->activeLimitReserve($user, $resource, ReservationStatus::Active, $lastMonth);

        (new ActiveLimit())->validate($this->activeLimitContext($user, $resource, ['max_per_user' => 1]));
        $this->assertTrue(true);
    }

    public function test_active_limit_quota_month_is_the_jalali_month_not_the_gregorian_one(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);

        // Quota month under test: Shahrivar 1405 (23 Aug - 22 Sep). A booking on 31 Mordad (22 Aug) must not count.
        $this->activeLimitReserve($user, $resource, ReservationStatus::Active, Carbon::parse('2026-08-22 09:00:00'));
        (new ActiveLimit())->validate($this->activeLimitContext($user, $resource, ['max_per_user' => 1]));

        // 1 Shahrivar 1405 = 23 Aug 2026: same Jalali month as 15 Sep although it is a different Gregorian month.
        $this->activeLimitReserve($user, $resource, ReservationStatus::Active, Carbon::parse('2026-08-23 09:00:00'));
        $this->assertThrowsReservationErrorStartsWith(ReservationError::ActiveLimitReached, fn() => (new ActiveLimit())->validate($this->activeLimitContext($user, $resource, ['max_per_user' => 1])));
    }

    public function test_active_limit_count_uses_the_same_jalali_month_bounds(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->activeLimitReserve($user, $resource, ReservationStatus::Active, Carbon::parse('2026-08-23 00:30:00'));
        $this->activeLimitReserve($user, $resource, ReservationStatus::Active, Carbon::parse('2026-09-22 23:00:00'));
        $this->activeLimitReserve($user, $resource, ReservationStatus::Active, Carbon::parse('2026-09-23 00:30:00'));

        $count = app(\App\Services\Reservation\ValidationService::class)->activeLimitCount($user->id, 'seat', Carbon::parse('2026-09-15'));
        $this->assertSame(2, $count);
        [$start, $end] = jalaliMonthBounds(Carbon::parse('2026-09-15'));
        $this->assertSame('2026-08-23', $start->toDateString());
        $this->assertSame('2026-09-22', $end->toDateString());
    }

    public function test_active_limit_only_counts_active_and_released_reservations(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->activeLimitReserve($user, $resource, ReservationStatus::CancelledUser);
        $this->activeLimitReserve($user, $resource, ReservationStatus::CancelledAdmin);

        (new ActiveLimit())->validate($this->activeLimitContext($user, $resource, ['max_per_user' => 1]));
        $this->assertTrue(true);
    }

    public function test_active_limit_released_reservations_count_against_limit(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->activeLimitReserve($user, $resource, ReservationStatus::Released);

        $this->assertThrowsReservationErrorStartsWith(
            ReservationError::ActiveLimitReached,
            fn() => (new ActiveLimit())->validate($this->activeLimitContext($user, $resource, ['max_per_user' => 1])),
        );
    }

    public function test_active_limit_user_override_replaces_policy_cap_downward(): void
    {
        $user = User::factory()->create(['status' => 'active', 'booking' => [['key' => 'seat', 'value' => true, 'max' => 1]]]);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->activeLimitReserve($user, $resource, ReservationStatus::Active);

        $this->assertThrowsReservationErrorStartsWith(
            ReservationError::ActiveLimitReached,
            fn() => (new ActiveLimit())->validate($this->activeLimitContext($user, $resource, ['max_per_user' => 5])),
        );
    }

    public function test_active_limit_user_override_replaces_policy_cap_upward(): void
    {
        $user = User::factory()->create(['status' => 'active', 'booking' => [['key' => 'seat', 'value' => true, 'max' => 3]]]);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->activeLimitReserve($user, $resource, ReservationStatus::Active);
        $this->activeLimitReserve($user, $resource, ReservationStatus::Active);

        (new ActiveLimit())->validate($this->activeLimitContext($user, $resource, ['max_per_user' => 1]));
        $this->assertTrue(true);
    }

    public function test_active_limit_all_row_max_replaces_policy_for_types_without_own_value_and_own_beats_it(): void
    {
        $booking = [['key' => 'all', 'value' => true, 'max' => 2], ['key' => 'spot', 'value' => true, 'max' => 1]];
        $user = User::factory()->create(['status' => 'active', 'booking' => $booking]);
        $seat = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $spot = Resource::factory()->create(['type' => 'spot', 'status' => 'active']);
        $this->activeLimitReserve($user, $seat, ReservationStatus::Active);
        $this->activeLimitReserve($user, $spot, ReservationStatus::Active);

        (new ActiveLimit())->validate($this->activeLimitContext($user, $seat, ['max_per_user' => 1]));

        $this->assertThrowsReservationErrorStartsWith(
            ReservationError::ActiveLimitReached,
            fn() => (new ActiveLimit())->validate($this->activeLimitContext($user, $spot, ['max_per_user' => 9])),
        );
    }

    public function test_active_limit_user_override_of_another_type_or_blank_falls_back_to_policy(): void
    {
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);

        foreach ([[['key' => 'meeting', 'value' => true, 'max' => 9]], [['key' => 'seat', 'value' => true, 'max' => null]]] as $booking) {
            $user = User::factory()->create(['status' => 'active', 'booking' => $booking]);
            $this->activeLimitReserve($user, $resource, ReservationStatus::Active);

            $this->assertThrowsReservationErrorStartsWith(
                ReservationError::ActiveLimitReached,
                fn() => (new ActiveLimit())->validate($this->activeLimitContext($user, $resource, ['max_per_user' => 1])),
            );
        }
    }

    private function lastMinuteSetup(array $booking = []): array
    {
        $this->travelTo(Carbon::create(2026, 10, 10, 9, 0, 0));
        $user = User::factory()->create(['status' => 'active', 'booking' => $booking]);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->activeLimitReserve($user, $resource, ReservationStatus::Active, Carbon::create(2026, 10, 20, 9, 0, 0));

        return [$user, $resource];
    }

    private function lastMinuteContext(User $user, Resource $resource, int $hoursAhead, array $policies): BookingContext
    {
        $start = now()->addHours($hoursAhead);

        return new BookingContext(user: $user, resource: $resource, start: $start, end: $start->copy()->addHour(), isFullDay: false, policies: $policies);
    }

    public function test_active_limit_last_minute_lets_a_capped_user_book_inside_the_window_including_the_boundary(): void
    {
        [$user, $resource] = $this->lastMinuteSetup();

        foreach ([10, 24] as $hoursAhead) {
            (new ActiveLimit())->validate($this->lastMinuteContext($user, $resource, $hoursAhead, ['max_per_user' => 1, 'last_minute_hours' => 24]));
        }

        $this->assertTrue(true);
        $this->travelBack();
    }

    public function test_active_limit_last_minute_still_blocks_beyond_the_window_or_when_the_policy_is_empty(): void
    {
        [$user, $resource] = $this->lastMinuteSetup();

        $this->assertThrowsReservationErrorStartsWith(
            ReservationError::ActiveLimitReached,
            fn() => (new ActiveLimit())->validate($this->lastMinuteContext($user, $resource, 25, ['max_per_user' => 1, 'last_minute_hours' => 24])),
        );
        $this->assertThrowsReservationErrorStartsWith(
            ReservationError::ActiveLimitReached,
            fn() => (new ActiveLimit())->validate($this->lastMinuteContext($user, $resource, 10, ['max_per_user' => 1, 'last_minute_hours' => null])),
        );
        $this->assertThrowsReservationErrorStartsWith(
            ReservationError::ActiveLimitReached,
            fn() => (new ActiveLimit())->validate($this->lastMinuteContext($user, $resource, 10, ['max_per_user' => 1])),
        );
        $this->travelBack();
    }

    public function test_active_limit_last_minute_also_relaxes_a_personal_cap(): void
    {
        [$user, $resource] = $this->lastMinuteSetup([['key' => 'seat', 'value' => true, 'max' => 1]]);

        (new ActiveLimit())->validate($this->lastMinuteContext($user, $resource, 10, ['max_per_user' => 5, 'last_minute_hours' => 24]));

        $this->assertThrowsReservationErrorStartsWith(
            ReservationError::ActiveLimitReached,
            fn() => (new ActiveLimit())->validate($this->lastMinuteContext($user, $resource, 30, ['max_per_user' => 5, 'last_minute_hours' => 24])),
        );
        $this->travelBack();
    }

    public function test_active_limit_passes_for_full_day_context(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);

        $context = new BookingContext(
            user: $user,
            resource: $resource,
            start: $this->activeLimitStart->copy(),
            end: $this->activeLimitStart->copy()->addDay(),
            isFullDay: true,
            policies: ['max_per_user' => 1],
        );

        (new ActiveLimit())->validate($context);
        $this->assertTrue(true);
    }

    public function test_allowed_days_passes_when_policy_key_absent(): void
    {
        $start = Carbon::create(2026, 8, 1, 10, 0, 0);

        $this->allowedDaysRule->validate($this->allowedDaysContext($start, ['window_days' => 7]));
        $this->assertTrue(true);
    }

    public function test_allowed_days_passes_when_policy_null(): void
    {
        $start = Carbon::create(2026, 8, 1, 10, 0, 0);

        $this->allowedDaysRule->validate($this->allowedDaysContext($start, ['allowed_days' => null]));
        $this->assertTrue(true);
    }

    public function test_allowed_days_passes_when_policy_not_array(): void
    {
        $start = Carbon::create(2026, 8, 1, 10, 0, 0);

        $this->allowedDaysRule->validate($this->allowedDaysContext($start, ['allowed_days' => 'saturday']));
        $this->assertTrue(true);
    }

    public function test_allowed_days_passes_when_start_weekday_listed(): void
    {
        $start = Carbon::create(2026, 8, 1, 10, 0, 0);

        $this->allowedDaysRule->validate($this->allowedDaysContext($start, ['allowed_days' => ['saturday']]));
        $this->assertTrue(true);
    }

    public function test_allowed_days_passes_when_start_weekday_matches_one_among_many(): void
    {
        $start = Carbon::create(2026, 8, 2, 10, 0, 0);

        $this->allowedDaysRule->validate($this->allowedDaysContext($start, ['allowed_days' => ['saturday', 'sunday', 'monday']]));
        $this->assertTrue(true);
    }

    public function test_allowed_days_passes_when_all_seven_weekdays_allowed(): void
    {
        $start = Carbon::create(2026, 8, 1, 10, 0, 0);
        $all = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        $this->allowedDaysRule->validate($this->allowedDaysContext($start, ['allowed_days' => $all]));
        $this->assertTrue(true);
    }

    public function test_allowed_days_throws_when_start_weekday_not_listed(): void
    {
        $start = Carbon::create(2026, 8, 1, 10, 0, 0);

        $this->assertThrowsDayNotAllowed($start, fn () => $this->allowedDaysRule->validate($this->allowedDaysContext($start, ['allowed_days' => ['sunday']])));
    }

    public function test_allowed_days_throws_when_array_empty(): void
    {
        $start = Carbon::create(2026, 8, 1, 10, 0, 0);

        $this->assertThrowsDayNotAllowed($start, fn () => $this->allowedDaysRule->validate($this->allowedDaysContext($start, ['allowed_days' => []])));
    }

    public function test_allowed_days_throws_when_entries_capitalized(): void
    {
        $start = Carbon::create(2026, 8, 1, 10, 0, 0);

        $this->assertThrowsDayNotAllowed($start, fn () => $this->allowedDaysRule->validate($this->allowedDaysContext($start, ['allowed_days' => ['Saturday']])));
    }

    public function test_allowed_days_full_day_behaves_identically_to_timed(): void
    {
        $start = Carbon::create(2026, 8, 1, 10, 0, 0);

        $this->assertThrowsDayNotAllowed($start, fn () => $this->allowedDaysRule->validate($this->allowedDaysContext($start, ['allowed_days' => ['sunday']], true)));
        $this->allowedDaysRule->validate($this->allowedDaysContext($start, ['allowed_days' => ['saturday']], true));
        $this->assertTrue(true);
    }

    public function test_allowed_days_ignores_recurrence_and_exclude_id(): void
    {
        $start = Carbon::create(2026, 8, 1, 10, 0, 0);
        $context = new BookingContext(
            user: new User(),
            resource: new Resource(),
            start: $start,
            end: $start->copy()->addHour(),
            isFullDay: false,
            policies: ['allowed_days' => ['saturday']],
            recurrence: ['freq' => 'weekly', 'count' => 5],
            excludeId: 42,
        );

        $this->allowedDaysRule->validate($context);
        $this->assertTrue(true);
    }

    public function test_allowed_days_evaluates_only_start_weekday_not_end(): void
    {
        $start = Carbon::create(2026, 8, 1, 23, 0, 0);
        $end = $start->copy()->addHours(2);

        $context = new BookingContext(
            user: new User(),
            resource: new Resource(),
            start: $start,
            end: $end,
            isFullDay: false,
            policies: ['allowed_days' => ['saturday']],
        );

        $this->allowedDaysRule->validate($context);
        $this->assertTrue(true);
    }

    public function test_allowed_hours_is_full_day_bypasses_check(): void
    {
        $start = Carbon::parse('2026-08-15 03:00:00');
        $end = Carbon::parse('2026-08-15 04:00:00');
        $this->allowedHoursPasses($start, $end, ['allowed_hours' => ['start' => '08:00:00', 'end' => '18:00:00']], true);
    }

    public function test_allowed_hours_null_policy_passes(): void
    {
        $start = Carbon::parse('2026-08-15 09:00:00');
        $end = Carbon::parse('2026-08-15 10:00:00');
        $this->allowedHoursPasses($start, $end);
    }

    public function test_allowed_hours_missing_key_passes(): void
    {
        $start = Carbon::parse('2026-08-15 09:00:00');
        $end = Carbon::parse('2026-08-15 10:00:00');
        $this->allowedHoursPasses($start, $end, ['window_days' => 7]);
    }

    public function test_allowed_hours_empty_array_passes(): void
    {
        $start = Carbon::parse('2026-08-15 09:00:00');
        $end = Carbon::parse('2026-08-15 10:00:00');
        $this->allowedHoursPasses($start, $end, ['allowed_hours' => []]);
    }

    public function test_allowed_hours_within_window_passes(): void
    {
        $start = Carbon::parse('2026-08-15 09:00:00');
        $end = Carbon::parse('2026-08-15 17:00:00');
        $this->allowedHoursPasses($start, $end, ['allowed_hours' => ['start' => '08:00:00', 'end' => '18:00:00']]);
    }

    public function test_allowed_hours_start_before_window_throws(): void
    {
        $start = Carbon::parse('2026-08-15 07:00:00');
        $end = Carbon::parse('2026-08-15 09:00:00');
        $this->allowedHoursFails(ReservationError::HourNotAllowed, $start, $end, ['allowed_hours' => ['start' => '08:00:00', 'end' => '18:00:00']]);
    }

    public function test_allowed_hours_end_after_window_throws(): void
    {
        $start = Carbon::parse('2026-08-15 17:00:00');
        $end = Carbon::parse('2026-08-15 19:00:00');
        $this->allowedHoursFails(ReservationError::HourNotAllowed, $start, $end, ['allowed_hours' => ['start' => '08:00:00', 'end' => '18:00:00']]);
    }

    public function test_allowed_hours_start_on_window_start_passes(): void
    {
        $start = Carbon::parse('2026-08-15 08:00:00');
        $end = Carbon::parse('2026-08-15 09:00:00');
        $this->allowedHoursPasses($start, $end, ['allowed_hours' => ['start' => '08:00:00', 'end' => '18:00:00']]);
    }

    public function test_allowed_hours_end_on_window_end_passes(): void
    {
        $start = Carbon::parse('2026-08-15 17:00:00');
        $end = Carbon::parse('2026-08-15 18:00:00');
        $this->allowedHoursPasses($start, $end, ['allowed_hours' => ['start' => '08:00:00', 'end' => '18:00:00']]);
    }

    public function test_allowed_hours_missing_start_defaults_midnight_passes(): void
    {
        $start = Carbon::parse('2026-08-15 09:00:00');
        $end = Carbon::parse('2026-08-15 09:30:00');
        $this->allowedHoursPasses($start, $end, ['allowed_hours' => ['end' => '10:00:00']]);
    }

    public function test_allowed_hours_missing_end_defaults_end_of_day_passes(): void
    {
        $start = Carbon::parse('2026-08-15 09:00:00');
        $end = Carbon::parse('2026-08-15 22:00:00');
        $this->allowedHoursPasses($start, $end, ['allowed_hours' => ['start' => '08:00:00']]);
    }

    public function test_allowed_hours_missing_end_before_window_throws(): void
    {
        $start = Carbon::parse('2026-08-15 07:00:00');
        $end = Carbon::parse('2026-08-15 07:30:00');
        $this->allowedHoursFails(ReservationError::HourNotAllowed, $start, $end, ['allowed_hours' => ['start' => '08:00:00']]);
    }

    public function test_allowed_hours_overnight_late_night_passes(): void
    {
        $start = Carbon::parse('2026-08-15 23:00:00');
        $end = Carbon::parse('2026-08-15 23:30:00');
        $this->allowedHoursPasses($start, $end, ['allowed_hours' => ['start' => '22:00:00', 'end' => '06:00:00']]);
    }

    public function test_allowed_hours_overnight_early_morning_passes(): void
    {
        $start = Carbon::parse('2026-08-15 02:00:00');
        $end = Carbon::parse('2026-08-15 02:30:00');
        $this->allowedHoursPasses($start, $end, ['allowed_hours' => ['start' => '22:00:00', 'end' => '06:00:00']]);
    }

    public function test_allowed_hours_overnight_daytime_throws(): void
    {
        $start = Carbon::parse('2026-08-15 08:00:00');
        $end = Carbon::parse('2026-08-15 09:00:00');
        $this->allowedHoursFails(ReservationError::HourNotAllowed, $start, $end, ['allowed_hours' => ['start' => '22:00:00', 'end' => '06:00:00']]);
    }

    public function test_allowed_hours_daytime_cannot_cheat_via_overnight_shift(): void
    {
        $start = Carbon::parse('2026-08-15 23:30:00');
        $end = Carbon::parse('2026-08-15 23:59:00');
        $this->allowedHoursFails(ReservationError::HourNotAllowed, $start, $end, ['allowed_hours' => ['start' => '10:00:00', 'end' => '23:00:00']]);
    }

    public function test_booking_permission_passes_when_all_access_true(): void
    {
        $user = new User(['booking' => ['all' => true]]);
        $resource = new Resource(['type' => 'meeting']);

        $this->validateBookingPermission($this->bookingPermissionContext($user, $resource));
        $this->assertTrue(true);
    }

    public function test_booking_permission_passes_when_type_specific_access_true(): void
    {
        $user = new User(['booking' => ['car' => true]]);
        $resource = new Resource(['type' => 'car']);

        $this->validateBookingPermission($this->bookingPermissionContext($user, $resource));
        $this->assertTrue(true);
    }

    public function test_booking_permission_passes_when_all_true_even_if_type_false(): void
    {
        $user = new User(['booking' => ['all' => true, 'seat' => false]]);
        $resource = new Resource(['type' => 'seat']);

        $this->validateBookingPermission($this->bookingPermissionContext($user, $resource));
        $this->assertTrue(true);
    }

    public function test_booking_permission_passes_when_all_absent_but_type_true(): void
    {
        $user = new User(['booking' => ['spot' => true]]);
        $resource = new Resource(['type' => 'spot']);

        $this->validateBookingPermission($this->bookingPermissionContext($user, $resource));
        $this->assertTrue(true);
    }

    public function test_booking_permission_passes_when_all_true_for_every_type(): void
    {
        foreach (ResourceType::cases() as $type) {
            $user = new User(['booking' => ['all' => true]]);
            $resource = new Resource(['type' => $type->value]);

            $this->validateBookingPermission($this->bookingPermissionContext($user, $resource));
        }
        $this->assertTrue(true);
    }

    public function test_booking_permission_throws_when_booking_empty(): void
    {
        $user = new User(['booking' => []]);
        $resource = new Resource(['type' => 'seat']);

        $this->assertThrowsNoPermission(fn() => $this->validateBookingPermission($this->bookingPermissionContext($user, $resource)));
    }

    public function test_booking_permission_throws_when_booking_null(): void
    {
        $user = new User(['booking' => null]);
        $resource = new Resource(['type' => 'seat']);

        $this->assertThrowsNoPermission(fn() => $this->validateBookingPermission($this->bookingPermissionContext($user, $resource)));
    }

    public function test_booking_permission_throws_when_all_false_and_type_absent(): void
    {
        $user = new User(['booking' => ['all' => false]]);
        $resource = new Resource(['type' => 'seat']);

        $this->assertThrowsNoPermission(fn() => $this->validateBookingPermission($this->bookingPermissionContext($user, $resource)));
    }

    public function test_booking_permission_throws_when_all_false_and_type_false(): void
    {
        $user = new User(['booking' => ['all' => false, 'seat' => false]]);
        $resource = new Resource(['type' => 'seat']);

        $this->assertThrowsNoPermission(fn() => $this->validateBookingPermission($this->bookingPermissionContext($user, $resource)));
    }

    public function test_booking_permission_throws_when_granted_type_differs(): void
    {
        $user = new User(['booking' => ['seat' => true]]);
        $resource = new Resource(['type' => 'meeting']);

        $this->assertThrowsNoPermission(fn() => $this->validateBookingPermission($this->bookingPermissionContext($user, $resource)));
    }

    public function test_booking_permission_throws_when_all_truthy_one_not_strict_true(): void
    {
        $user = new User(['booking' => ['all' => 1]]);
        $resource = new Resource(['type' => 'seat']);

        $this->assertThrowsNoPermission(fn() => $this->validateBookingPermission($this->bookingPermissionContext($user, $resource)));
    }

    public function test_booking_permission_throws_when_type_value_truthy_one_not_strict_true(): void
    {
        $user = new User(['booking' => ['seat' => 1]]);
        $resource = new Resource(['type' => 'seat']);

        $this->assertThrowsNoPermission(fn() => $this->validateBookingPermission($this->bookingPermissionContext($user, $resource)));
    }

    public function test_booking_permission_throws_when_resource_type_blank_and_no_all_access(): void
    {
        $user = new User(['booking' => ['seat' => true]]);
        $resource = new Resource(['type' => '']);

        $this->assertThrowsNoPermission(fn() => $this->validateBookingPermission($this->bookingPermissionContext($user, $resource)));
    }

    public function test_cancellation_limit_passes_when_key_absent(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);

        app(CancellationLimit::class)->validate($this->cancellationContext($user, $resource, []));
        $this->assertTrue(true);
    }

    public function test_cancellation_limit_passes_when_max_cancel_count_null(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->seedCancel($user, $resource, ReservationStatus::CancelledUser, Carbon::now()->subDay());

        app(CancellationLimit::class)->validate($this->cancellationContext($user, $resource, ['max_cancel_count' => null]));
        $this->assertTrue(true);
    }

    public function test_cancellation_limit_passes_when_prior_cancels_below_limit(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->seedCancel($user, $resource, ReservationStatus::CancelledUser, Carbon::now()->subDay());
        $this->seedCancel($user, $resource, ReservationStatus::CancelledUser, Carbon::now()->subDays(2));

        app(CancellationLimit::class)->validate($this->cancellationContext($user, $resource, ['max_cancel_count' => 5]));
        $this->assertTrue(true);
    }

    public function test_cancellation_limit_throws_at_boundary_count_equals_limit(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->seedCancel($user, $resource, ReservationStatus::CancelledUser, Carbon::now()->subDay());
        $this->seedCancel($user, $resource, ReservationStatus::CancelledUser, Carbon::now()->subDays(2));

        $this->assertThrowsReservationErrorStartsWith(ReservationError::CancelLimitBooking, fn() => app(CancellationLimit::class)->validate($this->cancellationContext($user, $resource, ['max_cancel_count' => 2])));
    }

    public function test_cancellation_limit_throws_when_count_exceeds_limit(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->seedCancel($user, $resource, ReservationStatus::CancelledUser, Carbon::now()->subDay());
        $this->seedCancel($user, $resource, ReservationStatus::CancelledUser, Carbon::now()->subDays(2));

        $this->assertThrowsReservationErrorStartsWith(ReservationError::CancelLimitBooking, fn() => app(CancellationLimit::class)->validate($this->cancellationContext($user, $resource, ['max_cancel_count' => 1])));
    }

    public function test_cancellation_limit_treats_zero_as_disabled(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->seedCancel($user, $resource, ReservationStatus::CancelledUser, Carbon::now()->subDay());

        app(CancellationLimit::class)->validate($this->cancellationContext($user, $resource, ['max_cancel_count' => 0]));
        $this->assertTrue(true);
    }

    public function test_cancellation_limit_treats_negative_as_disabled(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->seedCancel($user, $resource, ReservationStatus::CancelledUser, Carbon::now()->subDay());

        app(CancellationLimit::class)->validate($this->cancellationContext($user, $resource, ['max_cancel_count' => -3]));
        $this->assertTrue(true);
    }

    public function test_cancellation_limit_treats_string_zero_as_disabled(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->seedCancel($user, $resource, ReservationStatus::CancelledUser, Carbon::now()->subDay());

        app(CancellationLimit::class)->validate($this->cancellationContext($user, $resource, ['max_cancel_count' => '0']));
        $this->assertTrue(true);
    }

    public function test_cancellation_limit_passes_with_no_cancels_when_limit_one(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);

        app(CancellationLimit::class)->validate($this->cancellationContext($user, $resource, ['max_cancel_count' => 1]));
        $this->assertTrue(true);
    }

    public function test_cancellation_limit_ignores_cancels_different_resource_type(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $seat = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $car = Resource::factory()->create(['type' => 'car', 'status' => 'active']);
        $this->seedCancel($user, $seat, ReservationStatus::CancelledUser, Carbon::now()->subDay());

        app(CancellationLimit::class)->validate($this->cancellationContext($user, $car, ['max_cancel_count' => 1]));
        $this->assertTrue(true);
    }

    public function test_cancellation_limit_ignores_cancels_different_user(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->seedCancel($stranger, $resource, ReservationStatus::CancelledUser, Carbon::now()->subDay());

        app(CancellationLimit::class)->validate($this->cancellationContext($owner, $resource, ['max_cancel_count' => 1]));
        $this->assertTrue(true);
    }

    public function test_cancellation_limit_ignores_cancelled_admin_status(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->seedCancel($user, $resource, ReservationStatus::CancelledAdmin, Carbon::now()->subDay());

        app(CancellationLimit::class)->validate($this->cancellationContext($user, $resource, ['max_cancel_count' => 1]));
        $this->assertTrue(true);
    }

    public function test_cancellation_limit_ignores_released_status(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->seedCancel($user, $resource, ReservationStatus::Released, Carbon::now()->subDay());

        app(CancellationLimit::class)->validate($this->cancellationContext($user, $resource, ['max_cancel_count' => 1]));
        $this->assertTrue(true);
    }

    public function test_cancellation_limit_counts_cancels_with_null_cancelled_at(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->seedCancel($user, $resource, ReservationStatus::CancelledUser, null);

        $this->assertThrowsReservationErrorStartsWith(ReservationError::CancelLimitBooking, fn() => app(CancellationLimit::class)->validate($this->cancellationContext($user, $resource, ['max_cancel_count' => 1])));
    }

    public function test_cancellation_limit_window_follows_cancel_window_days_policy(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->seedCancel($user, $resource, ReservationStatus::CancelledUser, Carbon::now()->subDays(40));

        // 40 days ago: outside the default 30, inside a 60-day window.
        app(CancellationLimit::class)->validate($this->cancellationContext($user, $resource, ['max_cancel_count' => 1]));
        $this->assertThrowsReservationErrorStartsWith(ReservationError::CancelLimitBooking, fn() => app(CancellationLimit::class)->validate($this->cancellationContext($user, $resource, ['max_cancel_count' => 1, 'cancel_window_days' => 60])));
        $this->assertSame(30, \App\Services\Reservation\ValidationService::cancelWindowDays([]));
        $this->assertSame(365, \App\Services\Reservation\ValidationService::cancelWindowDays(['cancel_window_days' => 9999]));
    }

    public function test_cancellation_limit_of_zero_disables_cancelling_but_never_blocks_booking(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->seedCancel($user, $resource, ReservationStatus::CancelledUser, Carbon::now()->subDay());

        app(CancellationLimit::class)->validate($this->cancellationContext($user, $resource, ['max_cancel_count' => 0]));
        $this->assertTrue(true);
    }

    public function test_cancellation_limit_ignores_cancels_older_than_thirty_days(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->seedCancel($user, $resource, ReservationStatus::CancelledUser, Carbon::now()->subDays(40));

        app(CancellationLimit::class)->validate($this->cancellationContext($user, $resource, ['max_cancel_count' => 1]));
        $this->assertTrue(true);
    }

    public function test_cancellation_limit_counts_cancels_with_recent_cancelled_at(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->seedCancel($user, $resource, ReservationStatus::CancelledUser, Carbon::now()->subDays(10));

        $this->assertThrowsReservationErrorStartsWith(ReservationError::CancelLimitBooking, fn() => app(CancellationLimit::class)->validate($this->cancellationContext($user, $resource, ['max_cancel_count' => 1])));
    }

    public function test_cancellation_limit_full_day_does_not_bypass(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->seedCancel($user, $resource, ReservationStatus::CancelledUser, Carbon::now()->subDay());

        $this->assertThrowsReservationErrorStartsWith(ReservationError::CancelLimitBooking, fn() => app(CancellationLimit::class)->validate($this->cancellationContext($user, $resource, ['max_cancel_count' => 1], true)));
    }

    public function test_cancellation_limit_recurrence_does_not_affect(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->seedCancel($user, $resource, ReservationStatus::CancelledUser, Carbon::now()->subDay());

        $this->assertThrowsReservationErrorStartsWith(ReservationError::CancelLimitBooking, fn() => app(CancellationLimit::class)->validate($this->cancellationContext($user, $resource, ['max_cancel_count' => 1], false, ['freq' => 'daily'])));
    }

    public function test_duration_passes_when_end_strictly_after_start_no_policies(): void
    {
        $start = Carbon::parse('2026-08-01 09:00:00');
        $this->assertTrue($this->durationPasses($this->durationContext($start, $start->copy()->addMinutes(60))));
    }

    public function test_duration_throws_invalid_time_range_when_start_equals_end(): void
    {
        $start = Carbon::parse('2026-08-01 09:00:00');
        $this->assertThrowsReservationErrorWithArg(ReservationError::InvalidTimeRange, null, fn() => $this->durationRule->validate($this->durationContext($start, $start->copy())));
    }

    public function test_duration_throws_invalid_time_range_when_end_before_start(): void
    {
        $start = Carbon::parse('2026-08-01 09:00:00');
        $this->assertThrowsReservationErrorWithArg(ReservationError::InvalidTimeRange, null, fn() => $this->durationRule->validate($this->durationContext($start, $start->copy()->subMinutes(10))));
    }

    public function test_duration_invalid_time_range_fires_before_full_day_bypass(): void
    {
        $start = Carbon::parse('2026-08-01 09:00:00');
        $this->assertThrowsReservationErrorWithArg(ReservationError::InvalidTimeRange, null, fn() => $this->durationRule->validate($this->durationContext($start, $start->copy(), true, ['min_duration_minutes' => 30, 'max_duration_minutes' => 120])));
    }

    public function test_duration_full_day_bypasses_min_and_max(): void
    {
        $start = Carbon::parse('2026-08-01 00:00:00');
        $this->assertTrue($this->durationPasses($this->durationContext($start, $start->copy()->addMinutes(5), true, ['min_duration_minutes' => 60, 'max_duration_minutes' => 120])));
    }

    public function test_duration_full_day_passes_even_when_span_single_minute(): void
    {
        $start = Carbon::parse('2026-08-01 00:00:00');
        $this->assertTrue($this->durationPasses($this->durationContext($start, $start->copy()->addMinute(), true, ['min_duration_minutes' => 999])));
    }

    public function test_duration_passes_when_policies_empty(): void
    {
        $start = Carbon::parse('2026-08-01 09:00:00');
        $this->assertTrue($this->durationPasses($this->durationContext($start, $start->copy()->addMinutes(1), false, [])));
    }

    public function test_duration_passes_when_min_max_absent(): void
    {
        $start = Carbon::parse('2026-08-01 09:00:00');
        $this->assertTrue($this->durationPasses($this->durationContext($start, $start->copy()->addMinutes(10), false, ['window_days' => 7])));
    }

    public function test_duration_passes_when_min_max_explicitly_null(): void
    {
        $start = Carbon::parse('2026-08-01 09:00:00');
        $this->assertTrue($this->durationPasses($this->durationContext($start, $start->copy()->addMinutes(10), false, ['min_duration_minutes' => null, 'max_duration_minutes' => null])));
    }

    public function test_duration_passes_when_duration_equals_min_boundary(): void
    {
        $start = Carbon::parse('2026-08-01 09:00:00');
        $this->assertTrue($this->durationPasses($this->durationContext($start, $start->copy()->addMinutes(30), false, ['min_duration_minutes' => 30])));
    }

    public function test_duration_throws_too_short_one_minute_below_min(): void
    {
        $start = Carbon::parse('2026-08-01 09:00:00');
        $this->assertThrowsReservationErrorWithArg(ReservationError::TooShort, 30, fn() => $this->durationRule->validate($this->durationContext($start, $start->copy()->addMinutes(29), false, ['min_duration_minutes' => 30])));
    }

    public function test_duration_throws_too_short_with_min_when_max_also_set(): void
    {
        $start = Carbon::parse('2026-08-01 09:00:00');
        $this->assertThrowsReservationErrorWithArg(ReservationError::TooShort, 30, fn() => $this->durationRule->validate($this->durationContext($start, $start->copy()->addMinutes(10), false, ['min_duration_minutes' => 30, 'max_duration_minutes' => 120])));
    }

    public function test_duration_passes_when_duration_equals_max_boundary(): void
    {
        $start = Carbon::parse('2026-08-01 09:00:00');
        $this->assertTrue($this->durationPasses($this->durationContext($start, $start->copy()->addMinutes(120), false, ['max_duration_minutes' => 120])));
    }

    public function test_duration_throws_too_long_one_minute_above_max(): void
    {
        $start = Carbon::parse('2026-08-01 09:00:00');
        $this->assertThrowsReservationErrorWithArg(ReservationError::TooLong, 120, fn() => $this->durationRule->validate($this->durationContext($start, $start->copy()->addMinutes(121), false, ['max_duration_minutes' => 120])));
    }

    public function test_duration_throws_too_long_with_max_when_min_also_set(): void
    {
        $start = Carbon::parse('2026-08-01 09:00:00');
        $this->assertThrowsReservationErrorWithArg(ReservationError::TooLong, 120, fn() => $this->durationRule->validate($this->durationContext($start, $start->copy()->addMinutes(200), false, ['min_duration_minutes' => 30, 'max_duration_minutes' => 120])));
    }

    public function test_duration_passes_within_min_max_inclusive_bounds(): void
    {
        $start = Carbon::parse('2026-08-01 09:00:00');
        $this->assertTrue($this->durationPasses($this->durationContext($start, $start->copy()->addMinutes(60), false, ['min_duration_minutes' => 30, 'max_duration_minutes' => 120])));
    }

    public function test_duration_min_policy_cast_to_int_from_string(): void
    {
        $start = Carbon::parse('2026-08-01 09:00:00');
        $this->assertThrowsReservationErrorWithArg(ReservationError::TooShort, '45', fn() => $this->durationRule->validate($this->durationContext($start, $start->copy()->addMinutes(44), false, ['min_duration_minutes' => '45'])));
    }

    public function test_duration_max_policy_cast_to_int_from_string(): void
    {
        $start = Carbon::parse('2026-08-01 09:00:00');
        $this->assertThrowsReservationErrorWithArg(ReservationError::TooLong, '90', fn() => $this->durationRule->validate($this->durationContext($start, $start->copy()->addMinutes(91), false, ['max_duration_minutes' => '90'])));
    }

    public function test_duration_recurrence_and_exclude_id_do_not_affect(): void
    {
        $start = Carbon::parse('2026-08-01 09:00:00');
        $this->assertTrue($this->durationPasses($this->durationContext($start, $start->copy()->addMinutes(60), false, ['min_duration_minutes' => 30], ['freq' => 'daily'], 42)));
    }

    public function test_duration_cross_midnight_passes_when_within_policies(): void
    {
        $start = Carbon::parse('2026-08-01 23:00:00');
        $this->assertTrue($this->durationPasses($this->durationContext($start, $start->copy()->addMinutes(120), false, ['min_duration_minutes' => 60, 'max_duration_minutes' => 180])));
    }

    public function test_duration_cross_midnight_too_short_triggers_too_short(): void
    {
        $start = Carbon::parse('2026-08-01 23:30:00');
        $this->assertThrowsReservationErrorWithArg(ReservationError::TooShort, 60, fn() => $this->durationRule->validate($this->durationContext($start, $start->copy()->addMinutes(20), false, ['min_duration_minutes' => 60])));
    }

    public function test_range_duration_passes_when_context_is_not_a_range(): void
    {
        $start = Carbon::parse('2026-08-01 10:00:00');

        (new RangeDuration())->validate($this->rangeDurationContext($start, $start->copy()->addHours(8), ['max_range_days' => 1]));
        (new RangeDuration())->validate($this->rangeDurationContext($start, $start->copy()->addDays(5), ['max_range_days' => 1], true));
        $this->assertTrue(true);
    }

    public function test_range_duration_passes_when_max_range_days_is_null(): void
    {
        $start = Carbon::parse('2026-08-01 12:00:00');

        (new RangeDuration())->validate($this->rangeDurationContext($start, $start->copy()->addDays(9), ['max_range_days' => null]));
        $this->assertTrue(true);
    }

    public function test_range_duration_throws_when_range_exceeds_the_cap(): void
    {
        $start = Carbon::parse('2026-08-01 12:00:00');

        $this->assertThrowsReservationErrorWithArg(
            ReservationError::RangeTooLong,
            3,
            fn() => (new RangeDuration())->validate($this->rangeDurationContext($start, $start->copy()->addDays(4), ['max_range_days' => 3])),
        );
    }

    public function test_range_duration_passes_when_range_exactly_equals_the_cap(): void
    {
        $start = Carbon::parse('2026-08-01 12:00:00');

        (new RangeDuration())->validate($this->rangeDurationContext($start, $start->copy()->addDays(3), ['max_range_days' => 3]));
        $this->assertTrue(true);
    }

    public function test_full_day_passes_when_key_missing(): void
    {
        $this->fullDayRule->validate($this->fullDayContext(true, []));
        $this->assertTrue(true);
    }

    public function test_full_day_passes_when_allow_full_day_true(): void
    {
        $this->fullDayRule->validate($this->fullDayContext(true, ['allow_full_day' => true]));
        $this->assertTrue(true);
    }

    public function test_full_day_passes_when_allow_full_day_null_default_true(): void
    {
        $this->fullDayRule->validate($this->fullDayContext(true, ['allow_full_day' => null]));
        $this->assertTrue(true);
    }

    public function test_full_day_throws_when_allow_full_day_false(): void
    {
        $this->assertThrowsFullDayForbidden(fn() => $this->fullDayRule->validate($this->fullDayContext(true, ['allow_full_day' => false])));
    }

    public function test_full_day_passes_when_not_full_day_and_allow_false(): void
    {
        $this->fullDayRule->validate($this->fullDayContext(false, ['allow_full_day' => false]));
        $this->assertTrue(true);
    }

    public function test_full_day_passes_when_not_full_day_and_key_missing(): void
    {
        $this->fullDayRule->validate($this->fullDayContext(false, []));
        $this->assertTrue(true);
    }

    public function test_full_day_passes_when_not_full_day_regardless_of_policy(): void
    {
        $this->fullDayRule->validate($this->fullDayContext(false, ['allow_full_day' => false, 'window_days' => 7]));
        $this->assertTrue(true);
    }

    public function test_full_day_throws_message_matches_exact_enum_case(): void
    {
        $thrown = null;
        try {
            $this->fullDayRule->validate($this->fullDayContext(true, ['allow_full_day' => false]));
        } catch (\Throwable $e) {
            $thrown = $e;
        }
        $this->assertNotNull($thrown, 'Expected ReservationError ERR-006 to be thrown.');
        $this->assertSame(ReservationError::FullDayForbidden->message(), $thrown->getMessage());
    }

    public function test_recurrence_passes_when_null_regardless_of_allow_repeat_false(): void
    {
        $this->recurrenceValidator->validate($this->recurrenceContext(['allow_repeat' => false], null));
        $this->assertTrue(true);
    }

    public function test_recurrence_passes_when_empty_array(): void
    {
        $this->recurrenceValidator->validate($this->recurrenceContext(['allow_repeat' => false], []));
        $this->assertTrue(true);
    }

    public function test_recurrence_passes_when_present_and_allow_repeat_true(): void
    {
        $this->recurrenceValidator->validate($this->recurrenceContext(['allow_repeat' => true], ['freq' => 'daily']));
        $this->assertTrue(true);
    }

    public function test_recurrence_passes_when_present_and_allow_repeat_key_missing(): void
    {
        $this->recurrenceValidator->validate($this->recurrenceContext([], ['freq' => 'daily']));
        $this->assertTrue(true);
    }

    public function test_recurrence_passes_when_present_and_empty_policies(): void
    {
        $this->recurrenceValidator->validate($this->recurrenceContext([], ['freq' => 'weekly']));
        $this->assertTrue(true);
    }

    public function test_recurrence_throws_when_allow_repeat_falsy_zero(): void
    {
        $this->assertThrowsRecurForbidden(fn() => $this->recurrenceValidator->validate($this->recurrenceContext(['allow_repeat' => 0], ['freq' => 'daily'])));
    }

    public function test_recurrence_passes_when_allow_repeat_null_key_present(): void
    {
        $this->recurrenceValidator->validate($this->recurrenceContext(['allow_repeat' => null], ['freq' => 'daily']));
        $this->assertTrue(true);
    }

    public function test_recurrence_throws_when_present_and_allow_repeat_false(): void
    {
        $this->assertThrowsRecurForbidden(fn() => $this->recurrenceValidator->validate($this->recurrenceContext(['allow_repeat' => false], ['freq' => 'daily'])));
    }

    public function test_recurrence_throws_when_present_and_allow_repeat_string_falsey_empty(): void
    {
        $this->assertThrowsRecurForbidden(fn() => $this->recurrenceValidator->validate($this->recurrenceContext(['allow_repeat' => ''], ['freq' => 'daily'])));
    }

    public function test_recurrence_throws_under_full_day_when_allow_repeat_false(): void
    {
        $this->assertThrowsRecurForbidden(fn() => $this->recurrenceValidator->validate($this->recurrenceContext(['allow_repeat' => false], ['freq' => 'daily'], true)));
    }

    public function test_recurrence_passes_under_full_day_when_allow_repeat_true(): void
    {
        $this->recurrenceValidator->validate($this->recurrenceContext(['allow_repeat' => true], ['freq' => 'daily'], true));
        $this->assertTrue(true);
    }

    public function test_recurrence_throws_with_minimal_non_empty_recurrence_shape(): void
    {
        $this->assertThrowsRecurForbidden(fn() => $this->recurrenceValidator->validate($this->recurrenceContext(['allow_repeat' => false], ['rule' => 'FREQ=DAILY'])));
    }

    public function test_recurrence_passes_when_null_and_policies_empty(): void
    {
        $this->recurrenceValidator->validate($this->recurrenceContext([], null));
        $this->assertTrue(true);
    }

    public function test_resource_active_passes_when_status_active(): void
    {
        $user = User::factory()->make(['status' => 'active']);
        $resource = new Resource(['status' => 'active']);

        app(ResourceActive::class)->validate($this->resourceActiveContext($user, $resource));
        $this->assertTrue(true);
    }

    public function test_resource_active_throws_when_status_inactive(): void
    {
        $user = User::factory()->make(['status' => 'active']);
        $resource = new Resource(['status' => 'inactive']);

        $this->assertThrowsReservationError(
            ReservationError::ResourceInactive,
            fn() => app(ResourceActive::class)->validate($this->resourceActiveContext($user, $resource)),
        );
    }

    public function test_resource_active_throws_for_non_active_status_value(): void
    {
        $user = User::factory()->make(['status' => 'active']);

        foreach (['maintenance', 'disabled', 'paused', 'archived', ''] as $status) {
            $resource = new Resource(['status' => $status]);

            $this->assertThrowsReservationError(
                ReservationError::ResourceInactive,
                fn() => app(ResourceActive::class)->validate($this->resourceActiveContext($user, $resource)),
            );
        }
    }

    public function test_resource_active_case_sensitive_comparison(): void
    {
        $user = User::factory()->make(['status' => 'active']);
        $resource = new Resource(['status' => 'Active']);

        $this->assertThrowsReservationError(
            ReservationError::ResourceInactive,
            fn() => app(ResourceActive::class)->validate($this->resourceActiveContext($user, $resource)),
        );
    }

    public function test_resource_active_passes_with_empty_policies_and_null_optionals(): void
    {
        $user = User::factory()->make(['status' => 'active']);
        $resource = new Resource(['status' => 'active']);
        $context = new BookingContext(
            user: $user,
            resource: $resource,
            start: Carbon::now()->addDay(),
            end: Carbon::now()->addDay()->addHour(),
            isFullDay: false,
            policies: [],
            recurrence: null,
            excludeId: null,
        );

        app(ResourceActive::class)->validate($context);
        $this->assertTrue(true);
    }

    public function test_resource_active_only_inspects_resource_status(): void
    {
        $user = User::factory()->make(['status' => 'inactive']);
        $resource = new Resource(['status' => 'active']);
        $context = new BookingContext(
            user: $user,
            resource: $resource,
            start: Carbon::now()->subDay(),
            end: Carbon::now()->subDay()->addHour(),
            isFullDay: true,
            policies: [],
        );

        app(ResourceActive::class)->validate($context);
        $this->assertTrue(true);
    }

    public function test_resource_availability_passes_when_no_overlapping(): void
    {
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $start = Carbon::now()->addDays(2)->setTime(9, 0, 0);

        $this->resourceAvailabilityValidator()->validate($this->resourceAvailabilityContext($resource, $start, $start->copy()->addHour()));
        $this->assertTrue(true);
    }

    public function test_resource_availability_throws_when_active_overlaps(): void
    {
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $day = Carbon::now()->addDays(2);
        $this->resourceAvailabilityReserve($resource, ReservationStatus::Active, $day->copy()->setTime(9, 0, 0), $day->copy()->setTime(11, 0, 0));

        $this->assertThrowsResourceTaken(fn() => $this->resourceAvailabilityValidator()->validate(
            $this->resourceAvailabilityContext($resource, $day->copy()->setTime(10, 0, 0), $day->copy()->setTime(11, 30, 0))
        ));
    }

    public function test_resource_availability_throws_when_released_overlaps_and_overlap_release_disabled(): void
    {
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $day = Carbon::now()->addDays(2);
        $this->resourceAvailabilityReserve($resource, ReservationStatus::Released, $day->copy()->setTime(9, 0, 0), $day->copy()->setTime(11, 0, 0));

        $this->assertThrowsResourceTaken(fn() => $this->resourceAvailabilityValidator()->validate(
            $this->resourceAvailabilityContext($resource, $day->copy()->setTime(10, 0, 0), $day->copy()->setTime(11, 30, 0), ['allow_overlap_release' => false])
        ));
    }

    public function test_resource_availability_passes_when_released_overlaps_and_allow_overlap_release_true(): void
    {
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $day = Carbon::now()->addDays(2);
        $this->resourceAvailabilityReserve($resource, ReservationStatus::Released, $day->copy()->setTime(9, 0, 0), $day->copy()->setTime(11, 0, 0));

        $this->resourceAvailabilityValidator()->validate(
            $this->resourceAvailabilityContext($resource, $day->copy()->setTime(10, 0, 0), $day->copy()->setTime(11, 30, 0), ['allow_overlap_release' => true])
        );
        $this->assertTrue(true);
    }

    public function test_resource_availability_throws_when_active_overlaps_even_if_allow_overlap_release_true(): void
    {
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $day = Carbon::now()->addDays(2);
        $this->resourceAvailabilityReserve($resource, ReservationStatus::Active, $day->copy()->setTime(9, 0, 0), $day->copy()->setTime(11, 0, 0));

        $this->assertThrowsResourceTaken(fn() => $this->resourceAvailabilityValidator()->validate(
            $this->resourceAvailabilityContext($resource, $day->copy()->setTime(10, 0, 0), $day->copy()->setTime(11, 30, 0), ['allow_overlap_release' => true])
        ));
    }

    public function test_resource_availability_exclude_id_skips_self(): void
    {
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $day = Carbon::now()->addDays(2);
        $self = $this->resourceAvailabilityReserve($resource, ReservationStatus::Active, $day->copy()->setTime(9, 0, 0), $day->copy()->setTime(11, 0, 0));

        $this->resourceAvailabilityValidator()->validate(
            $this->resourceAvailabilityContext($resource, $day->copy()->setTime(9, 0, 0), $day->copy()->setTime(11, 0, 0), [], $self->id)
        );
        $this->assertTrue(true);
    }

    public function test_resource_availability_adjacent_boundary_no_overlap(): void
    {
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $day = Carbon::now()->addDays(2);
        $this->resourceAvailabilityReserve($resource, ReservationStatus::Active, $day->copy()->setTime(9, 0, 0), $day->copy()->setTime(11, 0, 0));

        $this->resourceAvailabilityValidator()->validate(
            $this->resourceAvailabilityContext($resource, $day->copy()->setTime(11, 0, 0), $day->copy()->setTime(12, 0, 0))
        );
        $this->assertTrue(true);
    }

    public function test_resource_availability_full_day_null_start_blocks_within_same_day(): void
    {
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->resourceAvailabilityReserve($resource, ReservationStatus::Active, null, null, true);

        $start = Carbon::today()->setTime(0, 0, 0);
        $this->assertThrowsResourceTaken(fn() => $this->resourceAvailabilityValidator()->validate(
            $this->resourceAvailabilityContext($resource, $start, $start->copy()->endOfDay(), [], null, true)
        ));
    }

    public function test_resource_availability_full_day_null_start_outside_day_no_block(): void
    {
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $this->resourceAvailabilityReserve($resource, ReservationStatus::Active, null, null, true);

        $tomorrow = Carbon::tomorrow()->setTime(0, 0, 0);
        $this->resourceAvailabilityValidator()->validate(
            $this->resourceAvailabilityContext($resource, $tomorrow, $tomorrow->copy()->endOfDay(), [], null, true)
        );
        $this->assertTrue(true);
    }

    public function test_resource_availability_cancelled_do_not_block(): void
    {
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $day = Carbon::now()->addDays(2);
        $this->resourceAvailabilityReserve($resource, ReservationStatus::CancelledUser, $day->copy()->setTime(9, 0, 0), $day->copy()->setTime(11, 0, 0));
        $this->resourceAvailabilityReserve($resource, ReservationStatus::CancelledAdmin, $day->copy()->setTime(12, 0, 0), $day->copy()->setTime(13, 0, 0));

        $this->resourceAvailabilityValidator()->validate(
            $this->resourceAvailabilityContext($resource, $day->copy()->setTime(9, 0, 0), $day->copy()->setTime(13, 0, 0))
        );
        $this->assertTrue(true);
    }

    public function test_resource_availability_overlap_different_resource_no_block(): void
    {
        $other = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $day = Carbon::now()->addDays(2);
        $this->resourceAvailabilityReserve($other, ReservationStatus::Active, $day->copy()->setTime(9, 0, 0), $day->copy()->setTime(11, 0, 0));

        $this->resourceAvailabilityValidator()->validate(
            $this->resourceAvailabilityContext($resource, $day->copy()->setTime(10, 0, 0), $day->copy()->setTime(11, 0, 0))
        );
        $this->assertTrue(true);
    }

    public function test_resource_availability_missing_overlap_release_policy_defaults_blocking(): void
    {
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $day = Carbon::now()->addDays(2);
        $this->resourceAvailabilityReserve($resource, ReservationStatus::Released, $day->copy()->setTime(9, 0, 0), $day->copy()->setTime(11, 0, 0));

        $this->assertThrowsResourceTaken(fn() => $this->resourceAvailabilityValidator()->validate(
            $this->resourceAvailabilityContext($resource, $day->copy()->setTime(10, 0, 0), $day->copy()->setTime(11, 30, 0), [])
        ));
    }

    public function test_time_window_passes_when_start_future_empty_policies(): void
    {
        $this->validateTimeWindow($this->timeWindowContext(Carbon::now()->addDays(2)));
        $this->assertTrue(true);
    }

    public function test_time_window_passes_same_minute_even_if_seconds_past(): void
    {
        $this->validateTimeWindow($this->timeWindowContext(Carbon::parse('2026-08-01 12:00:10')));
        $this->assertTrue(true);
    }

    public function test_time_window_throws_past_booking_when_earlier_minute(): void
    {
        $this->assertThrowsReservationError(ReservationError::PastBooking, fn() => $this->validateTimeWindow($this->timeWindowContext(Carbon::now()->subMinutes(5))));
    }

    public function test_time_window_passes_when_window_days_null(): void
    {
        $this->validateTimeWindow($this->timeWindowContext(Carbon::now()->addYears(2), ['window_days' => null]));
        $this->assertTrue(true);
    }

    public function test_time_window_passes_on_window_days_boundary(): void
    {
        $this->validateTimeWindow($this->timeWindowContext(Carbon::now()->addDays(7), ['window_days' => 7]));
        $this->assertTrue(true);
    }

    public function test_time_window_throws_window_exceeded_after_boundary(): void
    {
        $this->assertThrowsReservationError(ReservationError::WindowExceeded, fn() => $this->validateTimeWindow($this->timeWindowContext(Carbon::now()->addDays(8), ['window_days' => 7])));
    }

    public function test_time_window_throws_window_exceeded_when_zero_and_future(): void
    {
        $this->assertThrowsReservationError(ReservationError::WindowExceeded, fn() => $this->validateTimeWindow($this->timeWindowContext(Carbon::now()->addDay(), ['window_days' => 0])));
    }

    public function test_time_window_passes_full_day_booking_for_today(): void
    {
        $start = Carbon::now()->startOfDay();

        $this->validateTimeWindow(new BookingContext(
            user: new User(['status' => 'active']),
            resource: new Resource(['type' => 'seat', 'status' => 'active']),
            start: $start,
            end: $start->copy()->endOfDay(),
            isFullDay: true,
            policies: ['window_days' => 0],
        ));

        $this->addToAssertionCount(1);
    }

    public function test_time_window_throws_window_exceeded_when_false(): void
    {
        $this->assertThrowsReservationError(ReservationError::WindowExceeded, fn() => $this->validateTimeWindow($this->timeWindowContext(Carbon::now()->addDay(), ['window_days' => false])));
    }

    public function test_time_window_passes_when_window_hours_zero_or_null(): void
    {
        $this->validateTimeWindow($this->timeWindowContext(Carbon::now()->addMinute(), ['window_hours' => 0]));
        $this->validateTimeWindow($this->timeWindowContext(Carbon::now()->addMinute(), ['window_hours' => null]));
        $this->assertTrue(true);
    }

    public function test_time_window_passes_when_lead_time_equals_window_hours(): void
    {
        $this->validateTimeWindow($this->timeWindowContext(Carbon::now()->addHours(12), ['window_hours' => 12]));
        $this->assertTrue(true);
    }

    public function test_time_window_throws_early_booking_when_lead_time_less(): void
    {
        $this->assertThrowsReservationError(ReservationError::EarlyBooking, fn() => $this->validateTimeWindow($this->timeWindowContext(Carbon::now()->addHours(3), ['window_hours' => 12])));
    }

    public function test_time_window_passes_when_window_hours_false(): void
    {
        $this->validateTimeWindow($this->timeWindowContext(Carbon::now()->addMinute(), ['window_hours' => false]));
        $this->assertTrue(true);
    }

    public function test_time_window_throws_window_exceeded_before_early_booking(): void
    {
        $this->assertThrowsReservationError(
            ReservationError::WindowExceeded,
            fn() => $this->validateTimeWindow($this->timeWindowContext(Carbon::now()->addDays(10), ['window_days' => 7, 'window_hours' => 500]))
        );
    }

    public function test_time_window_passes_for_full_day_within_window(): void
    {
        $this->validateTimeWindow($this->timeWindowContext(Carbon::now()->addDays(2), ['window_days' => 7], true));
        $this->assertTrue(true);
    }

    public function test_type_active_is_type_active_true_when_window_days_non_zero(): void
    {
        $this->assertTrue(TypeActive::isTypeActive(['window_days' => 5, 'window_hours' => 0]));
    }

    public function test_type_active_is_type_active_true_when_window_hours_non_zero(): void
    {
        $this->assertTrue(TypeActive::isTypeActive(['window_days' => 0, 'window_hours' => 3]));
    }

    public function test_type_active_is_type_active_false_when_both_zero(): void
    {
        $this->assertFalse(TypeActive::isTypeActive(['window_days' => 0, 'window_hours' => 0]));
    }

    public function test_type_active_defaults_missing_window_days_to_one(): void
    {
        $this->assertTrue(TypeActive::isTypeActive(['window_hours' => 0]));
    }

    public function test_type_active_defaults_missing_window_hours_to_one(): void
    {
        $this->assertTrue(TypeActive::isTypeActive(['window_days' => 0]));
    }

    public function test_type_active_defaults_empty_policies_to_active(): void
    {
        $this->assertTrue(TypeActive::isTypeActive([]));
    }

    public function test_type_active_casts_string_zero_to_inactive(): void
    {
        $this->assertFalse(TypeActive::isTypeActive(['window_days' => '0', 'window_hours' => '0']));
    }

    public function test_type_active_casts_string_non_zero_to_active(): void
    {
        $this->assertTrue(TypeActive::isTypeActive(['window_days' => '7', 'window_hours' => '0']));
    }

    public function test_type_active_validate_passes_when_window_days_non_zero(): void
    {
        $this->assertTypeActivePasses(['window_days' => 5, 'window_hours' => 0]);
    }

    public function test_type_active_validate_passes_when_window_hours_non_zero(): void
    {
        $this->assertTypeActivePasses(['window_days' => 0, 'window_hours' => 3]);
    }

    public function test_type_active_validate_passes_when_both_non_zero(): void
    {
        $this->assertTypeActivePasses(['window_days' => 5, 'window_hours' => 3]);
    }

    public function test_type_active_validate_passes_when_policies_empty(): void
    {
        $this->assertTypeActivePasses([]);
    }

    public function test_type_active_validate_passes_when_window_days_missing_and_hours_zero(): void
    {
        $this->assertTypeActivePasses(['window_hours' => 0]);
    }

    public function test_type_active_validate_passes_when_window_hours_missing_and_days_zero(): void
    {
        $this->assertTypeActivePasses(['window_days' => 0]);
    }

    public function test_type_active_validate_throws_when_both_zero(): void
    {
        $this->assertThrowsTypeInactive(['window_days' => 0, 'window_hours' => 0]);
    }

    public function test_type_active_validate_throws_when_string_zeros(): void
    {
        $this->assertThrowsTypeInactive(['window_days' => '0', 'window_hours' => '0']);
    }

    public function test_type_active_validate_throws_message_includes_error_code(): void
    {
        $thrown = null;
        try {
            $this->typeActiveValidator->validate($this->typeActiveContext(['window_days' => 0, 'window_hours' => 0]));
        } catch (\Throwable $e) {
            $thrown = $e;
        }
        $this->assertNotNull($thrown, 'Expected ReservationError TypeInactive to be thrown.');
        $this->assertSame('[ERR-022] این نوع منبع در حال حاضر توسط مدیریت غیرفعال شده است.', $thrown->getMessage());
    }

    public function test_type_active_validate_ignores_unrelated_keys_for_active_decision(): void
    {
        $this->assertThrowsTypeInactive(['window_days' => 0, 'window_hours' => 0, 'min_duration_minutes' => 30, 'max_per_user' => 5]);
    }

    public function test_type_active_validate_passes_with_unrelated_keys_when_type_active(): void
    {
        $this->assertTypeActivePasses(['window_days' => 2, 'window_hours' => 1, 'min_duration_minutes' => 30, 'max_per_user' => 5]);
    }

    public function test_user_active_passes_when_status_active(): void
    {
        $user = new User(['status' => 'active']);

        $this->userActiveValidator->validate($this->userActiveContext($user));
        $this->assertTrue(true);
    }

    public function test_user_active_passes_regardless_of_policies_and_full_day(): void
    {
        $user = new User(['status' => 'active']);

        $this->userActiveValidator->validate($this->userActiveContext($user, ['window_days' => 7]));
        $this->userActiveValidator->validate(new BookingContext(
            user: $user,
            resource: new Resource(),
            start: Carbon::now()->addDay(),
            end: Carbon::now()->addDay()->addDay(),
            isFullDay: true,
            policies: [],
            recurrence: ['freq' => 'daily'],
            excludeId: 42,
        ));
        $this->assertTrue(true);
    }

    public function test_user_active_throws_when_status_inactive(): void
    {
        $user = new User(['status' => 'inactive']);

        $this->assertThrowsReservationError(
            ReservationError::UserInactive,
            fn() => $this->userActiveValidator->validate($this->userActiveContext($user)),
        );
    }

    public function test_user_active_throws_for_non_active_status_strings(): void
    {
        foreach (['inactive', 'suspended', 'pending', ''] as $status) {
            $user = new User(['status' => $status]);

            $this->assertThrowsReservationError(
                ReservationError::UserInactive,
                fn() => $this->userActiveValidator->validate($this->userActiveContext($user)),
            );
        }
    }

    public function test_user_active_throws_when_status_null(): void
    {
        $user = new User(['status' => null]);

        $this->assertThrowsReservationError(
            ReservationError::UserInactive,
            fn() => $this->userActiveValidator->validate($this->userActiveContext($user)),
        );
    }

    public function test_user_active_passes_with_empty_policies(): void
    {
        $user = new User(['status' => 'active']);

        $this->userActiveValidator->validate($this->userActiveContext($user, []));
        $this->assertTrue(true);
    }

    public function test_user_conflict_passes_when_no_existing_reservation(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $start = Carbon::now()->addDay()->setHour(9)->setMinute(0)->setSecond(0);

        app(UserConflict::class)->validate($this->userConflictContext($user, $resource, $start, $start->copy()->addHour()));
        $this->assertTrue(true);
    }

    public function test_user_conflict_throws_for_overlapping_active(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $day = Carbon::now()->addDay()->setHour(9)->setMinute(0)->setSecond(0);
        $this->userConflictReserve($user, $resource, $day->copy(), $day->copy()->addHour(), ReservationStatus::Active->value);

        $this->assertThrowsTimeConflict(fn() => app(UserConflict::class)->validate(
            $this->userConflictContext($user, $resource, $day->copy()->addMinutes(30), $day->copy()->addHours(2))
        ));
    }

    public function test_user_conflict_throws_for_overlapping_released(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $day = Carbon::now()->addDay()->setHour(9)->setMinute(0)->setSecond(0);
        $this->userConflictReserve($user, $resource, $day->copy(), $day->copy()->addHour(), ReservationStatus::Released->value);

        $this->assertThrowsTimeConflict(fn() => app(UserConflict::class)->validate(
            $this->userConflictContext($user, $resource, $day->copy()->addMinutes(30), $day->copy()->addHours(2))
        ));
    }

    public function test_user_conflict_passes_when_existing_cancelled(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $day = Carbon::now()->addDay()->setHour(9)->setMinute(0)->setSecond(0);
        $this->userConflictReserve($user, $resource, $day->copy(), $day->copy()->addHour(), ReservationStatus::CancelledUser->value);
        $this->userConflictReserve($user, $resource, $day->copy(), $day->copy()->addHour(), ReservationStatus::CancelledAdmin->value);

        app(UserConflict::class)->validate($this->userConflictContext($user, $resource, $day->copy()->addMinutes(30), $day->copy()->addHours(2)));
        $this->assertTrue(true);
    }

    public function test_user_conflict_passes_when_different_resource_type(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $seat = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $car = Resource::factory()->create(['type' => 'car', 'status' => 'active']);
        $day = Carbon::now()->addDay()->setHour(9)->setMinute(0)->setSecond(0);
        $this->userConflictReserve($user, $seat, $day->copy(), $day->copy()->addHour(), ReservationStatus::Active->value);

        app(UserConflict::class)->validate($this->userConflictContext($user, $car, $day->copy()->addMinutes(30), $day->copy()->addHours(2)));
        $this->assertTrue(true);
    }

    public function test_user_conflict_passes_when_different_user(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $day = Carbon::now()->addDay()->setHour(9)->setMinute(0)->setSecond(0);
        $this->userConflictReserve($other, $resource, $day->copy(), $day->copy()->addHour(), ReservationStatus::Active->value);

        app(UserConflict::class)->validate($this->userConflictContext($owner, $resource, $day->copy()->addMinutes(30), $day->copy()->addHours(2)));
        $this->assertTrue(true);
    }

    public function test_user_conflict_passes_when_existing_ends_where_context_starts(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $day = Carbon::now()->addDay()->setHour(9)->setMinute(0)->setSecond(0);
        $this->userConflictReserve($user, $resource, $day->copy(), $day->copy()->addHour(), ReservationStatus::Active->value);

        app(UserConflict::class)->validate($this->userConflictContext($user, $resource, $day->copy()->addHour(), $day->copy()->addHours(2)));
        $this->assertTrue(true);
    }

    public function test_user_conflict_passes_when_existing_starts_where_context_ends(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $day = Carbon::now()->addDay()->setHour(9)->setMinute(0)->setSecond(0);
        $this->userConflictReserve($user, $resource, $day->copy()->addHour(), $day->copy()->addHours(2), ReservationStatus::Active->value);

        app(UserConflict::class)->validate($this->userConflictContext($user, $resource, $day->copy(), $day->copy()->addHour()));
        $this->assertTrue(true);
    }

    public function test_user_conflict_exclude_id_excludes_self(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $day = Carbon::now()->addDay()->setHour(9)->setMinute(0)->setSecond(0);
        $existing = $this->userConflictReserve($user, $resource, $day->copy(), $day->copy()->addHour(), ReservationStatus::Active->value);

        app(UserConflict::class)->validate(
            $this->userConflictContext($user, $resource, $day->copy()->addMinutes(30), $day->copy()->addHours(2), false, $existing->id)
        );
        $this->assertTrue(true);
    }

    public function test_user_conflict_exclude_id_does_not_exclude_other_overlapping(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $day = Carbon::now()->addDay()->setHour(9)->setMinute(0)->setSecond(0);
        $first = $this->userConflictReserve($user, $resource, $day->copy(), $day->copy()->addHour(), ReservationStatus::Active->value);
        $this->userConflictReserve($user, $resource, $day->copy()->addMinutes(30), $day->copy()->addHours(2), ReservationStatus::Active->value);

        $this->assertThrowsTimeConflict(fn() => app(UserConflict::class)->validate(
            $this->userConflictContext($user, $resource, $day->copy()->addMinutes(15), $day->copy()->addHours(3), false, $first->id)
        ));
    }

    public function test_user_conflict_throws_for_full_day_null_start_within_day_range(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $noon = Carbon::today()->setHour(12);
        Reservation::factory()->create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => null,
            'end_time' => null,
            'is_full_day' => true,
            'status' => ReservationStatus::Active->value,
            'created_at' => $noon,
            'updated_at' => $noon,
        ]);
        $start = Carbon::today()->setHour(9)->setMinute(0)->setSecond(0);
        $end = $start->copy()->setHour(17);

        $this->assertThrowsTimeConflict(fn() => app(UserConflict::class)->validate(
            $this->userConflictContext($user, $resource, $start, $end, true)
        ));
    }

    public function test_user_conflict_passes_for_full_day_null_start_outside_day_range(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $noon = Carbon::today()->setHour(12);
        Reservation::factory()->create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => null,
            'end_time' => null,
            'is_full_day' => true,
            'status' => ReservationStatus::Active->value,
            'created_at' => $noon,
            'updated_at' => $noon,
        ]);
        $start = Carbon::tomorrow()->setHour(9)->setMinute(0)->setSecond(0);
        $end = $start->copy()->setHour(17);

        app(UserConflict::class)->validate($this->userConflictContext($user, $resource, $start, $end, true));
        $this->assertTrue(true);
    }

    public function test_user_conflict_is_full_day_flag_does_not_change_behavior(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $day = Carbon::now()->addDay()->setHour(9)->setMinute(0)->setSecond(0);
        $this->userConflictReserve($user, $resource, $day->copy(), $day->copy()->addHour(), ReservationStatus::Active->value);

        $this->assertThrowsTimeConflict(fn() => app(UserConflict::class)->validate(
            $this->userConflictContext($user, $resource, $day->copy()->addMinutes(30), $day->copy()->addHours(2), true)
        ));
    }

    public function test_resource_schedule_passes_when_metadata_absent(): void
    {
        $start = Carbon::create(2026, 8, 1, 10, 0, 0); // Saturday
        $resource = new Resource(['type' => 'meeting', 'metadata' => null]);

        (new ResourceSchedule())->validate($this->resourceScheduleContext($resource, $start, $start->copy()->addHour()));
        $this->assertTrue(true);
    }

    public function test_resource_schedule_passes_when_available_days_and_time_slots_absent_from_metadata(): void
    {
        $start = Carbon::create(2026, 8, 1, 10, 0, 0); // Saturday
        $resource = new Resource(['type' => 'meeting', 'metadata' => ['capacity' => 8]]);

        (new ResourceSchedule())->validate($this->resourceScheduleContext($resource, $start, $start->copy()->addHour()));
        $this->assertTrue(true);
    }

    public function test_resource_schedule_passes_when_available_days_empty_array(): void
    {
        $start = Carbon::create(2026, 8, 1, 10, 0, 0); // Saturday
        $resource = new Resource(['type' => 'meeting', 'metadata' => ['available_days' => []]]);

        (new ResourceSchedule())->validate($this->resourceScheduleContext($resource, $start, $start->copy()->addHour()));
        $this->assertTrue(true);
    }

    public function test_resource_schedule_passes_when_start_weekday_listed(): void
    {
        $start = Carbon::create(2026, 8, 1, 10, 0, 0); // Saturday
        $resource = new Resource(['type' => 'meeting', 'metadata' => ['available_days' => ['saturday']]]);

        (new ResourceSchedule())->validate($this->resourceScheduleContext($resource, $start, $start->copy()->addHour()));
        $this->assertTrue(true);
    }

    public function test_resource_schedule_throws_resource_day_not_allowed_when_weekday_not_listed(): void
    {
        $start = Carbon::create(2026, 8, 2, 10, 0, 0); // Sunday
        $resource = new Resource(['type' => 'meeting', 'metadata' => ['available_days' => ['saturday']]]);

        $this->assertThrowsReservationErrorStartsWith(
            ReservationError::ResourceDayNotAllowed,
            fn() => (new ResourceSchedule())->validate($this->resourceScheduleContext($resource, $start, $start->copy()->addHour())),
        );
    }

    public function test_resource_schedule_passes_when_within_time_slot(): void
    {
        $start = Carbon::create(2026, 8, 1, 10, 0, 0);
        $end = Carbon::create(2026, 8, 1, 11, 0, 0);
        $resource = new Resource(['type' => 'meeting', 'metadata' => ['time_slots' => ['start' => '09:00', 'end' => '12:00']]]);

        (new ResourceSchedule())->validate($this->resourceScheduleContext($resource, $start, $end));
        $this->assertTrue(true);
    }

    public function test_resource_schedule_throws_resource_hour_not_allowed_when_outside_time_slot(): void
    {
        $start = Carbon::create(2026, 8, 1, 14, 0, 0);
        $end = Carbon::create(2026, 8, 1, 15, 0, 0);
        $resource = new Resource(['type' => 'meeting', 'metadata' => ['time_slots' => ['start' => '09:00', 'end' => '12:00']]]);

        $this->assertThrowsReservationErrorStartsWith(
            ReservationError::ResourceHourNotAllowed,
            fn() => (new ResourceSchedule())->validate($this->resourceScheduleContext($resource, $start, $end)),
        );
    }

    public function test_resource_schedule_full_day_bypasses_time_slot_check_but_still_enforces_day(): void
    {
        $saturday = Carbon::create(2026, 8, 1, 0, 0, 0);
        $sunday = Carbon::create(2026, 8, 2, 0, 0, 0);
        $resource = new Resource(['type' => 'car', 'metadata' => [
            'available_days' => ['saturday'],
            'time_slots' => ['start' => '09:00', 'end' => '12:00'],
        ]]);

        (new ResourceSchedule())->validate($this->resourceScheduleContext($resource, $saturday, $saturday->copy()->endOfDay(), true));
        $this->assertTrue(true);

        $this->assertThrowsReservationErrorStartsWith(
            ReservationError::ResourceDayNotAllowed,
            fn() => (new ResourceSchedule())->validate($this->resourceScheduleContext($resource, $sunday, $sunday->copy()->endOfDay(), true)),
        );
    }

    public function test_resource_schedule_skips_time_check_when_time_slot_missing_end(): void
    {
        $start = Carbon::create(2026, 8, 1, 7, 0, 0);
        $resource = new Resource(['type' => 'meeting', 'metadata' => ['available_days' => ['saturday'], 'time_slots' => ['start' => '09:00']]]);

        (new ResourceSchedule())->validate($this->resourceScheduleContext($resource, $start, $start->copy()->addHour()));
        $this->assertTrue(true);
    }

    public function test_resource_schedule_skips_time_check_when_time_slot_missing_start(): void
    {
        $start = Carbon::create(2026, 8, 1, 7, 0, 0);
        $resource = new Resource(['type' => 'meeting', 'metadata' => ['available_days' => ['saturday'], 'time_slots' => ['end' => '12:00']]]);

        (new ResourceSchedule())->validate($this->resourceScheduleContext($resource, $start, $start->copy()->addHour()));
        $this->assertTrue(true);
    }

    public function test_resource_schedule_throws_resource_hour_not_allowed_when_start_before_time_slot(): void
    {
        $start = Carbon::create(2026, 8, 1, 7, 0, 0);
        $resource = new Resource(['type' => 'meeting', 'metadata' => ['available_days' => ['saturday'], 'time_slots' => ['start' => '09:00', 'end' => '12:00']]]);

        $this->assertThrowsReservationErrorStartsWith(
            ReservationError::ResourceHourNotAllowed,
            fn() => (new ResourceSchedule())->validate($this->resourceScheduleContext($resource, $start, $start->copy()->addHour())),
        );
    }
}