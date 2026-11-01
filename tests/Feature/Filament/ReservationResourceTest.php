<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ReservationResource\Pages\CreateReservation;
use App\Filament\Resources\ReservationResource\Pages\EditReservation;
use App\Filament\Resources\ReservationResource\Pages\ListReservations;
use App\Filament\Resources\ReservationResource\Schemas\ReservationFormPresenter;
use App\Models\Permission;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Morilog\Jalali\Jalalian;
use Tests\TestCase;

class ReservationResourceTest extends TestCase
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

    public function test_reservations_policies_and_resources_share_one_menu_entry_with_a_header_switch()
    {
        $admin = $this->developer();
        $this->actingAs($admin);

        $this->assertTrue(\App\Filament\Resources\ReservationResource::shouldRegisterNavigation());
        $this->assertFalse(\App\Filament\Resources\ReservationPolicyResource::shouldRegisterNavigation());
        $this->assertFalse(\App\Filament\Resources\ResourceResource::shouldRegisterNavigation());

        $pages = [
            ListReservations::class => 'switch_reservation_resource',
            \App\Filament\Resources\ReservationPolicyResource\Pages\ListPolicies::class => 'switch_reservation_policy_resource',
            \App\Filament\Resources\ResourceResource\Pages\ListResources::class => 'switch_resource_resource',
        ];

        foreach ($pages as $page => $active) {
            $component = Livewire::actingAs($admin)->test($page);

            foreach ($pages as $other) {
                $component->assertActionHasColor($other, $other === $active ? 'primary' : 'gray');
            }
        }

        Livewire::actingAs($admin)->test(ListReservations::class)
            ->assertActionHasUrl('switch_reservation_policy_resource', \App\Filament\Resources\ReservationPolicyResource::getUrl())
            ->assertActionHasUrl('switch_resource_resource', \App\Filament\Resources\ResourceResource::getUrl());
    }

    public function test_the_header_switch_only_offers_resources_the_user_may_view()
    {
        $reservationOnly = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        Permission::create(['user_id' => $reservationOnly->id, 'is_super_admin' => false, 'abilities' => [['module' => 'reservation', 'actions' => ['view']]]]);

        Livewire::actingAs($reservationOnly)->test(ListReservations::class)
            ->assertActionExists('switch_reservation_resource')
            ->assertActionDoesNotExist('switch_reservation_policy_resource')
            ->assertActionDoesNotExist('switch_resource_resource');
    }

    private function developer(): User
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        Permission::create(['user_id' => $user->id, 'is_super_admin' => true]);

        return $user;
    }

    private function seatResource(): Resource
    {
        return Resource::factory()->create([
            'name' => '0-test-seat',
            'type' => 'seat',
            'status' => 'active',
            'metadata' => [],
        ]);
    }

    public function test_list_renders_and_shows_factory_reservation_row()
    {
        $admin = $this->developer();
        $resource = $this->seatResource();
        $reservation = Reservation::factory()->create([
            'user_id' => $admin->id,
            'resource_id' => $resource->id,
            'status' => 'active',
            'is_full_day' => false,
            'start_time' => now()->addDay()->setHour(9)->startOfHour(),
            'end_time' => now()->addDay()->setHour(10)->startOfHour(),
            'parent_id' => null,
        ]);

        Livewire::actingAs($admin)->test(ListReservations::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$reservation]);
    }

    public function test_create_reservation_persists_user_resource_and_status()
    {
        $admin = $this->developer();
        $resource = $this->seatResource();
        $start = now()->addDay()->startOfDay()->addHours(9);
        $jStart = Jalalian::fromCarbon($start);
        $jEnd = Jalalian::fromCarbon($start->copy()->addHour());

        Livewire::actingAs($admin)->test(CreateReservation::class)
            ->fillForm([
                'user_id' => $admin->id,
                'resource_id' => $resource->id,
                'status' => 'active',
                'is_full_day' => false,
                'start_time_date' => $start->format('Y-m-d'),
                'start_time_date_year' => $jStart->getYear(),
                'start_time_date_month' => $jStart->getMonth(),
                'start_time_date_day' => $jStart->getDay(),
                'start_time_time' => $start->format('H:i'),
                'end_time_date' => $start->copy()->addHour()->format('Y-m-d'),
                'end_time_date_year' => $jEnd->getYear(),
                'end_time_date_month' => $jEnd->getMonth(),
                'end_time_date_day' => $jEnd->getDay(),
                'end_time_time' => $start->copy()->addHour()->format('H:i'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('reservations', [
            'user_id' => $admin->id,
            'resource_id' => $resource->id,
            'status' => 'active',
        ]);
    }

    public function test_create_reservation_requires_user_id()
    {
        $admin = $this->developer();
        $resource = $this->seatResource();
        $start = now()->addDay()->startOfDay()->addHours(9);
        $jStart = Jalalian::fromCarbon($start);
        $jEnd = Jalalian::fromCarbon($start->copy()->addHour());

        Livewire::actingAs($admin)->test(CreateReservation::class)
            ->fillForm([
                'user_id' => null,
                'resource_id' => $resource->id,
                'status' => 'active',
                'is_full_day' => false,
                'start_time_date' => $start->format('Y-m-d'),
                'start_time_date_year' => $jStart->getYear(),
                'start_time_date_month' => $jStart->getMonth(),
                'start_time_date_day' => $jStart->getDay(),
                'start_time_time' => $start->format('H:i'),
                'end_time_date' => $start->copy()->addHour()->format('Y-m-d'),
                'end_time_date_year' => $jEnd->getYear(),
                'end_time_date_month' => $jEnd->getMonth(),
                'end_time_date_day' => $jEnd->getDay(),
                'end_time_time' => $start->copy()->addHour()->format('H:i'),
            ])
            ->call('create')
            ->assertHasFormErrors(['user_id' => 'required']);
    }

    public function test_edit_reservation_updates_status()
    {
        $admin = $this->developer();
        $resource = $this->seatResource();
        $start = now()->addDay()->startOfDay()->addHours(9);
        $reservation = Reservation::factory()->create([
            'user_id' => $admin->id,
            'resource_id' => $resource->id,
            'status' => 'active',
            'is_full_day' => false,
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'parent_id' => null,
        ]);

        Livewire::actingAs($admin)->test(EditReservation::class, ['record' => $reservation->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['status' => 'released'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('released', $reservation->fresh()->status);
    }

    public function test_release_truncates_ongoing_range_to_free_resource(): void
    {
        $admin = $this->developer();
        $resource = $this->seatResource();
        $reservation = Reservation::factory()->create([
            'user_id' => $admin->id,
            'resource_id' => $resource->id,
            'status' => 'active',
            'is_full_day' => false,
            'start_time' => now()->subDay(),
            'end_time' => now()->addMonths(11),
            'parent_id' => null,
        ]);

        Livewire::actingAs($admin)->test(ListReservations::class)
            ->callTableAction('release', $reservation);

        $fresh = $reservation->fresh();
        $this->assertSame('released', $fresh->status);
        $this->assertNotNull($fresh->end_time);
        $this->assertLessThan(60, abs(now()->diffInSeconds($fresh->end_time, false)));
    }

    public function test_release_does_not_truncate_future_range(): void
    {
        $admin = $this->developer();
        $resource = $this->seatResource();
        $end = now()->addMonths(6);
        $reservation = Reservation::factory()->create([
            'user_id' => $admin->id,
            'resource_id' => $resource->id,
            'status' => 'active',
            'is_full_day' => false,
            'start_time' => now()->addMonth(),
            'end_time' => $end,
            'parent_id' => null,
        ]);

        Livewire::actingAs($admin)->test(ListReservations::class)
            ->callTableAction('release', $reservation);

        $fresh = $reservation->fresh();
        $this->assertSame('released', $fresh->status);
        $this->assertSame($end->format('Y-m-d H:i'), Carbon::parse($fresh->end_time)->format('Y-m-d H:i'));
    }

    public function test_delete_reservation_action_removes_row()
    {
        $admin = $this->developer();
        $resource = $this->seatResource();
        $reservation = Reservation::factory()->create([
            'user_id' => $admin->id,
            'resource_id' => $resource->id,
            'status' => 'active',
            'is_full_day' => false,
            'start_time' => now()->addDay()->setHour(9)->startOfHour(),
            'end_time' => now()->addDay()->setHour(10)->startOfHour(),
            'parent_id' => null,
        ]);

        Livewire::actingAs($admin)->test(ListReservations::class)
            ->callTableAction(\Filament\Actions\DeleteAction::class, $reservation);

        $this->assertDatabaseMissing('reservations', ['id' => $reservation->id]);
    }

    public function test_status_filter_scopes_list_to_active_reservations()
    {
        $admin = $this->developer();
        $resource = $this->seatResource();
        $start = now()->addDay()->setHour(9)->startOfHour();

        $active = Reservation::factory()->create([
            'user_id' => $admin->id,
            'resource_id' => $resource->id,
            'status' => 'active',
            'is_full_day' => false,
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'parent_id' => null,
        ]);

        $cancelled = Reservation::factory()->create([
            'user_id' => $admin->id,
            'resource_id' => $resource->id,
            'status' => 'cancelled_user',
            'is_full_day' => false,
            'start_time' => $start->copy()->addDay(),
            'end_time' => $start->copy()->addDay()->addHour(),
            'parent_id' => null,
        ]);

        Livewire::actingAs($admin)->test(ListReservations::class)
            ->assertSuccessful()
            ->filterTable('status', 'active')
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$cancelled]);
    }

    public function test_is_series_filter_scopes_list_to_recurring_reservations()
    {
        $admin = $this->developer();
        $resource = $this->seatResource();
        $start = now()->addDay()->setHour(9)->startOfHour();

        $root = Reservation::factory()->create([
            'user_id' => $admin->id,
            'resource_id' => $resource->id,
            'status' => 'active',
            'is_full_day' => false,
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'parent_id' => null,
        ]);

        $child = Reservation::factory()->create([
            'user_id' => $admin->id,
            'resource_id' => $resource->id,
            'status' => 'active',
            'is_full_day' => false,
            'start_time' => $start->copy()->addWeek(),
            'end_time' => $start->copy()->addWeek()->addHour(),
            'parent_id' => $root->id,
        ]);

        Livewire::actingAs($admin)->test(ListReservations::class)
            ->assertSuccessful()
            ->filterTable('is_series', true)
            ->assertCanSeeTableRecords([$child])
            ->assertCanNotSeeTableRecords([$root]);
    }

    public function test_resource_type_filter_scopes_list_to_matching_resource_type()
    {
        $admin = $this->developer();
        $seat = $this->seatResource();
        $meeting = Resource::factory()->create([
            'name' => '0-test-meeting',
            'type' => 'meeting',
            'status' => 'active',
            'metadata' => [],
        ]);
        $start = now()->addDay()->setHour(9)->startOfHour();

        $seatReservation = Reservation::factory()->create([
            'user_id' => $admin->id,
            'resource_id' => $seat->id,
            'status' => 'active',
            'is_full_day' => false,
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'parent_id' => null,
        ]);

        $meetingReservation = Reservation::factory()->create([
            'user_id' => $admin->id,
            'resource_id' => $meeting->id,
            'status' => 'active',
            'is_full_day' => false,
            'start_time' => $start->copy()->addDay(),
            'end_time' => $start->copy()->addDay()->addHour(),
            'parent_id' => null,
        ]);

        Livewire::actingAs($admin)->test(ListReservations::class)
            ->assertSuccessful()
            ->filterTable('resource_type', 'seat')
            ->assertCanSeeTableRecords([$seatReservation])
            ->assertCanNotSeeTableRecords([$meetingReservation]);
    }

    public function test_is_full_day_filter_scopes_list_to_full_day_reservations()
    {
        $admin = $this->developer();
        $resource = $this->seatResource();
        $start = now()->addDay()->setHour(9)->startOfHour();

        $fullDay = Reservation::factory()->create([
            'user_id' => $admin->id,
            'resource_id' => $resource->id,
            'status' => 'active',
            'is_full_day' => true,
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'parent_id' => null,
        ]);

        $partial = Reservation::factory()->create([
            'user_id' => $admin->id,
            'resource_id' => $resource->id,
            'status' => 'active',
            'is_full_day' => false,
            'start_time' => $start->copy()->addDay(),
            'end_time' => $start->copy()->addDay()->addHour(),
            'parent_id' => null,
        ]);

        Livewire::actingAs($admin)->test(ListReservations::class)
            ->assertSuccessful()
            ->filterTable('is_full_day', true)
            ->assertCanSeeTableRecords([$fullDay])
            ->assertCanNotSeeTableRecords([$partial]);
    }

    public function test_created_at_filter_scopes_by_from_until()
    {
        $admin = $this->developer();
        $resource = $this->seatResource();
        $start = now()->addDay()->setHour(9)->startOfHour();

        $old = Reservation::factory()->create([
            'user_id' => $admin->id,
            'resource_id' => $resource->id,
            'status' => 'active',
            'is_full_day' => false,
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'parent_id' => null,
            'created_at' => '2026-01-15',
        ]);

        $recent = Reservation::factory()->create([
            'user_id' => $admin->id,
            'resource_id' => $resource->id,
            'status' => 'active',
            'is_full_day' => false,
            'start_time' => $start->copy()->addDay(),
            'end_time' => $start->copy()->addDay()->addHour(),
            'parent_id' => null,
            'created_at' => '2026-03-20',
        ]);

        Livewire::actingAs($admin)->test(ListReservations::class)
            ->assertSuccessful()
            ->filterTable('created_at', ['from' => '2026-02-01', 'until' => '2026-04-01'])
            ->assertCanSeeTableRecords([$recent])
            ->assertCanNotSeeTableRecords([$old]);
    }

    public function test_multi_day_range_disables_full_day_and_recurring_controls_via_year_month_day_selects()
    {
        $this->app->setLocale('fa');
        $admin = $this->developer();
        $resource = $this->seatResource();
        $start = now()->addDays(5)->startOfDay();
        $end = $start->copy()->addDays(3);
        $jStart = Jalalian::fromCarbon($start);
        $jEnd = Jalalian::fromCarbon($end);

        Livewire::actingAs($admin)->test(CreateReservation::class)
            ->fillForm([
                'user_id' => $admin->id,
                'resource_id' => $resource->id,
                'status' => 'active',
                'is_full_day' => false,
                'is_recurring' => true,
            ])
            ->set('data.start_time_date_year', $jStart->getYear())
            ->set('data.start_time_date_month', $jStart->getMonth())
            ->set('data.start_time_date_day', $jStart->getDay())
            ->set('data.end_time_date_year', $jEnd->getYear())
            ->set('data.end_time_date_month', $jEnd->getMonth())
            ->set('data.end_time_date_day', $jEnd->getDay())
            ->assertFormFieldIsDisabled('is_full_day')
            ->assertFormFieldIsDisabled('is_recurring')
            ->assertFormFieldIsHidden('recur_count')
            ->assertFormFieldIsHidden('recur_pattern');
    }

    public function test_parent_id_search_matches_by_reserving_user_name(): void
    {
        $admin = $this->developer();
        $resource = $this->seatResource();
        $target = User::factory()->create(['name' => 'Zohreh Searchable Target']);
        $root = Reservation::factory()->create([
            'user_id' => $target->id,
            'resource_id' => $resource->id,
            'status' => 'active',
            'is_full_day' => false,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'parent_id' => null,
        ]);

        $results = ReservationFormPresenter::parentId()->getSearchResults('Zohreh Searchable');

        $this->assertArrayHasKey($root->id, $results);
    }

    public function test_parent_id_search_matches_by_resource_name(): void
    {
        $admin = $this->developer();
        $resource = Resource::factory()->create([
            'name' => 'Searchable Conference Room',
            'type' => 'meeting',
            'status' => 'active',
            'metadata' => [],
        ]);
        $root = Reservation::factory()->create([
            'user_id' => $admin->id,
            'resource_id' => $resource->id,
            'status' => 'active',
            'is_full_day' => false,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'parent_id' => null,
        ]);

        $results = ReservationFormPresenter::parentId()->getSearchResults('Conference Room');

        $this->assertArrayHasKey($root->id, $results);
    }

    public function test_edit_reservation_with_parent_id_resolves_option_label_without_errors(): void
    {
        $admin = $this->developer();
        $resource = $this->seatResource();
        $root = Reservation::factory()->create([
            'user_id' => $admin->id,
            'resource_id' => $resource->id,
            'status' => 'active',
            'is_full_day' => false,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'parent_id' => null,
        ]);
        $child = Reservation::factory()->create([
            'user_id' => $admin->id,
            'resource_id' => $resource->id,
            'status' => 'active',
            'is_full_day' => false,
            'start_time' => now()->addWeek(),
            'end_time' => now()->addWeek()->addHour(),
            'parent_id' => $root->id,
        ]);

        Livewire::actingAs($admin)->test(EditReservation::class, ['record' => $child->getRouteKey()])
            ->assertSuccessful();

        $select = ReservationFormPresenter::parentId();
        $property = new \ReflectionProperty($select, 'getOptionLabelUsing');
        $property->setAccessible(true);
        $closure = $property->getValue($select);

        $this->assertSame($root->resource_dropdown_label, $closure($root->id));
    }

    public function test_all_table_groups_render_without_error()
    {
        $admin = $this->developer();
        $resource = $this->seatResource();
        Reservation::factory()->create([
            'user_id' => $admin->id,
            'resource_id' => $resource->id,
            'status' => 'active',
            'is_full_day' => false,
            'start_time' => now()->addDay()->setHour(9)->startOfHour(),
            'end_time' => now()->addDay()->setHour(10)->startOfHour(),
            'parent_id' => null,
        ]);

        $component = Livewire::actingAs($admin)->test(ListReservations::class)->assertSuccessful();

        foreach (['user.name', 'status', 'resource.name'] as $group) {
            $component->set('tableGrouping', $group)->assertSuccessful();
        }
    }

    public function test_sort_by_id_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $resource = $this->seatResource();
        $start = now()->addDay()->setHour(9)->startOfHour();

        $first = Reservation::factory()->create([
            'user_id' => $admin->id, 'resource_id' => $resource->id, 'status' => 'active',
            'is_full_day' => false, 'start_time' => $start, 'end_time' => $start->copy()->addHour(), 'parent_id' => null,
        ]);
        $second = Reservation::factory()->create([
            'user_id' => $admin->id, 'resource_id' => $resource->id, 'status' => 'active',
            'is_full_day' => false, 'start_time' => $start->copy()->addDay(), 'end_time' => $start->copy()->addDay()->addHour(), 'parent_id' => null,
        ]);

        $component = Livewire::actingAs($admin)->test(ListReservations::class)
            ->searchTable('0-test-seat')
            ->sortTable('id')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('id', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sort_by_created_at_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $resource = $this->seatResource();
        $start = now()->addDay()->setHour(9)->startOfHour();

        $older = Reservation::factory()->create([
            'user_id' => $admin->id, 'resource_id' => $resource->id, 'status' => 'active', 'is_full_day' => false,
            'start_time' => $start, 'end_time' => $start->copy()->addHour(), 'parent_id' => null, 'created_at' => '2020-01-15',
        ]);
        $newer = Reservation::factory()->create([
            'user_id' => $admin->id, 'resource_id' => $resource->id, 'status' => 'active', 'is_full_day' => false,
            'start_time' => $start->copy()->addDay(), 'end_time' => $start->copy()->addDay()->addHour(), 'parent_id' => null, 'created_at' => '2030-01-15',
        ]);

        $component = Livewire::actingAs($admin)->test(ListReservations::class)
            ->searchTable('0-test-seat')
            ->sortTable('created_at')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('created_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));
    }

    public function test_sort_by_start_time_and_end_time_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $resource = $this->seatResource();

        $earlier = Reservation::factory()->create([
            'user_id' => $admin->id, 'resource_id' => $resource->id, 'status' => 'active', 'is_full_day' => false,
            'start_time' => now()->addDays(2)->setHour(9)->startOfHour(),
            'end_time' => now()->addDays(2)->setHour(10)->startOfHour(),
            'parent_id' => null,
        ]);
        $later = Reservation::factory()->create([
            'user_id' => $admin->id, 'resource_id' => $resource->id, 'status' => 'active', 'is_full_day' => false,
            'start_time' => now()->addYears(3)->setHour(9)->startOfHour(),
            'end_time' => now()->addYears(3)->setHour(10)->startOfHour(),
            'parent_id' => null,
        ]);

        $component = Livewire::actingAs($admin)->test(ListReservations::class)
            ->searchTable('0-test-seat')
            ->sortTable('start_time')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$earlier->id, $later->id], array_values(array_intersect($ids, [$earlier->id, $later->id])));

        $component->sortTable('start_time', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$later->id, $earlier->id], array_values(array_intersect($ids, [$earlier->id, $later->id])));

        $component->sortTable('end_time')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$earlier->id, $later->id], array_values(array_intersect($ids, [$earlier->id, $later->id])));

        $component->sortTable('end_time', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$later->id, $earlier->id], array_values(array_intersect($ids, [$earlier->id, $later->id])));
    }

    public function test_sort_by_status_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $resource = $this->seatResource();
        $start = now()->addDay()->setHour(9)->startOfHour();

        $active = Reservation::factory()->create([
            'user_id' => $admin->id, 'resource_id' => $resource->id, 'status' => 'active', 'is_full_day' => false,
            'start_time' => $start, 'end_time' => $start->copy()->addHour(), 'parent_id' => null,
        ]);
        $cancelled = Reservation::factory()->create([
            'user_id' => $admin->id, 'resource_id' => $resource->id, 'status' => 'cancelled_user', 'is_full_day' => false,
            'start_time' => $start->copy()->addDay(), 'end_time' => $start->copy()->addDay()->addHour(), 'parent_id' => null,
        ]);

        $component = Livewire::actingAs($admin)->test(ListReservations::class)
            ->searchTable('0-test-seat')
            ->sortTable('status')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$active->id, $cancelled->id], array_values(array_intersect($ids, [$active->id, $cancelled->id])));

        $component->sortTable('status', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$cancelled->id, $active->id], array_values(array_intersect($ids, [$active->id, $cancelled->id])));
    }

    public function test_sort_by_resource_labeled_name_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $resourceA = Resource::factory()->create(['name' => '0-AAA-sort-resource', 'type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $resourceZ = Resource::factory()->create(['name' => '0-ZZZ-sort-resource', 'type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $start = now()->addDay()->setHour(9)->startOfHour();

        $first = Reservation::factory()->create([
            'user_id' => $admin->id, 'resource_id' => $resourceA->id, 'status' => 'active', 'is_full_day' => false,
            'start_time' => $start, 'end_time' => $start->copy()->addHour(), 'parent_id' => null,
        ]);
        $second = Reservation::factory()->create([
            'user_id' => $admin->id, 'resource_id' => $resourceZ->id, 'status' => 'active', 'is_full_day' => false,
            'start_time' => $start->copy()->addDay(), 'end_time' => $start->copy()->addDay()->addHour(), 'parent_id' => null,
        ]);

        $component = Livewire::actingAs($admin)->test(ListReservations::class)
            ->searchTable('sort-resource')
            ->sortTable('resource.labeled_name')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('resource.labeled_name', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sort_by_user_name_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $resource = $this->seatResource();
        $userA = User::factory()->create(['name' => '0-AAA-sort-reservation-user']);
        $userZ = User::factory()->create(['name' => '0-ZZZ-sort-reservation-user']);
        $start = now()->addDay()->setHour(9)->startOfHour();

        $first = Reservation::factory()->create([
            'user_id' => $userA->id, 'resource_id' => $resource->id, 'status' => 'active', 'is_full_day' => false,
            'start_time' => $start, 'end_time' => $start->copy()->addHour(), 'parent_id' => null,
        ]);
        $second = Reservation::factory()->create([
            'user_id' => $userZ->id, 'resource_id' => $resource->id, 'status' => 'active', 'is_full_day' => false,
            'start_time' => $start->copy()->addDay(), 'end_time' => $start->copy()->addDay()->addHour(), 'parent_id' => null,
        ]);

        $component = Livewire::actingAs($admin)->test(ListReservations::class)
            ->searchTable('sort-reservation-user')
            ->sortTable('user.name')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('user.name', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            \App\Filament\Resources\ReservationResource\Pages\CreateReservation::class => \App\Filament\Pages\CreateRecord::class,
            \App\Filament\Resources\ReservationResource\Pages\EditReservation::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\ReservationResource\Pages\ListReservations::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\ReservationResource\Pages\CreateReservation::class, \App\Filament\Resources\ReservationResource\Pages\EditReservation::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }

        $managers = [\App\Filament\Resources\ReservationResource\RelationManagers\OccurrencesRelationManager::class];

        foreach ($managers as $manager) {
            $this->assertSame(\App\Filament\RelationManagers\RelationManager::class, get_parent_class($manager));
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, class_uses_recursive($manager));
        }
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/ReservationResource/Schemas/ReservationTablePresenter.php'),
        ];

        $recordMethods = ['getTitleFromRecordUsing', 'getKeyFromRecordUsing', 'getDescriptionFromRecordUsing'];
        $queryMethods = ['modifyQueryUsing', 'groupQueryUsing', 'orderQueryUsing', 'scopeQueryByKeyUsing', 'scopeQueryUsing', 'query'];

        $offenders = [];

        foreach ([[$recordMethods, 'record'], [$queryMethods, 'query']] as [$methods, $expectedName]) {
            $pattern = '/\\b(' . implode('|', $methods) . ')\\s*\\(\\s*(?:static\\s+)?(?:function|fn)\\s*\\(([^)]*)\\)/s';

            foreach ($files as $path) {
                $contents = file_get_contents($path);

                if (!preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                    continue;
                }

                foreach ($matches as $match) {
                    $method = $match[1][0];
                    $paramList = $match[2][0];
                    $offset = $match[0][1];

                    $firstParam = trim(explode(',', $paramList)[0] ?? '');

                    if ($firstParam === '') {
                        continue;
                    }

                    if (!preg_match('/^(?<type>[^$]*)\\$(?<name>[A-Za-z_][A-Za-z0-9_]*)/', $firstParam, $m)) {
                        continue;
                    }

                    $hasType = trim($m['type']) !== '';
                    $name = $m['name'];

                    if ($name === $expectedName || $hasType) {
                        continue;
                    }

                    $line = substr_count(substr($contents, 0, $offset), "\n") + 1;
                    $offenders[] = basename($path) . ":{$line} {$method}(fn(\${$name}) => ...)";
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Closure param must be named \$record/\$query (or carry an explicit type hint) so Filament's by-name evaluate() injection binds correctly:\n" . implode("\n", $offenders),
        );
    }
}
