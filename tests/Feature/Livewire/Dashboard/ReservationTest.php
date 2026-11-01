<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Enums\CancelReason;
use App\Enums\ReservationError;
use App\Enums\ReservationStatus;
use App\Livewire\Dashboard\Reservation\Actions\BookAction;
use App\Livewire\Dashboard\Reservation\Main;
use App\Livewire\Dashboard\Reservation\Presentation\AvantgardePresenter;
use App\Livewire\Dashboard\Reservation\Presentation\ReservationSummaryPresenter;
use App\Livewire\Dashboard\Reservation\Presentation\TimeSlotPresenter;
use App\Models\Reservation;
use App\Models\ReservationPolicy;
use App\Models\Resource;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use App\Services\Reservation\ValidationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Morilog\Jalali\Jalalian;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        Livewire::withoutLazyLoading();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::rollBack();
        parent::tearDown();
    }

    private function pinToMidJalaliMonth(): void
    {
        $now = Jalalian::now();

        Carbon::setTestNow((new Jalalian($now->getYear(), $now->getMonth(), 10))->toCarbon()->setTime(10, 0));
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

    private function disableType(string $type): void
    {
        $now = now();
        ReservationPolicy::upsert([
            ['resource_type' => $type, 'key' => 'window_days', 'value' => json_encode(0), 'created_at' => $now, 'updated_at' => $now],
            ['resource_type' => $type, 'key' => 'window_hours', 'value' => json_encode(0), 'created_at' => $now, 'updated_at' => $now],
        ], ['resource_type', 'key'], ['value', 'updated_at']);

        app(ValidationService::class)->flushPolicyCache();
    }

    private function setPolicy(string $type, string $key, mixed $value): void
    {
        $now = now();
        ReservationPolicy::upsert([
            ['resource_type' => $type, 'key' => $key, 'value' => json_encode($value), 'created_at' => $now, 'updated_at' => $now],
        ], ['resource_type', 'key'], ['value', 'updated_at']);

        app(ValidationService::class)->flushPolicyCache();
    }

    private function clearPolicies(string $type): void
    {
        ReservationPolicy::where('resource_type', $type)->delete();
        app(ValidationService::class)->flushPolicyCache();
    }

    private function actor(array $over = []): User
    {
        return User::factory()->create(array_merge(
            ['status' => 'active', 'presence' => 'onsite', 'role' => 'user', 'booking' => ['all' => true]],
            $over
        ));
    }

    private function summaryOf(Main $instance, ?Resource $resource = null): string
    {
        return ReservationSummaryPresenter::summary($instance, $resource ?? Resource::factory()->make(['type' => $instance->activeTab, 'metadata' => []]));
    }

    private function assertBlockedError(string $code): callable
    {
        return fn($name, $params) => $params['type'] === 'error' && str_contains((string) $params['message'], $code);
    }

    public function test_reservation_book_and_cancel()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('date', now()->addDay()->toDateString())
            ->call('book', $resource->id)
            ->assertHasNoErrors();

        $reservation = Reservation::where('user_id', $user->id)->where('resource_id', $resource->id)->firstOrFail();
        $this->assertSame('active', $reservation->status);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('cancel', $reservation->id)
            ->assertHasNoErrors();

        $this->assertSame('cancelled_user', $reservation->refresh()->status);
    }

    public function test_full_day_booking_for_today_succeeds()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('date', now()->toDateString())
            ->call('book', $resource->id);

        $this->assertDatabaseHas('reservations', ['user_id' => $user->id, 'resource_id' => $resource->id, 'status' => 'active']);
    }

    public function test_same_full_day_resource_cannot_be_booked_twice()
    {
        $first = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $second = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');
        $start = now()->addDay()->startOfDay();
        $action = app(BookAction::class);

        $action->execute($first, $resource, $start, $start->copy()->endOfDay(), true);

        try {
            $action->execute($second, $resource, $start->copy(), $start->copy()->endOfDay(), true);
            $this->fail('second booking should have been rejected');
        } catch (\Exception) {
            $this->assertSame(1, Reservation::where('resource_id', $resource->id)->count());
        }
    }

    public function test_disabled_type_tab_is_marked_disabled_in_menu()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user', 'booking' => ['all' => true]]);

        $this->disableType('car');

        $html = Livewire::actingAs($user)
            ->test(Main::class)
            ->assertHasNoErrors()
            ->html();

        $this->assertStringContainsString('disabled', $html);
    }

    public function test_booking_disabled_type_is_blocked_for_regular_user()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user', 'booking' => ['all' => true]]);
        $resource = Resource::factory()->create(['type' => 'car', 'status' => 'active', 'metadata' => []]);

        $this->disableType('car');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('date', now()->addDay()->toDateString())
            ->call('book', $resource->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('reservations', ['user_id' => $user->id, 'resource_id' => $resource->id]);
    }

    public function test_booking_disabled_type_is_blocked_for_admin_too()
    {
        $admin = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'admin', 'booking' => ['all' => true]]);
        $resource = Resource::factory()->create(['type' => 'car', 'status' => 'active', 'metadata' => []]);

        $this->disableType('car');

        Livewire::actingAs($admin)
            ->test(Main::class)
            ->set('date', now()->addDay()->toDateString())
            ->call('book', $resource->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('reservations', ['user_id' => $admin->id, 'resource_id' => $resource->id]);
    }

    public function test_switch_tab_ignores_disabled_type()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user', 'booking' => ['all' => true]]);

        $this->disableType('car');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('activeTab', 'seat')
            ->call('switchTab', 'car')
            ->assertSet('activeTab', 'seat');
    }

    public function test_disabled_types_helper_reflects_policy_changes_via_cache_flush()
    {
        ModelCacheVersion::bump(ReservationPolicy::class);
        $service = app(ValidationService::class);

        $this->assertNotContains('spot', $service->disabledTypes());

        $this->disableType('spot');

        $this->assertContains('spot', $service->disabledTypes());
        $this->assertContains('spot', disabledReservationTypes());
    }

    public function test_disabled_types_helper_issues_bounded_cache_lookups_when_warm()
    {
        $originalDriver = config('cache.default');
        config(['cache.default' => 'database']);
        ModelCacheVersion::bump(ReservationPolicy::class);

        try {
            app(ValidationService::class)->disabledTypes();

            DB::flushQueryLog();
            DB::enableQueryLog();
            disabledReservationTypes();

            // 2, not 1: one lookup for the current ReservationPolicy version token, one for the versioned cache entry itself.
            $this->assertCount(2, DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            config(['cache.default' => $originalDriver]);
            ModelCacheVersion::bump(ReservationPolicy::class);
        }
    }

    public function test_user_without_booking_permission_cannot_book_seat()
    {
        $user = $this->actor(['booking' => ['all' => false, 'seat' => false, 'spot' => false, 'meeting' => false, 'car' => false]]);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('date', now()->addDay()->toDateString())
            ->call('book', $resource->id)
            ->assertDispatched('toast', $this->assertBlockedError(ReservationError::NoPermission->value));

        $this->assertDatabaseMissing('reservations', ['user_id' => $user->id, 'resource_id' => $resource->id]);
    }

    public function test_admin_without_booking_permission_cannot_book()
    {
        $admin = $this->actor(['role' => 'admin', 'booking' => ['all' => false]]);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');

        Livewire::actingAs($admin)
            ->test(Main::class)
            ->set('date', now()->addDay()->toDateString())
            ->call('book', $resource->id)
            ->assertDispatched('toast', $this->assertBlockedError(ReservationError::NoPermission->value));

        $this->assertDatabaseMissing('reservations', ['user_id' => $admin->id, 'resource_id' => $resource->id]);
    }

    public function test_inactive_user_cannot_book()
    {
        $user = $this->actor(['status' => 'inactive']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('date', now()->addDay()->toDateString())
            ->call('book', $resource->id)
            ->assertDispatched('toast', $this->assertBlockedError(ReservationError::UserInactive->value));

        $this->assertDatabaseMissing('reservations', ['user_id' => $user->id]);
    }

    public function test_booking_in_the_past_is_blocked()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('date', now()->subDay()->toDateString())
            ->call('book', $resource->id)
            ->assertDispatched('toast', $this->assertBlockedError(ReservationError::PastBooking->value));

        $this->assertDatabaseMissing('reservations', ['user_id' => $user->id, 'resource_id' => $resource->id]);
    }

    public function test_booking_beyond_window_days_is_blocked()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'window_days', 2);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('date', now()->addDays(5)->toDateString())
            ->call('book', $resource->id)
            ->assertDispatched('toast', $this->assertBlockedError(ReservationError::WindowExceeded->value));

        $this->assertDatabaseMissing('reservations', ['user_id' => $user->id, 'resource_id' => $resource->id]);
    }

    public function test_admin_is_subject_to_time_window_when_booking()
    {
        $admin = $this->actor(['role' => 'admin']);
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'window_days', 2);

        Livewire::actingAs($admin)
            ->test(Main::class)
            ->set('date', now()->addDays(5)->toDateString())
            ->call('book', $resource->id)
            ->assertDispatched('toast', $this->assertBlockedError(ReservationError::WindowExceeded->value));

        $this->assertDatabaseMissing('reservations', ['user_id' => $admin->id, 'resource_id' => $resource->id]);
    }

    public function test_booking_on_disallowed_weekday_is_blocked()
    {
        $user = $this->actor();
        $tomorrow = now()->addDay();
        $tomorrowDay = strtolower($tomorrow->englishDayOfWeek);
        $allowed = $tomorrowDay === 'monday' ? ['tuesday'] : ['monday'];
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'allowed_days', $allowed);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('date', $tomorrow->toDateString())
            ->call('book', $resource->id)
            ->assertDispatched('toast', $this->assertBlockedError(ReservationError::DayNotAllowed->value));

        $this->assertDatabaseMissing('reservations', ['user_id' => $user->id, 'resource_id' => $resource->id]);
    }

    public function test_meeting_booking_below_min_duration_is_blocked()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'meeting', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('meeting');
        $this->setPolicy('meeting', 'min_duration_minutes', 60);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('switchTab', 'meeting')
            ->set('date', now()->addDay()->toDateString())
            ->set('startTime', '09:00')
            ->set('endTime', '09:30')
            ->call('book', $resource->id)
            ->assertDispatched('toast', $this->assertBlockedError(ReservationError::TooShort->value));

        $this->assertDatabaseMissing('reservations', ['user_id' => $user->id, 'resource_id' => $resource->id]);
    }

    public function test_meeting_booking_above_max_duration_is_blocked()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'meeting', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('meeting');
        $this->setPolicy('meeting', 'max_duration_minutes', 60);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('switchTab', 'meeting')
            ->set('date', now()->addDay()->toDateString())
            ->set('startTime', '09:00')
            ->set('endTime', '11:00')
            ->call('book', $resource->id)
            ->assertDispatched('toast', $this->assertBlockedError(ReservationError::TooLong->value));

        $this->assertDatabaseMissing('reservations', ['user_id' => $user->id, 'resource_id' => $resource->id]);
    }

    public function test_meeting_end_time_before_start_time_is_blocked()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'meeting', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('meeting');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('switchTab', 'meeting')
            ->set('date', now()->addDay()->toDateString())
            ->set('startTime', '10:00')
            ->set('endTime', '09:00')
            ->call('book', $resource->id)
            ->assertDispatched('toast', $this->assertBlockedError(ReservationError::InvalidTimeRange->value));

        $this->assertDatabaseMissing('reservations', ['user_id' => $user->id, 'resource_id' => $resource->id]);
    }

    public function test_full_day_booking_blocked_when_policy_disallows()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'allow_full_day', false);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('date', now()->addDay()->toDateString())
            ->call('book', $resource->id)
            ->assertDispatched('toast', $this->assertBlockedError(ReservationError::FullDayForbidden->value));

        $this->assertDatabaseMissing('reservations', ['user_id' => $user->id, 'resource_id' => $resource->id]);
    }

    public function test_meeting_outside_allowed_hours_is_blocked()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'meeting', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('meeting');
        $this->setPolicy('meeting', 'allowed_hours', ['start' => '10:00', 'end' => '12:00']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('switchTab', 'meeting')
            ->set('date', now()->addDay()->toDateString())
            ->set('startTime', '08:00')
            ->set('endTime', '09:00')
            ->call('book', $resource->id)
            ->assertDispatched('toast', $this->assertBlockedError(ReservationError::HourNotAllowed->value));

        $this->assertDatabaseMissing('reservations', ['user_id' => $user->id, 'resource_id' => $resource->id]);
    }

    public function test_recurring_toggle_is_ignored_when_policy_disallows_repeat()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'allow_repeat', false);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('date', now()->addDay()->toDateString())
            ->set('isRecurring', true)
            ->set('recurPattern', 'daily')
            ->set('recurCount', 3)
            ->call('book', $resource->id)
            ->assertDispatched('toast', fn($n, $p) => $p['type'] === 'success');

        $this->assertSame(1, Reservation::where('user_id', $user->id)->where('resource_id', $resource->id)->count());
        $this->assertSame(0, Reservation::where('user_id', $user->id)->whereNotNull('parent_id')->count());
    }

    public function test_booking_block_reason_opens_for_last_minute_dates_only_when_the_user_is_at_the_cap()
    {
        $this->travelTo(\Carbon\Carbon::create(2026, 10, 10, 12, 0, 0));
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'max_per_user', 1);
        $this->setPolicy('seat', 'window_days', 30);
        $this->setPolicy('seat', 'last_minute_hours', 24);
        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => '2026-10-20 00:00:00',
            'end_time' => '2026-10-20 23:59:59',
            'is_full_day' => true,
            'status' => 'active',
            'parent_id' => null,
        ]);

        $component = Livewire::actingAs($user)->test(Main::class);

        $this->assertNull($component->set('date', '2026-10-11')->get('bookingBlockReason'));
        $this->assertStringContainsString('ساعت دیگر شروع', (string) $component->set('date', '2026-10-13')->get('bookingBlockReason'));

        $this->setPolicy('seat', 'last_minute_hours', null);
        $this->assertStringNotContainsString('ساعت دیگر شروع', (string) $component->set('date', '2026-10-14')->get('bookingBlockReason'));
        $this->travelBack();
    }

    public function test_last_minute_booking_relaxes_only_the_quota_through_the_full_booking_flow()
    {
        $this->travelTo(\Carbon\Carbon::create(2026, 10, 10, 12, 0, 0));
        $user = $this->actor();
        $other = $this->actor();
        $free = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $taken = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'max_per_user', 1);
        $this->setPolicy('seat', 'window_days', 30);
        $this->setPolicy('seat', 'last_minute_hours', 24);
        $book = fn(User $who, Resource $resource, string $start) => Reservation::create([
            'user_id' => $who->id,
            'resource_id' => $resource->id,
            'start_time' => "$start 00:00:00",
            'end_time' => "$start 23:59:59",
            'is_full_day' => true,
            'status' => 'active',
            'parent_id' => null,
        ]);
        $book($user, $free, '2026-10-20');
        $book($other, $taken, '2026-10-11');

        $component = Livewire::actingAs($user)->test(Main::class)->set('date', '2026-10-11');

        $component->call('book', $taken->id)
            ->assertDispatched('toast', $this->assertBlockedError(ReservationError::ResourceTaken->value));
        $this->assertDatabaseMissing('reservations', ['user_id' => $user->id, 'resource_id' => $taken->id]);

        $component->call('book', $free->id)->assertDispatched('toast', fn($name, $params) => ($params['type'] ?? null) === 'success');
        $this->assertDatabaseHas('reservations', ['user_id' => $user->id, 'resource_id' => $free->id, 'start_time' => '2026-10-11 00:00:00']);

        $component->set('date', '2026-10-13')->call('book', $free->id)
            ->assertDispatched('toast', $this->assertBlockedError(ReservationError::ActiveLimitReached->value));
        $this->assertDatabaseMissing('reservations', ['user_id' => $user->id, 'start_time' => '2026-10-13 00:00:00']);
        $this->travelBack();
    }

    private function dailySetup(): array
    {
        $this->travelTo(\Carbon\Carbon::create(2026, 10, 10, 12, 0, 0));
        $tag = 'zz' . uniqid();
        $viewer = $this->actor();
        $colleague = $this->actor(['name' => "Col-$tag", 'type' => 'employee']);
        $desk = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'name' => "$tag-desk", 'metadata' => ['floor' => '3']]);
        $this->clearPolicies('seat');

        return [$viewer, $colleague, $desk, $tag];
    }

    private function dailyBook(User $who, Resource $resource, string $day, string $status = 'active'): Reservation
    {
        return Reservation::create([
            'user_id' => $who->id,
            'resource_id' => $resource->id,
            'start_time' => "$day 00:00:00",
            'end_time' => "$day 23:59:59",
            'is_full_day' => true,
            'status' => $status,
            'parent_id' => null,
        ]);
    }

    private function dailyOpen(User $viewer, string $tag)
    {
        return Livewire::actingAs($viewer)->test(Main::class)->set('date', '2026-10-10')->call('openDaily')->set('daily.search', $tag);
    }

    public function test_daily_modal_is_empty_until_opened_then_lists_colleagues_with_names()
    {
        [$viewer, $colleague, $desk, $tag] = $this->dailySetup();
        $this->dailyBook($colleague, $desk, '2026-10-10');

        $component = Livewire::actingAs($viewer)->test(Main::class)->set('date', '2026-10-10');
        $this->assertSame([], $component->get('dailyData')['groups']);

        $component->call('openDaily')->set('daily.search', $tag);
        $this->assertSame(1, $component->get('dailyData')['total']);
        $component->assertSee("Col-$tag")->assertSee("$tag-desk");
        $this->travelBack();
    }

    public function test_daily_modal_scopes_quick_ranges_and_navigation()
    {
        [$viewer, $colleague, $desk, $tag] = $this->dailySetup();
        foreach (['2026-10-10', '2026-10-14', '2026-10-17', '2026-10-25'] as $day) {
            $this->dailyBook($colleague, $desk, $day);
        }

        $component = $this->dailyOpen($viewer, $tag);
        $total = fn() => $component->get('dailyData')['total'];

        $this->assertSame(1, $total());
        $component->call('setDailyQuick', 'week');
        $this->assertSame(2, $total());
        $component->call('setDailyQuick', 'next_week');
        $this->assertSame(1, $total());
        $component->call('setDailyQuick', 'month');
        $this->assertSame(3, $total());
        $component->call('setDailyQuick', 'next_month');
        $this->assertSame(1, $total());
        $component->call('setDailyQuick', 'month')->call('shiftDaily', 1);
        $this->assertSame(1, $total());
        $component->call('setDailyQuick', 'nonsense');
        $this->assertSame(1, $total());
        $this->travelBack();
    }

    public function test_daily_modal_filters_by_type_search_and_mine()
    {
        [$viewer, $colleague, $desk, $tag] = $this->dailySetup();
        $meeting = Resource::factory()->create(['type' => 'meeting', 'status' => 'active', 'name' => "$tag-meeting", 'metadata' => []]);
        $this->dailyBook($colleague, $desk, '2026-10-10');
        $this->dailyBook($viewer, $desk, '2026-10-11');
        $this->dailyBook($colleague, $meeting, '2026-10-10');

        $component = $this->dailyOpen($viewer, $tag)->call('setDailyQuick', 'month');
        $total = fn() => $component->get('dailyData')['total'];

        $this->assertSame(2, $total());
        $component->set('daily.type', 'all');
        $this->assertSame(3, $total());
        $component->set('daily.type', 'seat')->set('daily.search', "Col-$tag");
        $this->assertSame(1, $total());
        $component->set('daily.search', "$tag-desk")->set('daily.mine', true);
        $this->assertSame(1, $total());
        $this->travelBack();
    }

    public function test_daily_modal_custom_range_validates_and_hides_cancelled_and_guest_identity()
    {
        [$viewer, $colleague, $desk, $tag] = $this->dailySetup();
        $guest = $this->actor(['name' => "Guest-$tag", 'type' => 'guest']);
        $other = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'name' => "$tag-other", 'metadata' => []]);
        $this->dailyBook($colleague, $desk, '2026-10-12', 'cancelled_user');
        $this->dailyBook($guest, $other, '2026-10-12');

        $component = $this->dailyOpen($viewer, $tag)->call('setDailyQuick', 'custom');
        $component->set('daily.fromYear', 1405)->set('daily.fromMonth', 7)->set('daily.fromDay', 1)
            ->set('daily.toYear', 1407)->set('daily.toMonth', 7)->set('daily.toDay', 1)
            ->call('applyDailyRange')
            ->assertHasErrors(['daily.toDay']);

        $component->set('daily.fromYear', 1405)->set('daily.fromMonth', 7)->set('daily.fromDay', 20)
            ->set('daily.toYear', 1405)->set('daily.toMonth', 7)->set('daily.toDay', 25)
            ->call('applyDailyRange');

        $this->assertSame('custom', $component->get('daily.scope'));
        $this->assertSame(1, $component->get('dailyData')['total']);
        $component->assertSee('کاربر مهمان')->assertDontSee("Guest-$tag")->assertDontSee("Col-$tag");
        $this->travelBack();
    }

    public function test_daily_modal_custom_range_shows_a_validation_message_for_each_kind_of_mistake()
    {
        [$viewer, $colleague, $desk, $tag] = $this->dailySetup();
        $component = $this->dailyOpen($viewer, $tag)->call('setDailyQuick', 'custom');
        $fill = fn(array $parts) => $component->set('daily', [...$component->get('daily'), ...$parts]);

        $component->call('applyDailyRange')->assertHasErrors(['daily.fromDay']);

        $fill(['fromYear' => 1405, 'fromMonth' => 7, 'fromDay' => 31, 'toYear' => 1405, 'toMonth' => 7, 'toDay' => 31]);
        $component->call('applyDailyRange')->assertHasErrors(['daily.fromDay']);

        $fill(['fromYear' => 1405, 'fromMonth' => 7, 'fromDay' => 20, 'toYear' => 1405, 'toMonth' => 7, 'toDay' => 10]);
        $component->call('applyDailyRange')->assertHasErrors(['daily.toDay']);

        $fill(['fromYear' => 1405, 'fromMonth' => 7, 'fromDay' => 1, 'toYear' => 1406, 'toMonth' => 7, 'toDay' => 1]);
        $component->call('applyDailyRange')->assertHasErrors(['daily.toDay']);

        $fill(['fromYear' => 1405, 'fromMonth' => 7, 'fromDay' => 1, 'toYear' => 1405, 'toMonth' => 7, 'toDay' => 10]);
        $component->call('applyDailyRange')->assertHasNoErrors();
        $this->assertSame('custom', $component->get('daily.scope'));
        $this->travelBack();
    }

    public function test_daily_modal_groups_long_holds_under_the_window_start_not_their_own_start()
    {
        [$viewer, $colleague, $desk, $tag] = $this->dailySetup();
        Reservation::create([
            'user_id' => $colleague->id,
            'resource_id' => $desk->id,
            'start_time' => '2026-09-04 05:30:00',
            'end_time' => '2027-03-20 13:30:00',
            'is_full_day' => false,
            'status' => 'active',
            'parent_id' => null,
        ]);

        $component = $this->dailyOpen($viewer, $tag);
        $groups = $component->get('dailyData')['groups'];

        $this->assertSame(1, $component->get('dailyData')['total']);
        $this->assertSame('2026-10-10', $groups[0]['key']);
        $this->assertStringContainsString('از', $groups[0]['rows'][0]['time']);

        $component->call('setDailyQuick', 'month');
        $this->assertSame('2026-09-23', $component->get('dailyData')['groups'][0]['key']);
        $this->travelBack();
    }

    public function test_daily_modal_shows_a_zoomable_thumbnail_only_for_resources_with_an_image()
    {
        [$viewer, $colleague, $desk, $tag] = $this->dailySetup();
        $desk->update(['image' => 'resources/zz-' . $tag . '.jpg']);
        $plain = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'name' => "$tag-plain", 'image' => null, 'metadata' => []]);
        $this->dailyBook($colleague, $desk, '2026-10-10');
        $this->dailyBook($colleague, $plain, '2026-10-10');

        $this->dailyOpen($viewer, $tag)
            ->assertSee("مشاهده تصویر $tag-desk")
            ->assertDontSee("مشاهده تصویر $tag-plain");
        $this->travelBack();
    }

    public function test_daily_modal_paginates_with_load_more()
    {
        [$viewer, $colleague, $desk, $tag] = $this->dailySetup();
        foreach (range(1, 35) as $i) {
            $this->dailyBook($colleague, Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'name' => "$tag-p$i", 'metadata' => []]), '2026-10-10');
        }

        $component = $this->dailyOpen($viewer, $tag);
        $this->assertSame(35, $component->get('dailyData')['total']);
        $this->assertTrue($component->get('dailyData')['more']);
        $component->call('loadMoreDaily');
        $this->assertFalse($component->get('dailyData')['more']);
        $this->travelBack();
    }

    public function test_daily_modal_ignores_tampered_state_and_never_exposes_unpermitted_types()
    {
        [$viewer, $colleague, $desk, $tag] = $this->dailySetup();
        $viewer->update(['booking' => ['seat' => true]]);
        $meeting = Resource::factory()->create(['type' => 'meeting', 'status' => 'active', 'name' => "$tag-meeting", 'metadata' => []]);
        $this->dailyBook($colleague, $desk, '2026-10-10');
        $this->dailyBook($colleague, $meeting, '2026-10-10');

        $component = $this->dailyOpen($viewer->fresh(), $tag);

        $component->set('daily.type', 'meeting');
        $this->assertSame(1, $component->get('dailyData')['total']);
        $component->set('daily.type', 'all');
        $this->assertSame(1, $component->get('dailyData')['total']);

        $component->set('daily.limit', 1000000000)->set('daily.anchor', '9999-01-01')->call('setDailyQuick', 'month')->set('daily.anchor', '9999-01-01');
        $this->assertLessThanOrEqual(\App\Livewire\Dashboard\Reservation\Presentation\DailyPresenter::MAX_ROWS, $component->get('dailyData')['total']);

        $component->set('daily', []);
        $this->assertIsArray($component->get('dailyData')['groups']);
        $this->travelBack();
    }

    public function test_reach_out_refuses_guests_inactive_holders_and_unpermitted_types()
    {
        [$viewer, $colleague, $desk, $tag] = $this->dailySetup();
        \Illuminate\Support\Facades\RateLimiter::clear("reservation-contact:{$viewer->id}");
        $guest = $this->actor(['type' => 'guest']);
        $inactive = $this->actor(['type' => 'employee', 'status' => 'inactive']);
        $meeting = Resource::factory()->create(['type' => 'meeting', 'status' => 'active', 'metadata' => []]);
        $viewer->update(['booking' => ['seat' => true]]);

        $byGuest = $this->dailyBook($guest, $desk, '2026-10-10');
        $byInactive = $this->dailyBook($inactive, Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]), '2026-10-10');
        $onMeeting = $this->dailyBook($colleague, $meeting, '2026-10-10');

        $component = Livewire::actingAs($viewer->fresh())->test(Main::class);
        foreach ([$byGuest, $byInactive, $onMeeting] as $reservation) {
            $component->call('reachOut', $reservation->id, 'contact')->assertDispatched('toast', fn($name, $params) => ($params['type'] ?? null) === 'error');
        }

        $this->assertSame(0, \App\Models\Message::where('sender_id', $viewer->id)->count());
        $this->travelBack();
    }

    public function test_reach_out_refuses_cancelled_reservations_but_allows_released_ones()
    {
        [$viewer, $colleague, $desk, $tag] = $this->dailySetup();
        \Illuminate\Support\Facades\RateLimiter::clear("reservation-contact:{$viewer->id}");
        $cancelled = $this->dailyBook($colleague, $desk, '2026-10-10', 'cancelled_user');
        $released = $this->dailyBook($colleague, Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]), '2026-10-10', 'released');

        $component = Livewire::actingAs($viewer)->test(Main::class);
        $component->call('reachOut', $cancelled->id, 'contact')->assertDispatched('toast', fn($name, $params) => ($params['type'] ?? null) === 'error');
        $this->assertSame(0, \App\Models\Message::where('sender_id', $viewer->id)->count());

        $component->call('reachOut', $released->id, 'contact')->assertDispatched('toast', fn($name, $params) => ($params['type'] ?? null) === 'success');
        $this->assertSame(1, \App\Models\Message::where('sender_id', $viewer->id)->count());
        \Illuminate\Support\Facades\RateLimiter::clear("reservation-contact:{$viewer->id}");
        $this->travelBack();
    }

    public function test_daily_modal_hides_phone_contacts_when_the_holder_presence_is_obscured()
    {
        [$viewer, $colleague, $desk, $tag] = $this->dailySetup();
        config(['app.office_phone' => '02100000000']);
        $desk->update(['metadata' => ['extension' => '123']]);
        $this->dailyBook($colleague, $desk, '2026-10-10');
        $component = $this->dailyOpen($viewer, $tag);
        $contact = fn() => $component->get('dailyData')['groups'][0]['rows'][0]['contact'];

        $colleague->update(['presence' => 'onsite']);
        $this->assertNotNull($contact()['extTel']);

        $colleague->update(['presence' => 'grumpy']);
        $component->call('loadMoreDaily');
        $this->assertNull($contact()['extTel']);
        $this->assertNull($contact()['mobileTel']);
        $this->assertNotNull($contact()['chatUrl']);
        $this->travelBack();
    }

    public function test_daily_modal_state_helpers_reset_and_reseed_correctly()
    {
        [$viewer, $colleague, $desk, $tag] = $this->dailySetup();
        $component = $this->dailyOpen($viewer, $tag);

        $component->call('setDailyQuick', 'custom')->set('daily.anchor', '2026-10-10')->call('shiftDaily', 1);
        $this->assertSame('2026-10-10', $component->get('daily.anchor'));

        $component->set('daily.limit', 90)->set('daily.search', $tag . 'x');
        $this->assertSame(30, $component->get('daily.limit'));

        $component->set('daily.type', 'meeting')->call('openDaily');
        $this->assertSame('seat', $component->get('daily.type'));
        $this->travelBack();
    }

    public function test_reach_out_sms_sends_chat_plus_a_sender_prefixed_sms_and_checks_every_precondition()
    {
        config(['services.kavenegar.key' => 'K']);
        \Illuminate\Support\Facades\Http::fake(['api.kavenegar.com/*' => \Illuminate\Support\Facades\Http::response(['return' => ['status' => 200]])]);
        [$viewer, $colleague, $desk] = $this->dailySetup();
        \Illuminate\Support\Facades\RateLimiter::clear("reservation-contact:{$viewer->id}");
        $profile = \App\Models\Profile::where('user_id', $colleague->id)->first();
        $profile
            ? $profile->update(['cellphone' => '09120000000'])
            : \App\Models\Profile::factory()->create(['user_id' => $colleague->id, 'cellphone' => '09120000000']);
        $theirs = $this->dailyBook($colleague, $desk, '2026-10-10');

        $component = Livewire::actingAs($viewer)->test(Main::class);

        $component->call('reachOutSms', $theirs->id)->assertDispatched('toast', fn($name, $params) => ($params['type'] ?? null) === 'success');
        $this->assertDatabaseHas('messages', ['sender_id' => $viewer->id, 'recipient_id' => $colleague->id]);
        \Illuminate\Support\Facades\Http::assertSent(fn($request) => $request['receptor'] === '09120000000'
            && str_starts_with($request['message'], $viewer->name . ': ')
            && str_contains($request['message'], $desk->name));
        $this->assertDatabaseHas('notify_logs', ['source' => 'reservation', 'sender_id' => $viewer->id, 'user_id' => $colleague->id, 'status' => 'sent', 'recipient' => '*******0000']);

        $messages = \App\Models\Message::count();
        config(['app.off' => ['notify:sms']]);
        $component->call('reachOutSms', $theirs->id)->assertDispatched('toast', fn($name, $params) => ($params['type'] ?? null) === 'error');
        config(['app.off' => []]);

        \App\Models\Profile::where('user_id', $colleague->id)->update(['cellphone' => null]);
        $component->call('reachOutSms', $theirs->id)->assertDispatched('toast', fn($name, $params) => ($params['type'] ?? null) === 'error');

        $this->assertSame($messages, \App\Models\Message::count());
        \Illuminate\Support\Facades\Http::assertSentCount(1);
        \Illuminate\Support\Facades\RateLimiter::clear("reservation-contact:{$viewer->id}");
        $this->travelBack();
    }

    public function test_reach_out_sends_a_chat_message_and_enforces_guards()
    {
        [$viewer, $colleague, $desk] = $this->dailySetup();
        \Illuminate\Support\Facades\RateLimiter::clear("reservation-contact:{$viewer->id}");
        $theirs = $this->dailyBook($colleague, $desk, '2026-10-10');
        $mine = $this->dailyBook($viewer, Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]), '2026-10-11');

        $component = Livewire::actingAs($viewer)->test(Main::class);

        $component->call('reachOut', $theirs->id, 'move')->assertDispatched('toast', fn($name, $params) => ($params['type'] ?? null) === 'success');
        $this->assertDatabaseHas('messages', ['sender_id' => $viewer->id, 'recipient_id' => $colleague->id]);
        $this->assertStringContainsString($desk->name, \App\Models\Message::where('sender_id', $viewer->id)->latest('id')->value('body'));

        $component->call('reachOut', $mine->id, 'move')->assertDispatched('toast', fn($name, $params) => ($params['type'] ?? null) === 'error');
        $component->call('reachOut', $theirs->id, 'bogus')->assertDispatched('toast', fn($name, $params) => ($params['type'] ?? null) === 'error');
        $this->assertSame(1, \App\Models\Message::where('sender_id', $viewer->id)->count());

        foreach (range(1, 5) as $i) {
            $component->call('reachOut', $theirs->id, 'contact');
        }
        $this->assertSame(5, \App\Models\Message::where('sender_id', $viewer->id)->count());
        \Illuminate\Support\Facades\RateLimiter::clear("reservation-contact:{$viewer->id}");
        $this->travelBack();
    }

    public function test_daily_presenter_week_starts_saturday_and_months_are_jalali()
    {
        $anchor = \Carbon\Carbon::create(2026, 10, 14, 9, 0, 0);

        [$from, $to] = \App\Livewire\Dashboard\Reservation\Presentation\DailyPresenter::range('week', $anchor);
        $this->assertSame('2026-10-10', $from->toDateString());
        $this->assertSame('2026-10-16', $to->toDateString());

        [$from, $to] = \App\Livewire\Dashboard\Reservation\Presentation\DailyPresenter::range('month', $anchor);
        $this->assertSame('2026-09-23', $from->toDateString());
        $this->assertSame('2026-10-22', $to->toDateString());

        $next = \App\Livewire\Dashboard\Reservation\Presentation\DailyPresenter::shift('month', $anchor, 1);
        $this->assertSame('2026-10-23', $next->toDateString());
    }

    public function test_quota_bars_report_level_remaining_reset_and_cancel_drop_off()
    {
        $this->travelTo(\Carbon\Carbon::create(2026, 10, 10, 12, 0, 0));
        $user = $this->actor();
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'max_per_user', 5);
        $this->setPolicy('seat', 'max_cancel_count', 3);
        $this->setPolicy('seat', 'window_days', 60);

        foreach (['2026-10-11', '2026-10-12', '2026-10-13', '2026-10-14'] as $day) {
            $this->dailyBook($user, Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]), $day);
        }
        $cancelled = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        foreach ([5, 20] as $daysAgo) {
            Reservation::create([
                'user_id' => $user->id,
                'resource_id' => $cancelled->id,
                'start_time' => '2026-09-01 00:00:00',
                'end_time' => '2026-09-01 23:59:59',
                'is_full_day' => true,
                'status' => 'cancelled_user',
                'cancelled_at' => now()->subDays($daysAgo),
                'parent_id' => null,
            ]);
        }

        $component = Livewire::actingAs($user)->test(Main::class)->set('date', '2026-10-10');
        $booking = $component->get('activeLimitUsage');
        $cancel = $component->get('cancelLimitUsage');

        $this->assertSame(['count' => 4, 'max' => 5, 'level' => 'warn', 'left' => 1], \Illuminate\Support\Arr::only($booking, ['count', 'max', 'level', 'left']));
        $this->assertSame(13, $booking['reset']['days']);
        $this->assertStringContainsString('آبان', $booking['reset']['date']);
        $this->assertSame(['count' => 2, 'max' => 3, 'level' => 'ok', 'left' => 1, 'dropsInDays' => 10], \Illuminate\Support\Arr::only($cancel, ['count', 'max', 'level', 'left', 'dropsInDays']));

        $component->set('date', '2026-11-20');
        $this->assertNull($component->get('activeLimitUsage')['reset']);
        $this->travelBack();
    }

    public function test_cancel_bar_follows_the_window_policy_and_zero_cap_disables_cancelling_without_blocking_booking()
    {
        $this->travelTo(\Carbon\Carbon::create(2026, 10, 10, 12, 0, 0));
        $user = $this->actor();
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'max_cancel_count', 3);
        $this->setPolicy('seat', 'cancel_window_days', 60);
        $cancelled = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $cancelled->id,
            'start_time' => '2026-08-01 00:00:00',
            'end_time' => '2026-08-01 23:59:59',
            'is_full_day' => true,
            'status' => 'cancelled_user',
            'cancelled_at' => now()->subDays(45),
            'parent_id' => null,
        ]);

        $cancel = Livewire::actingAs($user)->test(Main::class)->get('cancelLimitUsage');
        $this->assertSame(60, $cancel['days']);
        $this->assertSame(1, $cancel['count']);
        $this->assertSame(15, $cancel['dropsInDays']);
        $this->travelBack();
    }

    public function test_zero_cancel_cap_shows_a_full_bar_and_a_chip_but_never_blocks_booking()
    {
        $user = $this->actor();
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'max_cancel_count', 0);

        $component = Livewire::actingAs($user)->test(Main::class);
        $zero = $component->get('cancelLimitUsage');
        $this->assertSame(0, $zero['max']);
        $this->assertSame('full', $zero['level']);
        $this->assertFalse($zero['blocked']);
        $this->assertStringContainsString('لغو مجاز نیست', implode(' ', array_column($component->get('policyChips'), 'text')));
    }

    public function test_last_minute_opening_time_and_policy_chips_follow_the_policies()
    {
        $this->travelTo(\Carbon\Carbon::create(2026, 10, 10, 12, 0, 0));
        $user = $this->actor();
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'max_per_user', 1);
        $this->setPolicy('seat', 'window_days', 30);
        $this->setPolicy('seat', 'last_minute_hours', 24);
        $this->setPolicy('seat', 'allowed_days', ['saturday', 'sunday', 'monday']);
        $this->setPolicy('seat', 'allow_repeat', false);
        $this->dailyBook($user, Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]), '2026-10-20');

        $component = Livewire::actingAs($user)->test(Main::class)->set('date', '2026-10-14');
        $this->assertNotNull($component->get('lastMinuteOpensAt'));
        $component->set('date', '2026-10-11');
        $this->assertNull($component->get('lastMinuteOpensAt'));

        $texts = implode(' | ', array_column($component->get('policyChips'), 'text'));
        foreach (['رزرو تا', 'روزهای مجاز', 'رزرو تکراری: مجاز نیست', 'رزرو لحظه‌آخری تا'] as $needle) {
            $this->assertStringContainsString($needle, $texts);
        }
        $this->assertStringNotContainsString('ساعت مجاز', $texts);
        $this->travelBack();
    }

    public function test_policy_summary_shows_every_rule_that_is_set()
    {
        $presenter = \App\Livewire\Dashboard\Reservation\Presentation\PolicySummaryPresenter::class;
        $policies = [
            'window_days' => 14, 'window_hours' => 0, 'allowed_days' => ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'max_per_user' => 4, 'max_cancel_count' => 3, 'max_range_days' => 20, 'last_minute_hours' => 12,
            'allow_repeat' => true, 'allow_full_day' => false, 'allow_partial_cancel' => false, 'allow_overlap_release' => true, 'requires_approval' => true,
        ];

        $text = implode(' | ', array_column($presenter::chips($policies, false), 'text'));
        foreach (['بدون حداقل مهلت', 'همهٔ روزهای هفته', 'سقف رزرو ماهانه: ۴', 'سقف لغو', 'رزرو تکراری: مجاز', 'رزرو تمام‌روز: مجاز نیست', 'کل سری لغو می‌شود', 'منبع دوباره قابل رزرو می‌شود', 'تأیید مدیر', 'رزرو بلندمدت', 'لحظه‌آخری'] as $needle) {
            $this->assertStringContainsString($needle, $text, $needle);
        }

        $this->assertStringContainsString('سقف شخصی شما', implode(' | ', array_column($presenter::chips($policies, false, '', 9), 'text')));
        $this->assertStringNotContainsString('رزرو تمام‌روز', implode(' | ', array_column($presenter::chips($policies, true), 'text')));
        $this->assertArrayHasKey('سهمیه', $presenter::grouped($presenter::chips($policies, false)));
    }

    public function test_policy_summary_presenter_chips_levels_and_reset()
    {
        $presenter = \App\Livewire\Dashboard\Reservation\Presentation\PolicySummaryPresenter::class;
        $timed = ['window_days' => 14, 'window_hours' => 2, 'allowed_hours' => ['start' => '08:00', 'end' => '18:00'], 'min_duration_minutes' => 30, 'max_duration_minutes' => 120, 'allowed_days' => ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday']];

        $timedText = implode(' | ', array_column($presenter::chips($timed, false), 'text'));
        $this->assertStringContainsString('ساعت مجاز', $timedText);
        $this->assertStringContainsString('مدت مجاز', $timedText);
        $this->assertStringContainsString('همهٔ روزهای هفته', $timedText);

        $fullDayText = implode(' | ', array_column($presenter::chips($timed, true), 'text'));
        $this->assertStringNotContainsString('ساعت مجاز', $fullDayText);
        $this->assertSame([], $presenter::chips([], true));
        $this->assertCount(1, $presenter::chips([], false, 'meeting'));
        $this->assertStringContainsString('همکار', $presenter::chips([], false, 'meeting')[0]['text']);

        $groups = $presenter::grouped($presenter::chips($timed, false, 'meeting'));
        $this->assertSame(['زمان‌بندی', 'مدت', 'سایر'], array_keys($groups));
        $this->assertStringContainsString('ساعت مجاز', implode(' ', array_column($groups['زمان‌بندی'], 'text')));
        $this->assertStringContainsString('مدت مجاز', $groups['مدت'][0]['text']);
        $this->assertSame([], $presenter::grouped([]));

        $this->assertSame('ok', $presenter::quotaLevel(7, 10));
        $this->assertSame('warn', $presenter::quotaLevel(8, 10));
        $this->assertSame('full', $presenter::quotaLevel(10, 10));

        $this->travelTo(\Carbon\Carbon::create(2026, 10, 10, 12, 0, 0));
        $this->assertSame(13, $presenter::quotaReset(now())['days']);
        $this->assertNull($presenter::quotaReset(\Carbon\Carbon::create(2026, 12, 1)));
        $this->travelBack();
    }

    public function test_active_limit_usage_shows_the_user_override_instead_of_the_policy_cap()
    {
        $user = $this->actor();
        $user->update(['booking' => [['key' => 'seat', 'value' => true, 'max' => 2]]]);
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'max_per_user', 7);

        Livewire::actingAs($user->fresh())
            ->test(Main::class)
            ->assertSet('activeLimitUsage.max', 2);
    }

    public function test_active_limit_blocks_new_booking_when_max_reached()
    {
        $user = $this->actor();
        $rA = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $rB = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'max_per_user', 1);

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $rA->id,
            'start_time' => now()->addDay()->setHour(9),
            'end_time' => now()->addDay()->setHour(10),
            'is_full_day' => false,
            'status' => 'active',
            'parent_id' => null,
        ]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('date', now()->addDay()->toDateString())
            ->call('book', $rB->id)
            ->assertDispatched('toast', $this->assertBlockedError(ReservationError::ActiveLimitReached->value));

        $this->assertDatabaseMissing('reservations', ['user_id' => $user->id, 'resource_id' => $rB->id]);
    }

    public function test_cancel_limit_blocks_new_booking_when_reached()
    {
        $user = $this->actor();
        $rA = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $rB = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'max_cancel_count', 1);

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $rA->id,
            'start_time' => now()->subDays(5)->setHour(9),
            'end_time' => now()->subDays(5)->setHour(10),
            'is_full_day' => false,
            'status' => ReservationStatus::CancelledUser->value,
            'cancelled_at' => now()->subDays(5),
            'parent_id' => null,
        ]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('date', now()->addDay()->toDateString())
            ->call('book', $rB->id)
            ->assertDispatched('toast', $this->assertBlockedError(ReservationError::CancelLimitBooking->value));

        $this->assertDatabaseMissing('reservations', ['user_id' => $user->id, 'resource_id' => $rB->id]);
    }

    public function test_double_booking_same_resource_same_day_is_blocked()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('date', now()->addDay()->toDateString())
            ->call('book', $resource->id)
            ->assertDispatched('toast', fn($n, $p) => $p['type'] === 'success')
            ->call('book', $resource->id)
            ->assertDispatched('toast', $this->assertBlockedError(ReservationError::ResourceTaken->value));

        $this->assertSame(1, Reservation::where('user_id', $user->id)->where('resource_id', $resource->id)->count());
    }

    public function test_user_cannot_book_two_resources_same_type_overlapping_time()
    {
        $user = $this->actor();
        $rA = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $rB = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('date', now()->addDay()->toDateString())
            ->call('book', $rA->id)
            ->assertDispatched('toast', fn($n, $p) => $p['type'] === 'success')
            ->call('book', $rB->id)
            ->assertDispatched('toast', $this->assertBlockedError(ReservationError::TimeConflict->value));

        $this->assertDatabaseMissing('reservations', ['user_id' => $user->id, 'resource_id' => $rB->id]);
    }

    public function test_inactive_resource_cannot_be_booked()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'inactive', 'metadata' => []]);
        $this->clearPolicies('seat');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('date', now()->addDay()->toDateString())
            ->call('book', $resource->id)
            ->assertDispatched('toast', $this->assertBlockedError(ReservationError::ResourceInactive->value));

        $this->assertDatabaseMissing('reservations', ['user_id' => $user->id, 'resource_id' => $resource->id]);
    }

    public function test_recurring_booking_generates_occurrences_linked_by_parent_id()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('date', now()->addDay()->toDateString())
            ->set('isRecurring', true)
            ->set('recurPattern', 'daily')
            ->set('recurCount', 3)
            ->call('book', $resource->id)
            ->assertDispatched('toast', fn($n, $p) => $p['type'] === 'success');

        $master = Reservation::where('user_id', $user->id)->where('resource_id', $resource->id)->whereNull('parent_id')->firstOrFail();
        $this->assertSame(3, Reservation::where('user_id', $user->id)->where('resource_id', $resource->id)->count());
        $this->assertSame(2, Reservation::where('parent_id', $master->id)->count());
    }

    public function test_cancel_single_reservation_only_cancels_that_one_when_partial_cancel_enabled()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('date', now()->addDay()->toDateString())
            ->set('isRecurring', true)
            ->set('recurPattern', 'daily')
            ->set('recurCount', 3)
            ->call('book', $resource->id)
            ->assertHasNoErrors();

        $master = Reservation::where('user_id', $user->id)->whereNull('parent_id')->firstOrFail();
        $occurrenceIds = Reservation::where('parent_id', $master->id)->pluck('id')->all();

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('cancel', $master->id)
            ->assertHasNoErrors();

        $this->assertSame(ReservationStatus::CancelledUser->value, $master->refresh()->status);
        $this->assertSame(0, Reservation::whereIn('id', $occurrenceIds)->where('status', ReservationStatus::CancelledUser->value)->count());
    }

    public function test_cancel_recurring_series_cancels_all_occurrences_when_partial_cancel_disabled()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'allow_partial_cancel', false);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('date', now()->addDay()->toDateString())
            ->set('isRecurring', true)
            ->set('recurPattern', 'daily')
            ->set('recurCount', 3)
            ->call('book', $resource->id)
            ->assertHasNoErrors();

        $master = Reservation::where('user_id', $user->id)->whereNull('parent_id')->firstOrFail();

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('cancel', $master->id)
            ->assertHasNoErrors();

        $this->assertSame(3, Reservation::where('user_id', $user->id)->where('status', ReservationStatus::CancelledUser->value)->count());
    }

    public function test_admin_cancel_sets_status_to_cancelled_admin()
    {
        $admin = $this->actor(['role' => 'admin']);
        $owner = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');

        $reservation = Reservation::create([
            'user_id' => $owner->id,
            'resource_id' => $resource->id,
            'start_time' => now()->addDay()->setHour(9),
            'end_time' => now()->addDay()->setHour(10),
            'is_full_day' => false,
            'status' => 'active',
            'parent_id' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(Main::class)
            ->call('cancel', $reservation->id)
            ->assertDispatched('toast', fn($n, $p) => $p['type'] === 'success');

        $this->assertSame(ReservationStatus::CancelledAdmin->value, $reservation->refresh()->status);
        $this->assertSame($admin->id, $reservation->refresh()->cancelled_by_id);
    }

    public function test_non_owner_cannot_cancel_others_reservation()
    {
        $owner = $this->actor();
        $stranger = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');

        $reservation = Reservation::create([
            'user_id' => $owner->id,
            'resource_id' => $resource->id,
            'start_time' => now()->addDay()->setHour(9),
            'end_time' => now()->addDay()->setHour(10),
            'is_full_day' => false,
            'status' => 'active',
            'parent_id' => null,
        ]);

        Livewire::actingAs($stranger)
            ->test(Main::class)
            ->call('cancel', $reservation->id)
            ->assertDispatched('toast', $this->assertBlockedError(ReservationError::CancelForbidden->value));

        $this->assertSame('active', $reservation->refresh()->status);
    }

    public function test_cancelling_already_cancelled_reservation_is_blocked()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');

        $reservation = Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => now()->addDay()->setHour(9),
            'end_time' => now()->addDay()->setHour(10),
            'is_full_day' => false,
            'status' => ReservationStatus::CancelledUser->value,
            'cancelled_at' => now()->subDay(),
            'parent_id' => null,
        ]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('cancel', $reservation->id)
            ->assertDispatched('toast', $this->assertBlockedError(ReservationError::NotActive->value));

        $this->assertSame(ReservationStatus::CancelledUser->value, $reservation->refresh()->status);
    }

    public function test_cancel_limit_reached_blocks_cancellation()
    {
        $user = $this->actor();
        $rA = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $rB = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'max_cancel_count', 1);

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $rA->id,
            'start_time' => now()->subDays(5)->setHour(9),
            'end_time' => now()->subDays(5)->setHour(10),
            'is_full_day' => false,
            'status' => ReservationStatus::CancelledUser->value,
            'cancelled_at' => now()->subDays(5),
            'parent_id' => null,
        ]);

        $active = Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $rB->id,
            'start_time' => now()->addDay()->setHour(9),
            'end_time' => now()->addDay()->setHour(10),
            'is_full_day' => false,
            'status' => 'active',
            'parent_id' => null,
        ]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('cancel', $active->id)
            ->assertDispatched('toast', $this->assertBlockedError(ReservationError::CancelLimitReached->value));

        $this->assertSame('active', $active->refresh()->status);
    }

    public function test_admin_skips_cancel_limit_rule()
    {
        $admin = $this->actor(['role' => 'admin']);
        $owner = $this->actor();
        $rA = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $rB = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'max_cancel_count', 1);

        Reservation::create([
            'user_id' => $admin->id,
            'resource_id' => $rA->id,
            'start_time' => now()->subDays(5)->setHour(9),
            'end_time' => now()->subDays(5)->setHour(10),
            'is_full_day' => false,
            'status' => ReservationStatus::CancelledUser->value,
            'cancelled_at' => now()->subDays(5),
            'parent_id' => null,
        ]);

        $active = Reservation::create([
            'user_id' => $owner->id,
            'resource_id' => $rB->id,
            'start_time' => now()->addDay()->setHour(9),
            'end_time' => now()->addDay()->setHour(10),
            'is_full_day' => false,
            'status' => 'active',
            'parent_id' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(Main::class)
            ->call('cancel', $active->id)
            ->assertDispatched('toast', fn($n, $p) => $p['type'] === 'success');

        $this->assertSame(ReservationStatus::CancelledAdmin->value, $active->refresh()->status);
    }

    public function test_history_upcoming_tab_counts_active_future_reservations()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => now()->addDays(2)->setHour(9),
            'end_time' => now()->addDays(2)->setHour(10),
            'is_full_day' => false,
            'status' => 'active',
            'parent_id' => null,
        ]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('activeHistoryTab', 'upcoming')
            ->assertSet('totalHistoryReservations', 1);
    }

    public function test_history_row_shows_image_thumbnail_with_icon_fallback()
    {
        $user = $this->actor();
        $this->clearPolicies('seat');
        $withImage = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => [], 'image' => 'zz-history-thumb.jpg']);
        $without = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => [], 'image' => null]);

        foreach ([$withImage, $without] as $i => $resource) {
            Reservation::create([
                'user_id' => $user->id,
                'resource_id' => $resource->id,
                'start_time' => now()->addDays(2 + $i)->setHour(9),
                'end_time' => now()->addDays(2 + $i)->setHour(10),
                'is_full_day' => false,
                'status' => 'active',
                'parent_id' => null,
            ]);
        }

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSeeHtml('data-fancybox="history-')
            ->assertSeeHtml('zz-history-thumb.jpg')
            ->assertSeeHtml($without->icon);
    }

    public function test_history_grid_thumbnail_and_classic_mobile_history_button()
    {
        $user = $this->actor();
        $this->clearPolicies('seat');
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => [], 'image' => 'zz-grid-thumb.jpg']);
        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => now()->addDays(2)->setHour(9),
            'end_time' => now()->addDays(2)->setHour(10),
            'is_full_day' => false,
            'status' => 'active',
            'parent_id' => null,
        ]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('view', 'classic')
            ->assertSeeHtml('id="reservation-history"')
            ->set('view', 'avantgarde')
            ->set('canvasTab', 'grid')
            ->assertDontSeeHtml('id="reservation-history"')
            ->assertSeeHtml('zz-grid-thumb.jpg');
    }

    public function test_booking_buttons_in_both_views_ask_for_confirmation_with_a_summary_before_booking()
    {
        $user = $this->actor();
        $this->clearPolicies('seat');
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => [], 'name' => 'zz-confirm-seat']);

        $classic = Livewire::actingAs($user)->test(Main::class)->set('view', 'classic')->set('activeTab', 'seat')->set('resourceSearch', 'zz-confirm-seat');
        $classic->assertSeeHtml('open-confirmation')->assertSeeHtml('zz-confirm-seat');
        $this->assertStringNotContainsString("wire:click=\"book({$resource->id})\"", $classic->html());
        $this->assertStringContainsString('تمام روز', $this->summaryOf($classic->instance(), $resource));
        $this->assertStringNotContainsString('<x-ui.buttons.submit', $classic->html());
        $this->assertStringContainsString('تأیید رزرو', $classic->html());
        $this->assertStringContainsString('منبع: zz-confirm-seat', $classic->html());
        $this->assertStringContainsString('تاریخ', $classic->html());

        Livewire::withoutLazyLoading();
        $avant =Livewire::actingAs($user)->test(Main::class)->set('view', 'avantgarde')->set('activeTab', 'seat')->set('resourceSearch', 'zz-confirm-seat')->set('open', $resource->id);
        $avant->assertSeeHtml('open-confirmation');
        $this->assertStringNotContainsString('<x-ui.buttons.submit', $avant->html());
        $this->assertStringNotContainsString("wire:click=\"book({$resource->id})\"", $avant->html());

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Main::class)->set('activeTab', 'seat')->call('book', $resource->id);
        $this->assertSame(1, Reservation::where('user_id', $user->id)->where('resource_id', $resource->id)->count());
    }

    public function test_picking_a_start_with_no_valid_end_slot_is_refused_with_a_warning()
    {
        $this->clearPolicies('seat');
        $user = $this->actor();
        $this->clearPolicies('meeting');

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)
            ->test(Main::class)
            ->call('switchTab', 'meeting')
            ->set('date', now()->addDay()->toDateString());

        $slots = $component->get('availableTimeSlots');
        $first = $slots[0];
        $last = end($slots);

        $component->set('startTime', $first)
            ->set('endTime', $last)
            ->call('setStartTime', $last)
            ->assertSet('startTime', $first)
            ->assertSet('endTime', $last)
            ->assertDispatched('toast', fn($n, $p) => $p['type'] === 'warning');
    }

    public function test_booking_and_switching_tab_with_an_open_resource_never_toast_the_unavailable_message()
    {
        $user = $this->actor();
        $this->clearPolicies('seat');
        $this->clearPolicies('car');
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $notice = fn($n, $p) => ($p['message'] ?? null) === 'این مورد با انتخاب جدید شما در دسترس نیست';

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('view', 'avantgarde')
            ->set('activeTab', 'seat')
            ->set('open', $resource->id)
            ->call('book', $resource->id)
            ->assertDispatched('toast', fn($n, $p) => ($p['type'] ?? null) === 'success')
            ->assertSet('open', null)
            ->assertNotDispatched('toast', $notice);

        Livewire::withoutLazyLoading();
        $other = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('view', 'avantgarde')
            ->set('activeTab', 'seat')
            ->set('open', $other->id)
            ->call('switchTab', 'car')
            ->assertSet('activeTab', 'car')
            ->assertSet('open', null)
            ->assertNotDispatched('toast', $notice);
    }

    public function test_classic_end_slot_buttons_carry_the_render_date_and_the_start_guard_but_start_buttons_do_not()
    {
        $this->clearPolicies('seat');
        $user = $this->actor();
        $this->clearPolicies('meeting');
        $date = now()->addDay()->toDateString();

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)
            ->test(Main::class)
            ->set('view', 'classic')
            ->call('switchTab', 'meeting')
            ->set('date', $date);

        $slot = $component->get('availableTimeSlots')[1];
        $html = html_entity_decode($component->html());

        preg_match('/wire:key="end-time-' . $slot . '".*?<\/button>/s', $html, $end);
        preg_match('/wire:key="start-time-' . $slot . '".*?<\/button>/s', $html, $start);

        $this->assertNotEmpty($end);
        $this->assertNotEmpty($start);
        $this->assertStringContainsString("'{$date}', '", $end[0]);
        $this->assertStringContainsString("|| ('{$slot}' <= start)", $end[0]);
        $this->assertStringContainsString("'{$date}', '", $start[0]);
        $this->assertStringNotContainsString('<= start', $start[0]);
    }

    public function test_booking_summary_for_an_hourly_type_lists_the_hours_and_the_repeat_line()
    {
        $this->clearPolicies('seat');
        $user = $this->actor();
        $this->clearPolicies('meeting');
        $this->setPolicy('meeting', 'allow_repeat', true);
        $this->setPolicy('meeting', 'window_days', 8);

        $date = now()->addDay()->toDateString();

        Livewire::withoutLazyLoading();
        $instance = Livewire::actingAs($user)
            ->test(Main::class)
            ->call('switchTab', 'meeting')
            ->set('date', $date)
            ->set('startTime', '09:00')
            ->set('endTime', '10:00')
            ->set('isRecurring', true)
            ->set('recurPattern', 'weekly')
            ->set('recurCount', 3)
            ->instance();

        $summary = $this->summaryOf($instance);
        $ok = collect($instance->recurPreview)->where('ok', true)->count();

        $this->assertLessThan(3, $ok);
        $this->assertGreaterThan(0, $ok);
        $this->assertStringContainsString(convertToPersian('تاریخ: ' . toJalali(Carbon::parse($date), 'l، j F Y')), $summary);
        $this->assertStringContainsString(convertToPersian('ساعت: 09:00 تا 10:00'), $summary);
        $this->assertStringContainsString(convertToPersian("تکرار: هفتگی، تا {$ok} بار"), $summary);
    }

    public function test_booking_summary_counts_only_occurrences_inside_the_window_for_an_elevated_role()
    {
        $this->clearPolicies('seat');
        $admin = $this->actor(['role' => 'admin']);
        $this->clearPolicies('meeting');
        $this->setPolicy('meeting', 'allow_repeat', true);
        $this->setPolicy('meeting', 'window_days', 8);

        Livewire::withoutLazyLoading();
        $instance = Livewire::actingAs($admin)
            ->test(Main::class)
            ->call('switchTab', 'meeting')
            ->set('date', now()->addDay()->toDateString())
            ->set('startTime', '09:00')
            ->set('endTime', '10:00')
            ->set('isRecurring', true)
            ->set('recurPattern', 'weekly')
            ->set('recurCount', 3)
            ->instance();

        $ok = collect($instance->recurPreview)->where('ok', true)->count();

        $this->assertSame(2, $ok);
        $this->assertStringContainsString(convertToPersian("تکرار: هفتگی، تا {$ok} بار"), $this->summaryOf($instance));
    }

    public function test_booking_summary_says_only_this_once_when_a_single_occurrence_is_inside_the_window()
    {
        $this->clearPolicies('seat');
        $user = $this->actor();
        $this->clearPolicies('meeting');
        $this->setPolicy('meeting', 'allow_repeat', true);
        $this->setPolicy('meeting', 'window_days', 3);

        Livewire::withoutLazyLoading();
        $instance = Livewire::actingAs($user)
            ->test(Main::class)
            ->call('switchTab', 'meeting')
            ->set('date', now()->addDay()->toDateString())
            ->set('startTime', '09:00')
            ->set('endTime', '10:00')
            ->set('isRecurring', true)
            ->set('recurPattern', 'weekly')
            ->set('recurCount', 3)
            ->instance();
        $summary = $this->summaryOf($instance);

        $this->assertStringContainsString('فقط همین یک‌بار', $summary);
        $this->assertStringNotContainsString('تکرار: هفتگی', $summary);
    }

    public function test_booking_summary_elevated_role_ignores_allowed_days_but_a_regular_user_does_not()
    {
        $this->clearPolicies('seat');
        $this->clearPolicies('meeting');
        $this->setPolicy('meeting', 'allow_repeat', true);
        $blocked = strtolower(now()->addDays(2)->englishDayOfWeek);
        $this->setPolicy('meeting', 'allowed_days', array_values(array_diff(['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'], [$blocked])));

        $instanceFor = function (User $who) {
            Livewire::withoutLazyLoading();

            return Livewire::actingAs($who)
                ->test(Main::class)
                ->call('switchTab', 'meeting')
                ->set('date', now()->addDay()->toDateString())
                ->set('startTime', '09:00')
                ->set('endTime', '10:00')
                ->set('isRecurring', true)
                ->set('recurPattern', 'daily')
                ->set('recurCount', 3)
                ->instance();
        };

        $adminUser = $this->actor(['role' => 'admin']);
        $regularUser = $this->actor();

        $admin = $instanceFor($adminUser);
        $this->actingAs($adminUser);
        $adminSummary = $this->summaryOf($admin);
        $adminSecond = $admin->recurPreview[1]['ok'];

        $regular = $instanceFor($regularUser);
        $this->actingAs($regularUser);
        $regularSummary = $this->summaryOf($regular);
        $regularSecond = $regular->recurPreview[1]['ok'];

        $this->assertStringContainsString(convertToPersian('تکرار: روزانه، تا 3 بار'), $adminSummary);
        $this->assertStringContainsString(convertToPersian('تکرار: روزانه، تا 2 بار'), $regularSummary);
        $this->assertTrue($adminSecond);
        $this->assertFalse($regularSecond);
    }

    public function test_booking_summary_repeat_count_follows_each_resources_own_available_days()
    {
        $user = $this->actor();
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'allow_repeat', true);
        $tomorrow = now()->addDay();
        $narrow = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'name' => 'zz-sum-narrow', 'metadata' => ['available_days' => [strtolower($tomorrow->englishDayOfWeek)]]]);
        $wide = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'name' => 'zz-sum-wide', 'metadata' => []]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)
            ->test(Main::class)
            ->set('view', 'classic')
            ->set('activeTab', 'seat')
            ->set('resourceSearch', 'zz-sum-')
            ->set('date', $tomorrow->toDateString())
            ->set('isRecurring', true)
            ->set('recurPattern', 'daily')
            ->set('recurCount', 3);
        $instance = $component->instance();

        $this->assertStringContainsString('فقط همین یک‌بار', $this->summaryOf($instance, $narrow));
        $this->assertStringContainsString(convertToPersian('تکرار: روزانه، تا 3 بار'), $this->summaryOf($instance, $wide));

        $html = html_entity_decode($component->html());
        $this->assertStringContainsString('فقط همین یک‌بار', $html);
        $this->assertStringContainsString(convertToPersian('تکرار: روزانه، تا 3 بار'), $html);

        Livewire::withoutLazyLoading();
        $admin = Livewire::actingAs($this->actor(['role' => 'admin']))
            ->test(Main::class)
            ->set('activeTab', 'seat')
            ->set('date', $tomorrow->toDateString())
            ->set('isRecurring', true)
            ->set('recurPattern', 'daily')
            ->set('recurCount', 3)
            ->instance();

        $this->assertStringContainsString(convertToPersian('تکرار: روزانه، تا 3 بار'), $this->summaryOf($admin, $narrow));
    }

    public function test_booking_summary_repeat_count_is_capped_by_the_remaining_monthly_quota_in_the_selected_month()
    {
        $this->pinToMidJalaliMonth();
        $user = $this->actor();
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'allow_repeat', true);
        $this->setPolicy('seat', 'max_per_user', 2);
        $held = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $target = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $day = now()->addDays(5)->toDateString();

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $held->id,
            'start_time' => "{$day} 00:00:00",
            'end_time' => "{$day} 23:59:59",
            'is_full_day' => true,
            'status' => ReservationStatus::Active->value,
        ]);

        Livewire::withoutLazyLoading();
        $instance = Livewire::actingAs($user)
            ->test(Main::class)
            ->set('activeTab', 'seat')
            ->set('date', now()->addDay()->toDateString())
            ->set('isRecurring', true)
            ->set('recurPattern', 'daily')
            ->set('recurCount', 4)
            ->instance();

        $this->assertStringContainsString('فقط همین یک‌بار', $this->summaryOf($instance, $target));
    }

    public function test_meeting_summary_adds_the_invite_line_only_when_a_different_related_user_exists()
    {
        $this->clearPolicies('seat');
        $user = $this->actor(['name' => 'zz Booker Person']);
        $colleague = $this->actor(['name' => 'zz Colleague Person']);
        $this->clearPolicies('meeting');
        $withRelated = Resource::factory()->create(['type' => 'meeting', 'status' => 'active', 'name' => $colleague->name, 'metadata' => []]);
        $self = Resource::factory()->create(['type' => 'meeting', 'status' => 'active', 'name' => $user->name, 'metadata' => []]);
        $orphan = Resource::factory()->create(['type' => 'meeting', 'status' => 'active', 'name' => 'zz Nobody Room', 'metadata' => []]);
        $line = 'اطلاع‌رسانی: دعوتنامه در تقویم همکار ثبت می‌شود';

        Livewire::withoutLazyLoading();
        $instance = Livewire::actingAs($user)
            ->test(Main::class)
            ->call('switchTab', 'meeting')
            ->set('resourceSearch', 'zz ')
            ->set('date', now()->addDay()->toDateString())
            ->instance();

        $listed = $instance->resources->keyBy('id');

        $this->assertStringContainsString($line, $this->summaryOf($instance, $listed[$withRelated->id]));
        $this->assertStringNotContainsString($line, $this->summaryOf($instance, $listed[$self->id]));
        $this->assertStringNotContainsString($line, $this->summaryOf($instance, $listed[$orphan->id]));
    }

    public function test_seat_summary_never_adds_the_invite_line()
    {
        $user = $this->actor();
        $this->clearPolicies('seat');
        $seat = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);

        Livewire::withoutLazyLoading();
        $instance = Livewire::actingAs($user)->test(Main::class)->set('activeTab', 'seat')->instance();

        $this->assertStringNotContainsString('اطلاع‌رسانی', $this->summaryOf($instance, $seat));
    }

    public function test_clearing_filters_keeps_valid_picked_hours_but_reseats_invalid_ones_and_tab_switches_reseat()
    {
        $this->pinToMidJalaliMonth();
        $user = $this->actor();
        $this->clearPolicies('meeting');
        $this->clearPolicies('seat');
        $tomorrow = now()->addDay()->toDateString();

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)
            ->test(Main::class)
            ->call('switchTab', 'meeting')
            ->set('date', $tomorrow)
            ->set('startTime', '14:00')
            ->set('endTime', '15:00')
            ->set('resourceSearch', 'zz-no-such-room')
            ->call('resetFilters', true)
            ->assertSet('resourceSearch', '')
            ->assertSet('startTime', '14:00')
            ->assertSet('endTime', '15:00');

        $component->set('startTime', '23:45')
            ->set('endTime', '23:50')
            ->call('resetFilters', true);

        $this->assertContains($component->get('startTime'), $component->get('availableTimeSlots'));
        $this->assertContains($component->get('endTime'), $component->get('availableTimeSlots'));

        $component->set('startTime', '14:00')
            ->set('endTime', '15:00')
            ->call('switchTab', 'seat');

        $this->assertNotSame('14:00', $component->get('startTime'));
    }

    public function test_booking_summary_has_no_repeat_line_when_the_policy_forbids_repeating()
    {
        $this->clearPolicies('seat');
        $user = $this->actor();
        $this->clearPolicies('meeting');
        $this->setPolicy('meeting', 'allow_repeat', false);

        Livewire::withoutLazyLoading();
        $instance = Livewire::actingAs($user)
            ->test(Main::class)
            ->call('switchTab', 'meeting')
            ->set('date', now()->addDay()->toDateString())
            ->set('startTime', '09:00')
            ->set('endTime', '10:00')
            ->set('isRecurring', true)
            ->instance();

        $this->assertStringNotContainsString('تکرار:', $this->summaryOf($instance));
    }

    public function test_slot_states_apply_the_notice_window_on_future_dates_too()
    {
        $this->clearPolicies('seat');
        $this->pinToMidJalaliMonth();
        $user = $this->actor();
        $this->clearPolicies('meeting');
        $this->setPolicy('meeting', 'window_hours', 24);
        $date = now()->addDay()->toDateString();

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)
            ->test(Main::class)
            ->set('view', 'classic')
            ->call('switchTab', 'meeting')
            ->set('date', $date);

        $slots = $component->get('availableTimeSlots');
        $cutoff = now()->addHours(24);
        $expectedFirst = collect($slots)->first(fn($s) => Carbon::parse("{$date} {$s}")->gte($cutoff));
        $meta = $component->instance()->startSlotMeta;

        foreach ($slots as $s) {
            $expected = Carbon::parse("{$date} {$s}")->lt($cutoff) ? 'soon' : 'ok';
            $this->assertSame($expected, $meta['states'][$s]);
        }

        $this->assertSame($expectedFirst, $meta['first']);
        $this->assertSame('soon', $meta['states'][$slots[0]]);

        $html = html_entity_decode($component->html());
        preg_match('/wire:key="start-time-' . $slots[0] . '".*?<\/button>/s', $html, $chunk);
        $this->assertNotEmpty($chunk);
        $wall = now()->format('Y-m-d\TH:i:s');
        $this->assertStringContainsString("'{$slots[0]}', 24, 'soon', '{$date}', '{$wall}')", $chunk[0]);
    }

    public function test_mount_with_a_rejected_open_id_lists_resources_for_the_fresh_default_times()
    {
        $this->clearPolicies('seat');
        $this->pinToMidJalaliMonth();
        $user = $this->actor();
        $other = $this->actor();
        $this->clearPolicies('meeting');
        $resource = Resource::factory()->create(['type' => 'meeting', 'status' => 'active', 'name' => 'zz-stale-list-room', 'metadata' => []]);
        $today = now()->toDateString();

        Resource::where('type', 'meeting')->pluck('id')->each(fn ($id) => Reservation::create([
            'user_id' => $other->id,
            'resource_id' => $id,
            'start_time' => "{$today} 09:00:00",
            'end_time' => "{$today} 10:00:00",
            'is_full_day' => false,
            'status' => ReservationStatus::Active->value,
        ]));

        Livewire::withoutLazyLoading();
        $component = Livewire::withQueryParams(['tab' => 'meeting', 'open' => 999999999])
            ->actingAs($user)
            ->test(Main::class);

        $component->assertSet('open', null)->assertSet('deckOnly', false);
        $fresh = Resource::available('meeting', Carbon::parse("{$today} 10:00"), Carbon::parse("{$today} 10:30"))->pluck('id');

        $this->assertTrue($fresh->contains($resource->id));
        $component->assertSet('startTime', '10:00')->assertSet('endTime', '10:30');
        $this->assertSame($fresh->count(), $component->get('totalResources'));
        $this->assertSame(
            Resource::available('meeting', Carbon::parse("{$today} 10:00"), Carbon::parse("{$today} 10:30"))->limit(6)->pluck('id')->sort()->values()->all(),
            $component->instance()->resources->pluck('id')->sort()->values()->all()
        );
    }

    public function test_a_bogus_recur_pattern_written_by_the_client_is_reset_and_the_page_still_renders()
    {
        $this->clearPolicies('seat');
        $user = $this->actor();

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Main::class)
            ->set('isRecurring', true)
            ->set('recurPattern', 'bogus')
            ->assertSet('recurPattern', 'daily')
            ->assertOk()
            ->set('recurPattern', 'weekly')
            ->assertSet('recurPattern', 'weekly');
    }

    public function test_deep_link_to_a_resource_restricted_to_todays_weekday_late_in_the_evening_lands_on_the_next_allowed_date()
    {
        $this->pinToMidJalaliMonth();
        Carbon::setTestNow(now()->setTime(23, 50));
        $user = $this->actor();
        $this->clearPolicies('seat');
        $this->clearPolicies('meeting');
        $weekday = strtolower(now()->englishDayOfWeek);
        $resource = Resource::factory()->create(['type' => 'meeting', 'status' => 'active', 'metadata' => ['available_days' => [$weekday]]]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Main::class)
            ->call('focusRecord', $resource->id);

        $component->assertSet('open', $resource->id)
            ->assertSet('date', now()->addWeek()->toDateString());
        $this->assertContains($component->get('startTime'), $component->get('availableTimeSlots'));
        $this->assertLessThan($component->get('endTime'), $component->get('startTime'));
    }

    public function test_mount_rolls_an_hourly_type_to_the_next_day_when_today_has_no_slot_left()
    {
        $this->clearPolicies('seat');
        $this->pinToMidJalaliMonth();
        Carbon::setTestNow(now()->setTime(23, 50));
        $user = $this->actor();
        $this->clearPolicies('meeting');
        $tomorrow = now()->addDay()->toDateString();

        Livewire::withoutLazyLoading();
        $component = Livewire::withQueryParams(['tab' => 'meeting'])->actingAs($user)->test(Main::class);

        $component->assertSet('date', $tomorrow);
        $this->assertContains($component->get('startTime'), $component->get('availableTimeSlots'));
        $this->assertLessThan($component->get('endTime'), $component->get('startTime'));
    }

    public function test_mount_stays_on_today_when_an_hourly_slot_is_still_available()
    {
        $this->clearPolicies('seat');
        $this->pinToMidJalaliMonth();
        $user = $this->actor();
        $this->clearPolicies('meeting');
        $month = Jalalian::now()->getMonth();

        Livewire::withoutLazyLoading();
        Livewire::withQueryParams(['tab' => 'meeting'])->actingAs($user)->test(Main::class)
            ->assertSet('date', now()->toDateString())
            ->assertSet('currentMonth', $month);
    }

    public function test_mount_roll_into_the_next_jalali_month_moves_the_month_cursor_with_it()
    {
        $this->clearPolicies('seat');
        $now = Jalalian::now();
        Carbon::setTestNow((new Jalalian($now->getYear(), $now->getMonth(), $now->getMonthDays()))->toCarbon()->setTime(23, 50));
        $user = $this->actor();
        $this->clearPolicies('meeting');
        $tomorrow = Jalalian::fromCarbon(now()->addDay());

        Livewire::withoutLazyLoading();
        $component = Livewire::withQueryParams(['tab' => 'meeting'])->actingAs($user)->test(Main::class);

        $component->assertSet('date', now()->addDay()->toDateString())
            ->assertSet('currentMonth', $tomorrow->getMonth())
            ->assertSet('currentYear', $tomorrow->getYear())
            ->assertSet('canPrevMonth', true);
    }

    public function test_both_filter_clear_controls_pass_true_so_picked_hours_survive()
    {
        foreach (['reservation.blade.php', 'reservation/booking-panel.blade.php'] as $file) {
            $source = file_get_contents(resource_path('views/livewire/dashboard/' . $file));

            $this->assertStringContainsString("this.\$wire.call('resetFilters', true)", $source, $file);
            $this->assertStringNotContainsString("call('resetFilters')", $source, $file);
        }
    }

    public function test_mount_rolls_to_the_first_date_that_clears_the_notice_window()
    {
        $this->clearPolicies('seat');
        $this->pinToMidJalaliMonth();
        Carbon::setTestNow(now()->setTime(14, 31));
        $user = $this->actor();
        $this->clearPolicies('meeting');
        $this->setPolicy('meeting', 'window_hours', 24);

        Livewire::withoutLazyLoading();
        $component = Livewire::withQueryParams(['tab' => 'meeting'])->actingAs($user)->test(Main::class);

        $component->assertSet('date', now()->addDay()->toDateString());
        $this->assertTrue(Carbon::parse($component->get('date') . ' ' . $component->get('startTime'))->gte(now()->addHours(24)));
    }

    public function test_switching_to_an_hourly_tab_late_in_the_evening_rolls_to_the_next_bookable_day()
    {
        $this->pinToMidJalaliMonth();
        Carbon::setTestNow(now()->setTime(23, 50));
        $user = $this->actor();
        $this->clearPolicies('seat');
        $this->clearPolicies('meeting');
        $this->clearPolicies('car');
        $tomorrow = now()->addDay();

        Livewire::withoutLazyLoading();
        $component = Livewire::withQueryParams(['tab' => 'seat'])->actingAs($user)->test(Main::class)
            ->assertSet('date', now()->toDateString())
            ->call('switchTab', 'meeting');

        $component->assertSet('date', $tomorrow->toDateString())
            ->assertSet('currentMonth', Jalalian::fromCarbon($tomorrow)->getMonth());
        $this->assertContains($component->get('startTime'), $component->get('availableTimeSlots'));
        $this->assertContains($component->get('endTime'), $component->get('availableTimeSlots'));
        $this->assertLessThan($component->get('endTime'), $component->get('startTime'));

        Livewire::withoutLazyLoading();
        Livewire::withQueryParams(['tab' => 'seat'])->actingAs($user)->test(Main::class)
            ->call('switchTab', 'car')
            ->assertSet('date', now()->toDateString());
    }

    public function test_switching_to_an_hourly_tab_during_the_day_keeps_today()
    {
        $this->pinToMidJalaliMonth();
        $user = $this->actor();
        $this->clearPolicies('seat');
        $this->clearPolicies('meeting');

        Livewire::withoutLazyLoading();
        Livewire::withQueryParams(['tab' => 'seat'])->actingAs($user)->test(Main::class)
            ->call('switchTab', 'meeting')
            ->assertSet('date', now()->toDateString());
    }

    public function test_client_style_active_tab_write_late_in_the_evening_rolls_and_during_the_day_stays()
    {
        $this->pinToMidJalaliMonth();
        $user = $this->actor();
        $this->clearPolicies('seat');
        $this->clearPolicies('meeting');

        Livewire::withoutLazyLoading();
        Livewire::withQueryParams(['tab' => 'seat'])->actingAs($user)->test(Main::class)
            ->set('activeTab', 'meeting')
            ->assertSet('date', now()->toDateString());

        Carbon::setTestNow(now()->setTime(23, 50));
        $tomorrow = now()->addDay()->toDateString();

        Livewire::withoutLazyLoading();
        $component = Livewire::withQueryParams(['tab' => 'seat'])->actingAs($user)->test(Main::class)
            ->set('activeTab', 'meeting');

        $component->assertSet('date', $tomorrow);
        $this->assertContains($component->get('startTime'), $component->get('availableTimeSlots'));
        $this->assertLessThan($component->get('endTime'), $component->get('startTime'));
    }

    public function test_keep_times_clear_reseats_when_the_date_changes_or_the_times_are_reversed()
    {
        $this->pinToMidJalaliMonth();
        $user = $this->actor();
        $this->clearPolicies('seat');
        $this->clearPolicies('meeting');
        $later = now()->addDays(3)->toDateString();

        Livewire::withoutLazyLoading();
        $component = Livewire::withQueryParams(['tab' => 'meeting'])->actingAs($user)->test(Main::class)
            ->set('date', $later)
            ->set('startTime', '14:00')
            ->set('endTime', '15:00')
            ->call('resetFilters', true)
            ->assertSet('startTime', '14:00')
            ->assertSet('endTime', '15:00');

        $component->set('date', now()->addDays(80)->toDateString())
            ->set('startTime', '14:00')
            ->set('endTime', '15:00')
            ->call('resetFilters', true)
            ->assertSet('date', now()->toDateString())
            ->assertSet('startTime', '10:00');

        $component->set('date', $later)
            ->set('startTime', '15:00')
            ->set('endTime', '14:00')
            ->call('resetFilters', true)
            ->assertSet('startTime', '08:00');
    }

    public function test_keep_times_clear_reseats_a_start_that_is_inside_the_notice_window()
    {
        $this->pinToMidJalaliMonth();
        $user = $this->actor();
        $this->clearPolicies('seat');
        $this->clearPolicies('meeting');
        $this->setPolicy('meeting', 'window_hours', 24);

        Livewire::withoutLazyLoading();
        Livewire::withQueryParams(['tab' => 'meeting'])->actingAs($user)->test(Main::class)
            ->set('date', now()->addDay()->toDateString())
            ->set('startTime', '09:00')
            ->set('endTime', '10:00')
            ->call('resetFilters', true)
            ->assertSet('startTime', '10:00');
    }

    public function test_booking_summary_counts_a_last_minute_relaxed_occurrence_against_the_remaining_quota()
    {
        $this->pinToMidJalaliMonth();
        $user = $this->actor();
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'allow_repeat', true);
        $this->setPolicy('seat', 'max_per_user', 3);
        $this->setPolicy('seat', 'last_minute_hours', 30);
        $held = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $target = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $day = now()->addDays(5)->toDateString();

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $held->id,
            'start_time' => "{$day} 00:00:00",
            'end_time' => "{$day} 23:59:59",
            'is_full_day' => true,
            'status' => ReservationStatus::Active->value,
        ]);

        Livewire::withoutLazyLoading();
        $instance = Livewire::actingAs($user)
            ->test(Main::class)
            ->set('activeTab', 'seat')
            ->set('date', now()->addDay()->toDateString())
            ->set('isRecurring', true)
            ->set('recurPattern', 'daily')
            ->set('recurCount', 3)
            ->instance();

        $this->assertStringContainsString(convertToPersian('تکرار: روزانه، تا 2 بار'), $this->summaryOf($instance, $target));
    }

    public function test_booking_summary_counts_every_occurrence_when_all_are_last_minute_even_with_no_quota_left()
    {
        $this->pinToMidJalaliMonth();
        $user = $this->actor();
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'allow_repeat', true);
        $this->setPolicy('seat', 'max_per_user', 1);
        $held = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $target = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $day = now()->addDays(5)->toDateString();

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $held->id,
            'start_time' => "{$day} 00:00:00",
            'end_time' => "{$day} 23:59:59",
            'is_full_day' => true,
            'status' => ReservationStatus::Active->value,
        ]);

        $summaryWith = function (int $hours) use ($user, $target) {
            $this->setPolicy('seat', 'last_minute_hours', $hours);
            Livewire::withoutLazyLoading();

            return $this->summaryOf(
                Livewire::actingAs($user)->test(Main::class)
                    ->set('activeTab', 'seat')
                    ->set('date', now()->addDay()->toDateString())
                    ->set('isRecurring', true)
                    ->set('recurPattern', 'daily')
                    ->set('recurCount', 3)
                    ->instance(),
                $target
            );
        };

        $this->assertStringContainsString(convertToPersian('تکرار: روزانه، تا 3 بار'), $summaryWith(72));
        $this->assertStringContainsString('فقط همین یک‌بار', $summaryWith(30));
    }

    public function test_deep_link_to_a_resource_of_another_hourly_type_late_in_the_evening_rolls_to_the_next_bookable_day()
    {
        $this->pinToMidJalaliMonth();
        $user = $this->actor();
        $this->clearPolicies('seat');
        $this->clearPolicies('meeting');
        $resource = Resource::factory()->create(['type' => 'meeting', 'status' => 'active', 'metadata' => []]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Main::class)
            ->call('focusRecord', $resource->id)
            ->assertSet('activeTab', 'meeting')
            ->assertSet('open', $resource->id)
            ->assertSet('date', now()->toDateString());

        Carbon::setTestNow(now()->setTime(23, 50));
        $tomorrow = now()->addDay()->toDateString();

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Main::class)
            ->call('focusRecord', $resource->id);

        $component->assertSet('activeTab', 'meeting')
            ->assertSet('open', $resource->id)
            ->assertSet('date', $tomorrow);
        $this->assertContains($component->get('startTime'), $component->get('availableTimeSlots'));
        $this->assertLessThan($component->get('endTime'), $component->get('startTime'));
    }

    public function test_mount_keeps_today_for_a_full_day_type_even_late_in_the_evening()
    {
        $this->pinToMidJalaliMonth();
        Carbon::setTestNow(now()->setTime(23, 50));
        $user = $this->actor();
        $this->clearPolicies('seat');

        Livewire::withoutLazyLoading();
        Livewire::withQueryParams(['tab' => 'seat'])->actingAs($user)->test(Main::class)
            ->assertSet('date', now()->toDateString());
    }

    public function test_default_end_time_falls_back_to_thirty_minutes_when_no_slot_satisfies_the_min_duration()
    {
        $this->clearPolicies('seat');
        $this->pinToMidJalaliMonth();
        Carbon::setTestNow(now()->setTime(18, 15));
        $user = $this->actor();
        $this->clearPolicies('meeting');
        $this->setPolicy('meeting', 'min_duration_minutes', 120);
        $this->setPolicy('meeting', 'allowed_hours', ['start' => '08:00', 'end' => '20:00']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)
            ->test(Main::class)
            ->call('switchTab', 'meeting');

        $component->assertSet('startTime', '18:30')
            ->assertSet('endTime', '19:00');
    }

    public function test_default_end_time_respects_the_min_duration_policy()
    {
        $this->clearPolicies('seat');
        $this->pinToMidJalaliMonth();
        $user = $this->actor();
        $this->clearPolicies('meeting');
        $this->setPolicy('meeting', 'min_duration_minutes', 90);
        $toMinutes = fn(string $s) => (int) substr($s, 0, 2) * 60 + (int) substr($s, 3, 2);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)
            ->test(Main::class)
            ->call('switchTab', 'meeting');

        $start = $component->get('startTime');
        $expected = collect($component->get('availableTimeSlots'))->first(fn($s) => $toMinutes($s) - $toMinutes($start) >= 90);

        $this->assertNotNull($expected);
        $component->assertSet('endTime', $expected);
        $this->assertNull($component->instance()->bookingBlockReason);
    }

    public function test_set_end_time_is_accepted_when_the_server_start_is_not_an_available_slot()
    {
        $this->clearPolicies('seat');
        $this->pinToMidJalaliMonth();
        Carbon::setTestNow(now()->setTime(19, 45));
        $user = $this->actor();
        $this->clearPolicies('meeting');
        $resource = Resource::factory()->create(['type' => 'meeting', 'status' => 'active', 'metadata' => ['time_slots' => ['start' => '10:00', 'end' => '18:00']]]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)
            ->test(Main::class)
            ->call('switchTab', 'meeting')
            ->set('startTime', '19:00')
            ->set('open', $resource->id)
            ->assertSet('startTime', '19:00');

        $this->assertNotContains('19:00', $component->get('availableTimeSlots'));

        $component->call('setEndTime', '12:00')
            ->assertSet('endTime', '12:00')
            ->assertNotDispatched('toast', fn($n, $p) => ($p['type'] ?? null) === 'warning');
    }

    public function test_last_valid_start_slot_follows_the_min_duration_and_the_last_start_button_renders_its_guard()
    {
        $this->clearPolicies('seat');
        $user = $this->actor();
        $this->clearPolicies('meeting');
        $this->setPolicy('meeting', 'min_duration_minutes', 60);
        $toMinutes = fn(string $s) => (int) substr($s, 0, 2) * 60 + (int) substr($s, 3, 2);
        $expectedFor = fn(array $slots, int $gap) => collect($slots)->last(fn($s) => collect($slots)->contains(fn($e) => $toMinutes($e) - $toMinutes($s) >= $gap));

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)
            ->test(Main::class)
            ->set('view', 'classic')
            ->call('switchTab', 'meeting')
            ->set('date', now()->addDay()->toDateString());

        $slots = $component->get('availableTimeSlots');
        $lastStart = $expectedFor($slots, 60);
        $component->assertSet('lastValidStartSlot', $lastStart);

        $html = html_entity_decode($component->html());
        $lastSlot = end($slots);
        preg_match('/wire:key="start-time-' . $lastSlot . '".*?<\/button>/s', $html, $chunk);
        $this->assertNotEmpty($chunk);
        $this->assertStringContainsString("'{$lastSlot}' > '{$lastStart}'", $chunk[0]);

        $this->setPolicy('meeting', 'min_duration_minutes', 120);
        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('switchTab', 'meeting')
            ->set('date', now()->addDay()->toDateString())
            ->assertSet('lastValidStartSlot', $expectedFor($slots, 120));
    }

    public function test_booking_block_reason_mirrors_the_server_notice_check_down_to_the_second()
    {
        $this->clearPolicies('seat');
        $this->pinToMidJalaliMonth();
        Carbon::setTestNow(now()->setTime(10, 0, 30));
        $user = $this->actor();
        $this->clearPolicies('meeting');
        $this->setPolicy('meeting', 'window_hours', 1);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)
            ->test(Main::class)
            ->call('switchTab', 'meeting')
            ->set('date', now()->toDateString())
            ->set('startTime', '11:00')
            ->set('endTime', '12:00');

        $this->assertNotNull($component->instance()->bookingBlockReason);

        $component->set('startTime', '11:30')->set('endTime', '12:30');
        $this->assertNull($component->instance()->bookingBlockReason);
    }

    public function test_history_previous_tab_counts_past_active_reservations()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => now()->subDays(2)->setHour(9),
            'end_time' => now()->subDays(2)->setHour(10),
            'is_full_day' => false,
            'status' => 'active',
            'parent_id' => null,
        ]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('switchTab', 'previous')
            ->assertSet('activeHistoryTab', 'previous')
            ->assertSet('totalHistoryReservations', 1);
    }

    public function test_history_cancelled_tab_counts_cancelled_reservations()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => now()->subDays(2)->setHour(9),
            'end_time' => now()->subDays(2)->setHour(10),
            'is_full_day' => false,
            'status' => ReservationStatus::CancelledUser->value,
            'cancelled_at' => now()->subDay(),
            'parent_id' => null,
        ]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('switchTab', 'cancelled')
            ->assertSet('activeHistoryTab', 'cancelled')
            ->assertSet('totalHistoryReservations', 1);
    }

    public function test_history_cancelled_tab_exposes_reason_and_canceller()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');
        $admin = User::factory()->create(['status' => 'active']);

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => now()->subDays(2)->setHour(9),
            'end_time' => now()->subDays(2)->setHour(10),
            'is_full_day' => false,
            'status' => ReservationStatus::CancelledAdmin->value,
            'cancelled_by_id' => $admin->id,
            'cancel_reason' => CancelReason::ScheduleChange->value,
            'cancelled_at' => now()->subHours(12),
            'parent_id' => null,
        ]);

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => now()->subDays(3)->setHour(9),
            'end_time' => now()->subDays(3)->setHour(10),
            'is_full_day' => false,
            'status' => ReservationStatus::CancelledUser->value,
            'cancelled_by_id' => $user->id,
            'cancel_reason' => 'تداخل جلسه',
            'cancelled_at' => now()->subDay(),
            'parent_id' => null,
        ]);

        $items = Livewire::actingAs($user)
            ->test(Main::class)
            ->call('switchTab', 'cancelled')
            ->get('historyReservations');

        $this->assertSame('تغییر برنامه زمانی', $items[0]->cancelReasonLabel());
        $this->assertSame($admin->name, $items[0]->cancelledBy->name);
        $this->assertSame('تداخل جلسه', $items[1]->cancelReasonLabel());
        $this->assertSame($user->id, $items[1]->cancelledBy->id);
    }

    public function test_history_released_tab_counts_released_reservations()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => now()->subDays(2)->setHour(9),
            'end_time' => now()->subDays(2)->setHour(10),
            'is_full_day' => false,
            'status' => ReservationStatus::Released->value,
            'parent_id' => null,
        ]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('switchTab', 'released')
            ->assertSet('activeHistoryTab', 'released')
            ->assertSet('totalHistoryReservations', 1);
    }

    public function test_total_history_reservations_counts_distinct_series()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('date', now()->addDay()->toDateString())
            ->set('isRecurring', true)
            ->set('recurPattern', 'daily')
            ->set('recurCount', 3)
            ->call('book', $resource->id)
            ->assertHasNoErrors();

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('totalHistoryReservations', 1);
    }

    public function test_history_query_applies_hard_ceiling_before_series_folding()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => now()->subDays(2)->setHour(9),
            'end_time' => now()->subDays(2)->setHour(10),
            'is_full_day' => false,
            'status' => ReservationStatus::CancelledUser->value,
            'cancelled_at' => now()->subDay(),
            'parent_id' => null,
        ]);

        $captured = [];
        DB::listen(function ($query) use (&$captured) {
            if (str_starts_with($query->sql, 'select * from `reservations`') && str_contains($query->sql, 'cancelled_at')) {
                $captured[] = $query->sql;
            }
        });

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('switchTab', 'cancelled');

        $this->assertNotEmpty($captured);
        $this->assertStringContainsString('limit 500', $captured[0]);
    }

    public function test_load_more_history_increases_history_limit()
    {
        $user = $this->actor();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('historyLimit', 5)
            ->call('loadMoreHistory')
            ->assertSet('historyLimit', 10);
    }

    public function test_load_more_resources_increases_resources_limit()
    {
        $user = $this->actor();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('resourcesLimit', 6)
            ->call('loadMoreResources')
            ->assertSet('resourcesLimit', 12);
    }

    public function test_set_date_updates_date_property()
    {
        $user = $this->actor();
        $component = Livewire::actingAs($user)->test(Main::class);
        $dates = $component->get('availableDates');
        $target = end($dates)['value'];

        $component->call('setDate', $target)->assertSet('date', $target);
    }

    public function test_malformed_client_written_date_and_times_are_reset_instead_of_crashing()
    {
        $user = $this->actor();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('date', 'garbage')
            ->assertSet('date', now()->toDateString())
            ->set('startTime', 'xx')
            ->assertSet('startTime', '09:00')
            ->set('endTime', '')
            ->assertSet('endTime', '10:00');
    }

    public function test_set_date_ignores_dates_outside_the_available_strip()
    {
        $user = $this->actor();
        $component = Livewire::actingAs($user)->test(Main::class);
        $before = $component->get('date');

        $component->call('setDate', '2000-01-01')->assertSet('date', $before);
    }

    public function test_set_facet_toggles_facet_filter()
    {
        $user = $this->actor();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('setFacet', 'floor', '2')
            ->assertSet('facetFilters', ['floor' => '2'])
            ->call('setFacet', 'floor', '2')
            ->assertSet('facetFilters', []);
    }

    public function test_set_start_time_updates_property()
    {
        $user = $this->actor();
        $this->clearPolicies('seat');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('endTime', '15:00')
            ->call('setStartTime', '14:00')
            ->assertSet('startTime', '14:00');
    }

    public function test_set_end_time_updates_property()
    {
        $user = $this->actor();
        $this->clearPolicies('seat');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('startTime', '09:00')
            ->call('setEndTime', '15:00')
            ->assertSet('endTime', '15:00');
    }

    public function test_picking_a_later_start_moves_the_end_to_the_next_valid_slot_and_toasts()
    {
        $this->clearPolicies('seat');
        $user = $this->actor();
        $this->clearPolicies('meeting');
        $this->setPolicy('meeting', 'min_duration_minutes', 60);
        $toMinutes = fn(string $s) => (int) substr($s, 0, 2) * 60 + (int) substr($s, 3, 2);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)
            ->test(Main::class)
            ->call('switchTab', 'meeting')
            ->set('date', now()->addDay()->toDateString())
            ->set('startTime', '09:00')
            ->set('endTime', '10:00');

        $expected = collect($component->get('availableTimeSlots'))
            ->first(fn($s) => $toMinutes($s) - $toMinutes('11:00') >= 60);

        $component->call('setStartTime', '11:00')
            ->assertSet('startTime', '11:00')
            ->assertSet('endTime', $expected)
            ->assertDispatched('toast', fn($n, $p) => $p['type'] === 'info');
    }

    public function test_picking_an_end_at_or_before_the_start_is_refused_with_a_warning()
    {
        $this->clearPolicies('seat');
        $user = $this->actor();
        $this->clearPolicies('meeting');

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('switchTab', 'meeting')
            ->set('date', now()->addDay()->toDateString())
            ->set('startTime', '11:00')
            ->set('endTime', '12:00')
            ->call('setEndTime', '11:00')
            ->assertSet('endTime', '12:00')
            ->assertDispatched('toast', fn($n, $p) => $p['type'] === 'warning')
            ->call('setEndTime', '10:00')
            ->assertSet('endTime', '12:00');
    }

    public function test_go_today_toasts_instead_of_moving_when_today_is_not_selectable()
    {
        $user = $this->actor();
        $today = strtolower(now()->englishDayOfWeek);
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'allowed_days', [$today === 'monday' ? 'tuesday' : 'monday']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Main::class);
        $before = $component->get('date');

        $component->call('goToday')
            ->assertSet('date', $before)
            ->assertDispatched('toast', fn($n, $p) => $p['type'] === 'info')
            ->assertNotDispatched('scroll-to-selected');
    }

    public function test_open_resource_dropping_out_after_a_filter_change_toasts_and_clears_focus()
    {
        $user = $this->actor();
        $this->clearPolicies('seat');
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => ['floor' => '1']]);
        Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => ['floor' => '2']]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('focusDeck', $resource->id)
            ->call('setFacet', 'floor', '2')
            ->assertSet('open', null)
            ->assertDispatched('toast', fn($n, $p) => $p['type'] === 'info');
    }

    public function test_focus_record_switches_tab_and_widens_limit()
    {
        $user = $this->actor();
        $car = Resource::factory()->create(['type' => 'car', 'status' => 'active', 'metadata' => []]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('activeTab', 'seat')
            ->call('focusRecord', $car->id)
            ->assertSet('activeTab', 'car')
            ->assertSet('facetFilters', [])
            ->assertSet('resourcesLimit', 50);
    }

    public function test_focus_record_ignores_locked_type()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'car', 'status' => 'active', 'metadata' => []]);
        $this->disableType('car');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('activeTab', 'seat')
            ->call('focusRecord', $resource->id)
            ->assertSet('activeTab', 'seat')
            ->assertSet('resourcesLimit', 6);
    }

    public function test_focus_record_ignores_unpermitted_type()
    {
        $user = $this->actor(['booking' => ['all' => false, 'seat' => true, 'car' => false]]);
        $resource = Resource::factory()->create(['type' => 'car', 'status' => 'active', 'metadata' => []]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('activeTab', 'seat')
            ->call('focusRecord', $resource->id)
            ->assertSet('activeTab', 'seat')
            ->assertSet('resourcesLimit', 6);
    }

    public function test_focus_deck_sets_open_and_deck_only_flag()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('focusDeck', $resource->id)
            ->assertSet('open', $resource->id)
            ->assertSet('deckOnly', true);
    }

    public function test_open_resource_clears_when_it_drops_from_filtered_list()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => ['floor' => '1']]);
        Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => ['floor' => '2']]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('focusDeck', $resource->id)
            ->assertSet('open', $resource->id)
            ->call('setFacet', 'floor', '2')
            ->assertSet('open', null)
            ->assertSet('deckOnly', false);
    }

    public function test_clear_focus_resets_deck_only_flag()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('focusDeck', $resource->id)
            ->assertSet('deckOnly', true)
            ->call('clearFocus')
            ->assertSet('open', null)
            ->assertSet('deckOnly', false);
    }

    public function test_deck_focus_suppresses_search_chip_but_focus_record_shows_it()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('focusDeck', $resource->id)
            ->assertDontSee('نتیجهٔ جستجو');

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('open', $resource->id)
            ->assertSee('نتیجهٔ جستجو');
    }

    public function test_go_today_sets_date_to_today()
    {
        $user = $this->actor();
        $this->clearPolicies('seat');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('date', now()->addDays(5)->toDateString())
            ->call('goToday')
            ->assertSet('date', now()->toDateString());
    }

    public function test_can_prev_month_is_false_at_current_jalali_month()
    {
        $user = $this->actor();
        $now = Jalalian::now();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('currentYear', $now->getYear())
            ->assertSet('currentMonth', $now->getMonth())
            ->assertSet('canPrevMonth', false);
    }

    public function test_date_window_defaults_to_the_twenty_one_day_strip_when_window_days_null()
    {
        $user = $this->actor();
        $this->clearPolicies('seat');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('dateWindow', 20);
    }

    public function test_can_next_month_true_when_window_days_covers_next_month()
    {
        $user = $this->actor();
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'window_days', 90);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('canNextMonth', true);
    }

    public function test_next_month_increments_month_cursor()
    {
        $user = $this->actor();
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'window_days', 90);
        $now = Jalalian::now();
        $expectedMonth = $now->getMonth() % 12 + 1;
        $expectedYear = $now->getMonth() === 12 ? $now->getYear() + 1 : $now->getYear();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('nextMonth')
            ->assertSet('currentMonth', $expectedMonth)
            ->assertSet('currentYear', $expectedYear);
    }

    public function test_prev_month_does_nothing_when_can_prev_month_false()
    {
        $user = $this->actor();
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'window_days', 90);
        $now = Jalalian::now();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('prevMonth')
            ->assertSet('currentMonth', $now->getMonth())
            ->assertSet('currentYear', $now->getYear());
    }

    public function test_next_arrow_is_disabled_immediately_after_entering_last_allowed_month()
    {
        $this->pinToMidJalaliMonth();
        $user = $this->actor();
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'window_days', 30);

        $component = Livewire::actingAs($user)->test(Main::class)->set('view', 'avantgarde');
        $this->assertDoesNotMatchRegularExpression('/wire:click="nextMonth"\s+disabled/', $component->html());

        $component->call('nextMonth');
        $this->assertMatchesRegularExpression('/wire:click="nextMonth"\s+disabled/', $component->html());
        $this->assertDoesNotMatchRegularExpression('/wire:click="prevMonth"\s+disabled/', $component->html());
    }

    public function test_prev_arrow_is_disabled_immediately_after_returning_to_the_current_month()
    {
        $this->pinToMidJalaliMonth();
        $user = $this->actor();
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'window_days', 30);

        $component = Livewire::actingAs($user)->test(Main::class)->set('view', 'avantgarde')->call('nextMonth');
        $this->assertDoesNotMatchRegularExpression('/wire:click="prevMonth"\s+disabled/', $component->html());

        $component->call('prevMonth');
        $this->assertMatchesRegularExpression('/wire:click="prevMonth"\s+disabled/', $component->html());
    }

    public function test_time_rail_key_changes_when_busy_segments_change()
    {
        $this->clearPolicies('seat');
        $this->pinToMidJalaliMonth();
        $user = $this->actor();
        $other = $this->actor();
        $this->clearPolicies('meeting');
        $resource = Resource::factory()->create(['type' => 'meeting', 'status' => 'active', 'metadata' => ['time_slots' => ['start' => '08:00', 'end' => '18:00']]]);
        $date = now()->addDay()->toDateString();

        $component = Livewire::actingAs($user)->test(Main::class)
            ->set('view', 'avantgarde')
            ->set('activeTab', 'meeting')
            ->set('date', $date)
            ->set('open', $resource->id);

        preg_match('/reservation-avantgarde-time-rail-[^"]*/', $component->html(), $before);

        Reservation::create([
            'user_id' => $other->id,
            'resource_id' => $resource->id,
            'start_time' => "{$date} 14:00:00",
            'end_time' => "{$date} 15:00:00",
            'is_full_day' => false,
            'status' => ReservationStatus::Active->value,
        ]);

        $component->call('$refresh');
        preg_match('/reservation-avantgarde-time-rail-[^"]*/', $component->html(), $after);

        $this->assertNotEmpty($before);
        $this->assertNotEmpty($after);
        $this->assertNotSame($before[0], $after[0]);
    }

    public function test_dossier_non_empty_branch_root_is_keyed()
    {
        $user = $this->actor();
        $this->clearPolicies('seat');
        Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => [], 'name' => 'zz-dossier-seat']);

        Livewire::actingAs($user)->test(Main::class)
            ->set('view', 'avantgarde')
            ->set('activeTab', 'seat')
            ->set('resourceSearch', 'zz-dossier-seat')
            ->assertSeeHtml('wire:key="reservation-dossier-branch"');
    }

    public function test_focus_deck_loads_the_resources_list_once_per_open()
    {
        $this->clearPolicies('seat');
        $this->pinToMidJalaliMonth();
        $user = $this->actor();
        $this->clearPolicies('meeting');
        $resource = Resource::factory()->create(['type' => 'meeting', 'status' => 'active', 'metadata' => ['time_slots' => ['start' => '08:00', 'end' => '18:00']]]);

        $component = Livewire::actingAs($user)->test(Main::class)
            ->set('view', 'avantgarde')
            ->set('activeTab', 'meeting')
            ->set('date', now()->addDay()->toDateString())
            ->set('startTime', '09:00')
            ->set('endTime', '10:00');

        $count = 0;
        DB::listen(function ($query) use (&$count) {
            if (str_contains($query->sql, 'from `resources`') && str_contains($query->sql, 'limit 50')) {
                $count++;
            }
        });

        $component->call('focusDeck', $resource->id);

        $this->assertSame(1, $count);
    }

    public function test_fully_booked_day_marked_booked_in_calendar_cells()
    {
        $this->pinToMidJalaliMonth();
        $user = $this->actor();
        $this->clearPolicies('car');
        $rA = Resource::factory()->create(['type' => 'car', 'status' => 'active', 'metadata' => []]);
        $rB = Resource::factory()->create(['type' => 'car', 'status' => 'active', 'metadata' => []]);
        $date = now()->addDay()->toDateString();

        foreach ([$rA, $rB] as $resource) {
            Reservation::create([
                'user_id' => $user->id,
                'resource_id' => $resource->id,
                'start_time' => "{$date} 00:00:00",
                'end_time' => "{$date} 23:59:59",
                'is_full_day' => true,
                'status' => ReservationStatus::Active->value,
            ]);
        }

        $instance = Livewire::actingAs($user)->test(Main::class)
            ->set('view', 'avantgarde')
            ->set('activeTab', 'car')
            ->assertSet('fullyBookedDates', fn ($dates) => in_array($date, $dates, true))
            ->instance();

        $cell = collect(AvantgardePresenter::calendarCells(
            $instance->currentYear,
            $instance->currentMonth,
            $instance->availableDates,
            $instance->date,
            $instance->allowedDays,
            $instance->fullyBookedDates,
        ))->filter()->firstWhere('value', $date);

        $this->assertSame('booked', $cell['reason']);
        $this->assertFalse($cell['available']);
        $this->assertArrayHasKey('booked', AvantgardePresenter::blockedDayLabels());
    }

    public function test_partially_booked_day_is_not_marked_booked()
    {
        $this->pinToMidJalaliMonth();
        $user = $this->actor();
        $this->clearPolicies('car');
        $rA = Resource::factory()->create(['type' => 'car', 'status' => 'active', 'metadata' => []]);
        Resource::factory()->create(['type' => 'car', 'status' => 'active', 'metadata' => []]);
        $date = now()->addDay()->toDateString();

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $rA->id,
            'start_time' => "{$date} 00:00:00",
            'end_time' => "{$date} 23:59:59",
            'is_full_day' => true,
            'status' => ReservationStatus::Active->value,
        ]);

        $instance = Livewire::actingAs($user)->test(Main::class)
            ->set('view', 'avantgarde')
            ->set('activeTab', 'car')
            ->assertSet('fullyBookedDates', fn ($dates) => ! in_array($date, $dates, true))
            ->assertSet('dayFreeCounts', fn ($counts) => $counts[$date] === 1 && $counts[now()->addDays(2)->toDateString()] === 2)
            ->instance();

        $cell = collect(AvantgardePresenter::calendarCells(
            $instance->currentYear,
            $instance->currentMonth,
            $instance->availableDates,
            $instance->date,
            $instance->allowedDays,
            $instance->fullyBookedDates,
        ))->filter()->firstWhere('value', $date);

        $this->assertNull($cell['reason']);
        $this->assertTrue($cell['available']);
    }

    public function test_multi_day_hold_marks_every_day_it_spans_as_booked()
    {
        $this->pinToMidJalaliMonth();
        $user = $this->actor();
        $this->clearPolicies('car');
        $resource = Resource::factory()->create(['type' => 'car', 'status' => 'active', 'metadata' => []]);
        Resource::where('type', 'car')->whereKeyNot($resource->id)->update(['status' => 'inactive']);
        $first = now()->addDay()->toDateString();
        $last = now()->addDays(3)->toDateString();

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => "{$first} 00:00:00",
            'end_time' => "{$last} 23:59:59",
            'is_full_day' => true,
            'status' => ReservationStatus::Active->value,
        ]);

        Livewire::actingAs($user)->test(Main::class)
            ->set('activeTab', 'car')
            ->assertSet('dayFreeCounts', fn ($counts) => $counts[$first] === 0 && $counts[now()->addDays(2)->toDateString()] === 0 && $counts[$last] === 0);
    }

    public function test_timed_type_day_is_booked_only_when_resource_hours_are_fully_covered()
    {
        $this->pinToMidJalaliMonth();
        $user = $this->actor();
        $this->clearPolicies('meeting');
        $resource = Resource::factory()->create(['type' => 'meeting', 'status' => 'active', 'metadata' => ['time_slots' => ['start' => '11:00', 'end' => '12:00']]]);
        Resource::where('type', 'meeting')->whereKeyNot($resource->id)->update(['status' => 'inactive']);
        $full = now()->addDay()->toDateString();
        $partial = now()->addDays(2)->toDateString();

        foreach ([[$full, '11:00:00', '12:00:00'], [$partial, '11:00:00', '11:30:00']] as [$date, $start, $end]) {
            Reservation::create([
                'user_id' => $user->id,
                'resource_id' => $resource->id,
                'start_time' => "{$date} {$start}",
                'end_time' => "{$date} {$end}",
                'is_full_day' => false,
                'status' => ReservationStatus::Active->value,
            ]);
        }

        Livewire::actingAs($user)->test(Main::class)
            ->set('view', 'avantgarde')
            ->set('activeTab', 'meeting')
            ->assertSet('dayFreeCounts', fn ($counts) => $counts[$full] === 0 && $counts[$partial] === 1)
            ->assertSet('fullyBookedDates', fn ($dates) => in_array($full, $dates, true) && ! in_array($partial, $dates, true));
    }

    public function test_calendar_cells_distinguish_past_and_window_reasons()
    {
        $todayValue = now()->toDateString();
        $pastYear = (int) Jalalian::now()->getYear() - 2;
        $futureYear = (int) Jalalian::now()->getYear() + 5;

        $pastCells = collect(AvantgardePresenter::calendarCells($pastYear, 1, [], $todayValue))->filter();
        $futureCells = collect(AvantgardePresenter::calendarCells($futureYear, 1, [], $todayValue))->filter();

        $this->assertSame('past', $pastCells->first()['reason']);
        $this->assertSame('window', $futureCells->first()['reason']);
    }

    public function test_reset_filters_clears_facet_filters_and_resets_resources_limit()
    {
        $user = $this->actor();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('setFacet', 'floor', '2')
            ->call('loadMoreResources')
            ->assertSet('resourcesLimit', 12)
            ->call('resetFilters')
            ->assertSet('facetFilters', [])
            ->assertSet('resourcesLimit', 6);
    }

    public function test_facets_lists_unique_floor_and_unit_values_for_active_tab()
    {
        $user = $this->actor();
        $floor = (string) random_int(1000, 9999);
        Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => ['floor' => $floor, 'unit' => '10']]);
        Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => ['floor' => (string) ((int) $floor + 1), 'unit' => '20']]);

        $facets = collect(Livewire::actingAs($user)->test(Main::class)->get('facets'))->keyBy('key');

        $this->assertTrue(collect($facets['floor']['options'])->contains('value', $floor));
        $this->assertTrue($facets->has('unit'));
    }

    public function test_set_facet_narrows_available_resources()
    {
        $user = $this->actor();
        $matching = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => ['unit' => '7']]);
        $other = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => ['unit' => '8']]);

        $component = Livewire::actingAs($user)->test(Main::class)->call('setFacet', 'unit', '7');

        $ids = $component->get('resources')->pluck('id');

        $this->assertTrue($ids->contains($matching->id));
        $this->assertFalse($ids->contains($other->id));
    }

    public function test_resource_search_filters_resources_by_name()
    {
        $user = $this->actor();
        $matching = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'name' => 'میز شماره ۹۹', 'metadata' => []]);
        $other = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'name' => 'میز شماره ۱۲', 'metadata' => []]);

        $component = Livewire::actingAs($user)->test(Main::class)->set('resourceSearch', '۹۹');

        $ids = $component->get('resources')->pluck('id');

        $this->assertTrue($ids->contains($matching->id));
        $this->assertFalse($ids->contains($other->id));
    }

    public function test_reset_filters_clears_resource_search()
    {
        $user = $this->actor();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('resourceSearch', 'foo')
            ->call('resetFilters')
            ->assertSet('resourceSearch', '');
    }

    public function test_available_time_slots_uses_policy_allowed_hours()
    {
        $user = $this->actor();
        $this->clearPolicies('meeting');
        $this->setPolicy('meeting', 'allowed_hours', ['start' => '10:00', 'end' => '12:00']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('switchTab', 'meeting')
            ->assertSet('availableTimeSlots', ['10:00', '10:30', '11:00', '11:30', '12:00']);
    }

    public function test_resource_hours_narrow_policy_hours_and_govern_alone_when_policy_is_unset()
    {
        $this->assertSame(['11:00', '11:30', '12:00'], TimeSlotPresenter::slots(['start' => '06:00', 'end' => '19:00'], ['start' => '11:00', 'end' => '12:00']));
        $this->assertSame(['06:00', '06:30', '07:00'], TimeSlotPresenter::slots(null, ['start' => '06:00', 'end' => '07:00']));
        $this->assertSame(['10:00', '10:30', '11:00'], TimeSlotPresenter::slots(['start' => '10:00', 'end' => '11:00'], ['start' => '06:00', 'end' => '20:00']));
    }

    public function test_active_limit_usage_near_flag_true_when_count_reaches_max()
    {
        $this->pinToMidJalaliMonth();
        $user = $this->actor();
        $rA = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $rB = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'max_per_user', 2);

        $day = now()->addDay();
        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $rA->id,
            'start_time' => $day->copy()->setHour(9),
            'end_time' => $day->copy()->setHour(10),
            'is_full_day' => false,
            'status' => 'active',
            'parent_id' => null,
        ]);
        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $rB->id,
            'start_time' => $day->copy()->setHour(11),
            'end_time' => $day->copy()->setHour(12),
            'is_full_day' => false,
            'status' => 'active',
            'parent_id' => null,
        ]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('activeLimitUsage', fn($u) => $u['count'] === 2 && $u['max'] === 2 && $u['near'] === true);
    }

    public function test_cancel_limit_usage_blocked_flag_true_when_count_reaches_limit()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'max_cancel_count', 1);

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => now()->subDays(5)->setHour(9),
            'end_time' => now()->subDays(5)->setHour(10),
            'is_full_day' => false,
            'status' => ReservationStatus::CancelledUser->value,
            'cancelled_at' => now()->subDays(5),
            'parent_id' => null,
        ]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('cancelLimitUsage', fn($u) => $u['count'] === 1 && $u['max'] === 1 && $u['blocked'] === true);
    }

    public function test_recur_preview_returns_empty_when_recurring_disabled()
    {
        $user = $this->actor();
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'allow_repeat', false);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('isRecurring', true)
            ->assertSet('recurPreview', []);
    }

    public function test_selected_duration_detects_invalid_range()
    {
        $user = $this->actor();
        $this->clearPolicies('meeting');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('switchTab', 'meeting')
            ->set('date', now()->addDay()->toDateString())
            ->set('startTime', '10:00')
            ->set('endTime', '09:00')
            ->assertSet('selectedDuration', fn($d) => $d['minutes'] <= 0 && $d['valid'] === false);
    }

    public function test_booking_block_reason_surfaces_active_limit_near()
    {
        $this->pinToMidJalaliMonth();
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $this->clearPolicies('seat');
        $this->setPolicy('seat', 'max_per_user', 1);

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => now()->addDay()->setHour(9),
            'end_time' => now()->addDay()->setHour(10),
            'is_full_day' => false,
            'status' => 'active',
            'parent_id' => null,
        ]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('bookingBlockReason', fn($r) => is_string($r) && str_contains($r, 'سقف'));
    }

    public function test_status_legend_renders_all_four_reservation_statuses(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSee('راهنمای وضعیت رزرو')
            ->assertSee('فعال')
            ->assertSee('آزادشده')
            ->assertSee('لغو توسط کاربر')
            ->assertSee('لغو توسط ادمین');
    }

    public function test_client_set_view_is_clamped_to_classic_when_the_view_is_off_or_unknown()
    {
        $component = Livewire::actingAs($this->actor())->test(Main::class);

        try {
            config(['app.off' => ['reservation:avantgarde']]);
            $component->set('view', 'avantgarde')->assertSet('view', 'classic');

            config(['app.off' => []]);
            $component->set('view', 'avantgarde')->assertSet('view', 'avantgarde')
                ->set('view', 'bogus')->assertSet('view', 'classic');
        } finally {
            config(['app.off' => []]);
        }
    }

    public function test_avantgarde_booking_panel_includes_recurring_toggle()
    {
        $user = $this->actor();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('view', 'avantgarde')
            ->assertSee('رزرو تکرارشونده');
    }

    public function test_toggle_view_sets_view_and_persists_session()
    {
        $user = $this->actor();

        $component = Livewire::actingAs($user)->test(Main::class)
            ->call('toggleView', 'avantgarde');

        $this->assertSame('avantgarde', $component->instance()->view);
        $this->assertSame('avantgarde', session('reservation_view_mode'));
    }

    public function test_toggle_view_ignores_invalid_value()
    {
        $user = $this->actor();

        $component = Livewire::actingAs($user)->test(Main::class)
            ->call('toggleView', 'bogus');

        $this->assertSame('classic', $component->instance()->view);
        $this->assertNull(session('reservation_view_mode'));
    }

    public function test_mount_restores_avantgarde_view_from_session()
    {
        $user = $this->actor();
        session(['reservation_view_mode' => 'avantgarde']);

        $component = Livewire::actingAs($user)->test(Main::class);

        $this->assertSame('avantgarde', $component->instance()->view);
    }

    public function test_view_survives_switch_tab_and_reset_filters()
    {
        $user = $this->actor();
        Resource::factory()->create(['type' => 'car', 'status' => 'active', 'metadata' => []]);

        Livewire::actingAs($user)->test(Main::class)
            ->call('toggleView', 'avantgarde')
            ->call('switchTab', 'car')
            ->assertSet('view', 'avantgarde')
            ->call('resetFilters')
            ->assertSet('view', 'avantgarde');
    }

    public function test_open_resource_busy_segments_empty_when_no_open()
    {
        $user = $this->actor();

        Livewire::actingAs($user)->test(Main::class)
            ->assertSet('openResourceBusySegments', []);
    }

    public function test_open_resource_busy_segments_returns_segments_for_opened_resource_on_selected_date()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $other = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $date = now()->addDay()->toDateString();

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => "{$date} 09:00:00",
            'end_time' => "{$date} 10:00:00",
            'is_full_day' => false,
            'status' => 'active',
            'parent_id' => null,
        ]);

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $other->id,
            'start_time' => "{$date} 11:00:00",
            'end_time' => "{$date} 12:00:00",
            'is_full_day' => false,
            'status' => 'active',
            'parent_id' => null,
        ]);

        Livewire::actingAs($user)->test(Main::class)
            ->set('date', $date)
            ->set('open', $resource->id)
            ->assertSet('openResourceBusySegments', fn ($segments) => count($segments) === 1
                && $segments[0]['start'] === '09:00'
                && $segments[0]['end'] === '10:00');
    }

    public function test_grid_search_filters_history_reservations_by_resource_name()
    {
        $user = $this->actor();
        $matching = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => [], 'name' => 'Alpha Desk']);
        $other = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => [], 'name' => 'Beta Desk']);

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $matching->id,
            'start_time' => now()->addDay()->setHour(9),
            'end_time' => now()->addDay()->setHour(10),
            'is_full_day' => false,
            'status' => 'active',
            'parent_id' => null,
        ]);

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $other->id,
            'start_time' => now()->addDay()->setHour(11),
            'end_time' => now()->addDay()->setHour(12),
            'is_full_day' => false,
            'status' => 'active',
            'parent_id' => null,
        ]);

        Livewire::actingAs($user)->test(Main::class)
            ->set('view', 'avantgarde')
            ->set('gridSearch', 'Alpha')
            ->assertSet('historyReservations', fn ($list) => $list->count() === 1 && $list->first()->resource->name === 'Alpha Desk')
            ->assertSet('totalHistoryReservations', 1);
    }

    public function test_grid_search_only_applies_in_avantgarde_view()
    {
        $user = $this->actor();
        $matching = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => [], 'name' => 'Alpha Desk']);
        $other = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => [], 'name' => 'Beta Desk']);

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $matching->id,
            'start_time' => now()->addDay()->setHour(9),
            'end_time' => now()->addDay()->setHour(10),
            'is_full_day' => false,
            'status' => 'active',
            'parent_id' => null,
        ]);

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $other->id,
            'start_time' => now()->addDay()->setHour(11),
            'end_time' => now()->addDay()->setHour(12),
            'is_full_day' => false,
            'status' => 'active',
            'parent_id' => null,
        ]);

        Livewire::actingAs($user)->test(Main::class)
            ->set('gridSearch', 'Alpha')
            ->assertSet('view', 'classic')
            ->assertSet('historyReservations', fn ($list) => $list->count() === 2)
            ->assertSet('totalHistoryReservations', 2);
    }

    public function test_grid_search_empty_by_default_does_not_filter_classic_history()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => now()->addDay()->setHour(9),
            'end_time' => now()->addDay()->setHour(10),
            'is_full_day' => false,
            'status' => 'active',
            'parent_id' => null,
        ]);

        Livewire::actingAs($user)->test(Main::class)
            ->assertSet('gridSearch', '')
            ->assertSet('historyReservations', fn ($list) => $list->count() === 1)
            ->assertSet('totalHistoryReservations', 1);
    }

    public function test_grid_search_update_resets_history_limit()
    {
        $user = $this->actor();

        Livewire::actingAs($user)->test(Main::class)
            ->call('loadMoreHistory')
            ->assertSet('historyLimit', 10)
            ->set('gridSearch', 'x')
            ->assertSet('historyLimit', 5);
    }

    public function test_show_all_history_returns_every_status_with_correct_bucket()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);

        $upcoming = Reservation::create([
            'user_id' => $user->id, 'resource_id' => $resource->id,
            'start_time' => now()->addDay()->setHour(9), 'end_time' => now()->addDay()->setHour(10),
            'is_full_day' => false, 'status' => 'active', 'parent_id' => null,
        ]);
        $previous = Reservation::create([
            'user_id' => $user->id, 'resource_id' => $resource->id,
            'start_time' => now()->subDays(2)->setHour(9), 'end_time' => now()->subDays(2)->setHour(10),
            'is_full_day' => false, 'status' => 'active', 'parent_id' => null,
        ]);
        $cancelled = Reservation::create([
            'user_id' => $user->id, 'resource_id' => $resource->id,
            'start_time' => now()->addDays(3)->setHour(9), 'end_time' => now()->addDays(3)->setHour(10),
            'is_full_day' => false, 'status' => 'cancelled_user', 'parent_id' => null,
        ]);
        $released = Reservation::create([
            'user_id' => $user->id, 'resource_id' => $resource->id,
            'start_time' => now()->addDays(4)->setHour(9), 'end_time' => now()->addDays(4)->setHour(10),
            'is_full_day' => false, 'status' => 'released', 'parent_id' => null,
        ]);

        Livewire::actingAs($user)->test(Main::class)
            ->set('view', 'avantgarde')
            ->assertSet('historyShowAll', true)
            ->assertSet('historyReservations', function ($list) use ($upcoming, $previous, $cancelled, $released) {
                $buckets = $list->keyBy('id')->map->history_bucket;

                return $list->count() === 4
                    && $buckets[$upcoming->id] === 'upcoming'
                    && $buckets[$previous->id] === 'previous'
                    && $buckets[$cancelled->id] === 'cancelled'
                    && $buckets[$released->id] === 'released';
            })
            ->assertSet('totalHistoryReservations', 4);
    }

    public function test_switch_tab_to_a_status_disables_show_all()
    {
        $user = $this->actor();

        Livewire::actingAs($user)->test(Main::class)
            ->set('view', 'avantgarde')
            ->assertSet('historyShowAll', true)
            ->call('switchTab', 'cancelled')
            ->assertSet('historyShowAll', false)
            ->assertSet('activeHistoryTab', 'cancelled');
    }

    public function test_show_all_history_reactivates_all_filter()
    {
        $user = $this->actor();

        Livewire::actingAs($user)->test(Main::class)
            ->set('view', 'avantgarde')
            ->call('switchTab', 'cancelled')
            ->assertSet('historyShowAll', false)
            ->call('showAllHistory')
            ->assertSet('historyShowAll', true);
    }

    public function test_reservation_command_grid_renders_column_visibility_markers(): void
    {
        $user = $this->actor();

        $html = Livewire::actingAs($user)->test(Main::class)
            ->set('view', 'avantgarde')
            ->call('switchTab', 'grid')
            ->assertSee('reservation-command-table', false)
            ->assertSee('data-col="resource"', false)
            ->assertSee('data-col="time"', false)
            ->assertSee('data-col="status"', false)
            ->assertSee('نمایش همه')
            ->assertSee('toggleHeaderDeclutter()', false)
            ->assertSee('toggleMaximize()', false)
            ->assertSee("'max-widget': max || maxLeaving", false)
            ->assertSee('$store.density.toggle()', false)
            ->assertSee('title="فیلتر بازه تاریخ"', false)
            ->assertSee('data-declutter-hide', false)
            ->assertSee('title="مرتب‌سازی"', false)
            ->assertSee("sortBy('time')", false)
            ->html();

        $this->assertMatchesRegularExpression('/<th(?![^>]*data-col)[^>]*>.*?view_column.*?<\/th>/s', $html);
        $this->assertStringNotContainsString('<span>اقدام</span>', $html);
    }

    public function test_grid_sort_orders_by_time_and_resource_keeps_pins_first_and_resets(): void
    {
        $user = $this->actor();
        $zulu = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => [], 'name' => 'Zulu']);
        $alpha = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => [], 'name' => 'Alpha']);

        $make = fn(Resource $resource, int $day) => Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => now()->addDays($day)->setHour(9),
            'end_time' => now()->addDays($day)->setHour(10),
            'is_full_day' => false,
            'status' => 'active',
            'parent_id' => null,
        ])->id;

        $zuluLate = $make($zulu, 3);
        $alphaSoon = $make($alpha, 1);

        $orderOf = function (string $html): array {
            preg_match_all('/data-rf="reservation-(\d+)"/', $html, $matches);
            return array_map('intval', $matches[1]);
        };

        $component = Livewire::actingAs($user)->test(Main::class)
            ->set('view', 'avantgarde')
            ->call('switchTab', 'grid');

        $this->assertSame([$zuluLate, $alphaSoon], $orderOf($component->html()));

        $component->call('sortBy', 'resource');
        $this->assertSame([$alphaSoon, $zuluLate], $orderOf($component->html()));

        $component->call('sortBy', 'resource');
        $this->assertSame([$zuluLate, $alphaSoon], $orderOf($component->html()));

        $component->call('sortBy', 'password')->assertSet('gridSort', 'resource');

        $component->set('gridPinned', [$alphaSoon])->call('sortBy', 'time');
        $this->assertSame($alphaSoon, $orderOf($component->html())[0]);

        $component->call('resetSort')->assertSet('gridSort', '')->assertSet('gridSortDir', 'asc');
    }

    public function test_history_and_canvas_tabs_are_remembered_across_mounts(): void
    {
        $user = $this->actor();

        Livewire::actingAs($user)->test(Main::class)
            ->set('view', 'avantgarde')
            ->call('switchTab', 'grid')
            ->call('switchTab', 'previous')
            ->assertSet('canvasTab', 'grid')
            ->assertSet('activeHistoryTab', 'previous');

        Livewire::actingAs($user)->test(Main::class)
            ->assertSet('canvasTab', 'grid')
            ->assertSet('activeHistoryTab', 'previous');
    }

    public function test_grid_pinned_reservations_float_to_top(): void
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);

        $ids = [];
        foreach ([1, 2, 3] as $day) {
            $ids[] = Reservation::create([
                'user_id' => $user->id,
                'resource_id' => $resource->id,
                'start_time' => now()->addDays($day)->setHour(9),
                'end_time' => now()->addDays($day)->setHour(10),
                'is_full_day' => false,
                'status' => 'active',
                'parent_id' => null,
            ])->id;
        }

        $orderOf = function (string $html): array {
            preg_match_all('/data-rf="reservation-(\d+)"/', $html, $matches);
            return array_map('intval', $matches[1]);
        };

        $component = Livewire::actingAs($user)->test(Main::class)
            ->set('view', 'avantgarde')
            ->call('switchTab', 'grid');

        $natural = $orderOf($component->html());
        $this->assertCount(3, $natural);

        $pinned = end($natural);

        $pinnedOrder = $orderOf($component->set('gridPinned', [$pinned])->html());

        $this->assertSame($pinned, $pinnedOrder[0]);
        $this->assertSame(array_values(array_diff($natural, [$pinned])), array_slice($pinnedOrder, 1));
    }

    public function test_grid_loads_ten_per_page_and_load_more_reveals_rest(): void
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);

        foreach (range(1, 12) as $day) {
            Reservation::create([
                'user_id' => $user->id,
                'resource_id' => $resource->id,
                'start_time' => now()->addDays($day)->setHour(9),
                'end_time' => now()->addDays($day)->setHour(10),
                'is_full_day' => false,
                'status' => 'active',
                'parent_id' => null,
            ]);
        }

        $component = Livewire::actingAs($user)->test(Main::class)
            ->set('view', 'avantgarde')
            ->call('switchTab', 'grid');

        $this->assertCount(10, $component->get('gridReservations'));
        $this->assertStringContainsString('wire:key="grid-load-more"', $component->html());

        $component->call('loadMoreGrid');

        $this->assertCount(12, $component->get('gridReservations'));
        $this->assertStringNotContainsString('wire:key="grid-load-more"', $component->html());

        Livewire::withoutLazyLoading();
        $classic = Livewire::actingAs($user)->test(Main::class);

        $this->assertCount(5, $classic->get('historyReservations'));
        $this->assertCount(10, $classic->call('loadMoreHistory')->get('historyReservations'));
    }

    public function test_view_in_canvas_switches_canvas_tab_and_focuses_resource_deck()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'car', 'status' => 'active', 'metadata' => []]);
        $reservation = Reservation::create([
            'user_id' => $user->id, 'resource_id' => $resource->id,
            'start_time' => now()->addDay()->setHour(9), 'end_time' => now()->addDay()->setHour(10),
            'is_full_day' => false, 'status' => 'active', 'parent_id' => null,
        ]);

        Livewire::actingAs($user)->test(Main::class)
            ->set('view', 'avantgarde')
            ->set('canvasTab', 'grid')
            ->assertSet('activeTab', 'seat')
            ->call('viewInCanvas', $reservation->id)
            ->assertSet('activeTab', 'car')
            ->assertSet('canvasTab', 'booking')
            ->assertSet('open', $resource->id)
            ->assertSet('deckOnly', true);
    }

    public function test_view_in_canvas_ignores_unpermitted_resource_type()
    {
        $user = $this->actor(['booking' => ['all' => false, 'seat' => true, 'car' => false]]);
        $resource = Resource::factory()->create(['type' => 'car', 'status' => 'active', 'metadata' => []]);
        $reservation = Reservation::create([
            'user_id' => $user->id, 'resource_id' => $resource->id,
            'start_time' => now()->addDay()->setHour(9), 'end_time' => now()->addDay()->setHour(10),
            'is_full_day' => false, 'status' => 'active', 'parent_id' => null,
        ]);

        Livewire::actingAs($user)->test(Main::class)
            ->call('viewInCanvas', $reservation->id)
            ->assertSet('activeTab', 'seat')
            ->assertSet('open', null);
    }

    public function test_apply_date_span_filters_grid_history_by_start_time_range()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);

        $inRange = Reservation::create([
            'user_id' => $user->id, 'resource_id' => $resource->id,
            'start_time' => now()->addDay()->setHour(9), 'end_time' => now()->addDay()->setHour(10),
            'is_full_day' => false, 'status' => 'active', 'parent_id' => null,
        ]);
        Reservation::create([
            'user_id' => $user->id, 'resource_id' => $resource->id,
            'start_time' => now()->addDays(20)->setHour(9), 'end_time' => now()->addDays(20)->setHour(10),
            'is_full_day' => false, 'status' => 'active', 'parent_id' => null,
        ]);

        $from = Jalalian::fromCarbon(now());
        $to = Jalalian::fromCarbon(now()->addDays(5));

        $component = Livewire::actingAs($user)->test(Main::class)
            ->set('view', 'avantgarde')
            ->set('fromYear', $from->getYear())->set('fromMonth', $from->getMonth())->set('fromDay', $from->getDay())
            ->set('toYear', $to->getYear())->set('toMonth', $to->getMonth())->set('toDay', $to->getDay())
            ->call('applyDateSpan')
            ->assertSet('historyReservations', fn ($list) => $list->count() === 1 && $list->first()->id === $inRange->id)
            ->assertSet('totalHistoryReservations', 1);

        $this->assertTrue($component->instance()->dateSpanActive());
    }

    public function test_date_span_filter_only_applies_in_avantgarde_view()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);

        Reservation::create([
            'user_id' => $user->id, 'resource_id' => $resource->id,
            'start_time' => now()->addDay()->setHour(9), 'end_time' => now()->addDay()->setHour(10),
            'is_full_day' => false, 'status' => 'active', 'parent_id' => null,
        ]);
        Reservation::create([
            'user_id' => $user->id, 'resource_id' => $resource->id,
            'start_time' => now()->addDays(20)->setHour(9), 'end_time' => now()->addDays(20)->setHour(10),
            'is_full_day' => false, 'status' => 'active', 'parent_id' => null,
        ]);

        $from = Jalalian::fromCarbon(now());
        $to = Jalalian::fromCarbon(now()->addDays(5));

        Livewire::actingAs($user)->test(Main::class)
            ->set('fromYear', $from->getYear())->set('fromMonth', $from->getMonth())->set('fromDay', $from->getDay())
            ->set('toYear', $to->getYear())->set('toMonth', $to->getMonth())->set('toDay', $to->getDay())
            ->call('applyDateSpan')
            ->assertSet('view', 'classic')
            ->assertSet('historyReservations', fn ($list) => $list->count() === 2)
            ->assertSet('totalHistoryReservations', 2);
    }

    public function test_clear_date_span_resets_filter_and_grid_page()
    {
        $user = $this->actor();
        $resource = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => []]);

        Reservation::create([
            'user_id' => $user->id, 'resource_id' => $resource->id,
            'start_time' => now()->addDay()->setHour(9), 'end_time' => now()->addDay()->setHour(10),
            'is_full_day' => false, 'status' => 'active', 'parent_id' => null,
        ]);
        Reservation::create([
            'user_id' => $user->id, 'resource_id' => $resource->id,
            'start_time' => now()->addDays(20)->setHour(9), 'end_time' => now()->addDays(20)->setHour(10),
            'is_full_day' => false, 'status' => 'active', 'parent_id' => null,
        ]);

        $from = Jalalian::fromCarbon(now());
        $to = Jalalian::fromCarbon(now()->addDays(5));

        $component = Livewire::actingAs($user)->test(Main::class)
            ->set('view', 'avantgarde')
            ->set('fromYear', $from->getYear())->set('fromMonth', $from->getMonth())->set('fromDay', $from->getDay())
            ->set('toYear', $to->getYear())->set('toMonth', $to->getMonth())->set('toDay', $to->getDay())
            ->call('applyDateSpan')
            ->call('loadMoreGrid')
            ->assertSet('historyReservations', fn ($list) => $list->count() === 1);

        $component->call('clearDateSpan')
            ->assertSet('gridLimit', 10)
            ->assertSet('historyReservations', fn ($list) => $list->count() === 2);

        $this->assertFalse($component->instance()->dateSpanActive());
    }
}