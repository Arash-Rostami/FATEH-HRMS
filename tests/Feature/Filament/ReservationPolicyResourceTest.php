<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ReservationPolicyResource\Pages\EditPolicy;
use App\Filament\Resources\ReservationPolicyResource\Pages\ListPolicies;
use App\Models\Permission;
use App\Models\ReservationPolicy;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ReservationPolicyResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        $this->app->setLocale('fa');
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

    private function developer(): User
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        Permission::create(['user_id' => $user->id, 'is_super_admin' => true]);

        return $user;
    }

    private function uniqueResourceType(): string
    {
        return 'zz-test-' . uniqid();
    }

    private function validPolicyData(): array
    {
        return [
            'window_days' => 21,
            'window_hours' => 0,
            'min_duration_minutes' => 30,
            'max_duration_minutes' => 480,
            'allowed_hours_start' => '08:00',
            'allowed_hours_end' => '18:00',
            'allowed_days' => ['saturday', 'sunday', 'monday'],
            'max_per_user' => 2,
            'max_cancel_count' => 3,
            'allow_full_day' => true,
            'allow_repeat' => true,
            'allow_partial_cancel' => false,
            'allow_overlap_release' => false,
        ];
    }

    public function test_last_minute_hours_is_saved_then_cleared_and_must_exceed_window_hours()
    {
        $admin = $this->developer();
        $type = $this->uniqueResourceType();
        ReservationPolicy::factory()->create(['resource_type' => $type, 'key' => 'window_days', 'value' => []]);
        $component = Livewire::actingAs($admin)->test(EditPolicy::class, ['record' => $type]);

        $component->fillForm([...$this->validPolicyData(), 'window_hours' => 6, 'last_minute_hours' => 6])
            ->call('save')->assertHasFormErrors(['last_minute_hours']);
        $component->fillForm([...$this->validPolicyData(), 'window_hours' => 6, 'last_minute_hours' => 0])
            ->call('save')->assertHasFormErrors(['last_minute_hours']);
        $component->fillForm([...$this->validPolicyData(), 'window_hours' => 6, 'last_minute_hours' => 721])
            ->call('save')->assertHasFormErrors(['last_minute_hours']);

        $component->fillForm([...$this->validPolicyData(), 'window_hours' => 6, 'last_minute_hours' => 24])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame(24, ReservationPolicy::where('resource_type', $type)->where('key', 'last_minute_hours')->first()->value);

        $component->fillForm([...$this->validPolicyData(), 'window_hours' => 6, 'last_minute_hours' => null])
            ->call('save')->assertHasNoFormErrors();
        $this->assertNull(ReservationPolicy::where('resource_type', $type)->where('key', 'last_minute_hours')->first()->value);
    }

    public function test_cancel_window_days_is_validated_saved_and_cleared()
    {
        $admin = $this->developer();
        $type = $this->uniqueResourceType();
        ReservationPolicy::factory()->create(['resource_type' => $type, 'key' => 'window_days', 'value' => []]);
        $component = Livewire::actingAs($admin)->test(EditPolicy::class, ['record' => $type]);

        foreach ([0, 366] as $bad) {
            $component->fillForm([...$this->validPolicyData(), 'cancel_window_days' => $bad])
                ->call('save')->assertHasFormErrors(['cancel_window_days']);
        }

        $component->fillForm([...$this->validPolicyData(), 'cancel_window_days' => 60])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame(60, (int) ReservationPolicy::where('resource_type', $type)->where('key', 'cancel_window_days')->first()->value);

        $component->fillForm([...$this->validPolicyData(), 'cancel_window_days' => null])
            ->call('save')->assertHasNoFormErrors();
        $this->assertNull(ReservationPolicy::where('resource_type', $type)->where('key', 'cancel_window_days')->first()->value);
    }

    public function test_list_renders_and_shows_a_policy_group()
    {
        $admin = $this->developer();

        $policy = ReservationPolicy::factory()->create([
            'resource_type' => $this->uniqueResourceType(),
            'key' => 'window_days',
            'value' => [21],
        ]);

        Livewire::actingAs($admin)
            ->test(ListPolicies::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$policy]);
    }

    public function test_edit_page_mounts_for_a_resource_type()
    {
        $admin = $this->developer();

        $type = $this->uniqueResourceType();
        ReservationPolicy::factory()->create([
            'resource_type' => $type,
            'key' => 'window_days',
            'value' => [21],
        ]);

        $component = Livewire::actingAs($admin)
            ->test(EditPolicy::class, ['record' => $type])
            ->assertSuccessful();

        $this->assertStringContainsString($type, (string) $component->instance()->getHeading());
    }

    public function test_edit_persists_numeric_and_toggle_policy_values()
    {
        $admin = $this->developer();

        $type = $this->uniqueResourceType();
        ReservationPolicy::factory()->create([
            'resource_type' => $type,
            'key' => 'window_days',
            'value' => [],
        ]);

        Livewire::actingAs($admin)
            ->test(EditPolicy::class, ['record' => $type])
            ->fillForm($this->validPolicyData())
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(21, ReservationPolicy::where('resource_type', $type)->where('key', 'window_days')->first()->value);
        $this->assertSame(480, ReservationPolicy::where('resource_type', $type)->where('key', 'max_duration_minutes')->first()->value);
        $this->assertTrue(ReservationPolicy::where('resource_type', $type)->where('key', 'allow_full_day')->first()->value);
        $this->assertFalse(ReservationPolicy::where('resource_type', $type)->where('key', 'allow_partial_cancel')->first()->value);
    }

    public function test_edit_persists_allowed_days_array()
    {
        $admin = $this->developer();

        $type = $this->uniqueResourceType();
        ReservationPolicy::factory()->create([
            'resource_type' => $type,
            'key' => 'window_days',
            'value' => [],
        ]);

        Livewire::actingAs($admin)
            ->test(EditPolicy::class, ['record' => $type])
            ->fillForm($this->validPolicyData())
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            ['saturday', 'sunday', 'monday'],
            ReservationPolicy::where('resource_type', $type)->where('key', 'allowed_days')->first()->value
        );
    }

    public function test_edit_persists_allowed_hours_as_nested_json()
    {
        $admin = $this->developer();

        $type = $this->uniqueResourceType();
        ReservationPolicy::factory()->create([
            'resource_type' => $type,
            'key' => 'window_days',
            'value' => [],
        ]);

        Livewire::actingAs($admin)
            ->test(EditPolicy::class, ['record' => $type])
            ->fillForm($this->validPolicyData())
            ->call('save')
            ->assertHasNoFormErrors();

        $hours = ReservationPolicy::where('resource_type', $type)->where('key', 'allowed_hours')->first()->value;
        $this->assertSame('08:00', $hours['start']);
        $this->assertSame('18:00', $hours['end']);

        $this->assertDatabaseMissing('reservation_policies', ['resource_type' => $type, 'key' => 'allowed_hours_start']);
        $this->assertDatabaseMissing('reservation_policies', ['resource_type' => $type, 'key' => 'allowed_hours_end']);
    }

    public function test_edit_rejects_min_duration_minutes_below_one()
    {
        $admin = $this->developer();

        $type = $this->uniqueResourceType();
        ReservationPolicy::factory()->create([
            'resource_type' => $type,
            'key' => 'window_days',
            'value' => [],
        ]);

        Livewire::actingAs($admin)
            ->test(EditPolicy::class, ['record' => $type])
            ->fillForm(array_merge($this->validPolicyData(), ['min_duration_minutes' => 0]))
            ->call('save')
            ->assertHasFormErrors(['min_duration_minutes']);
    }

    public function test_edit_rejects_window_days_above_365()
    {
        $admin = $this->developer();

        $type = $this->uniqueResourceType();
        ReservationPolicy::factory()->create([
            'resource_type' => $type,
            'key' => 'window_days',
            'value' => [],
        ]);

        Livewire::actingAs($admin)
            ->test(EditPolicy::class, ['record' => $type])
            ->fillForm(array_merge($this->validPolicyData(), ['window_days' => 400]))
            ->call('save')
            ->assertHasFormErrors(['window_days']);
    }

    public function test_view_action_renders_policy_infolist_without_error()
    {
        $admin = $this->developer();

        $type = $this->uniqueResourceType();
        $now = now();
        $rows = collect([
            'window_days' => 21,
            'window_hours' => 0,
            'min_duration_minutes' => 30,
            'max_duration_minutes' => 480,
            'allowed_hours' => ['start' => '08:00', 'end' => '18:00'],
            'allowed_days' => ['saturday', 'sunday', 'monday'],
            'max_per_user' => 2,
            'max_range_days' => 10,
            'max_cancel_count' => 3,
            'allow_full_day' => true,
            'allow_repeat' => true,
            'allow_partial_cancel' => false,
            'allow_overlap_release' => false,
            'requires_approval' => false,
        ])->map(fn($value, $key) => [
            'resource_type' => $type,
            'key' => $key,
            'value' => json_encode($value),
            'created_at' => $now,
            'updated_at' => $now,
        ])->values()->toArray();

        ReservationPolicy::upsert($rows, ['resource_type', 'key'], ['value', 'updated_at']);
        $groupedRow = ReservationPolicy::groupedByResourceType()->where('resource_type', $type)->firstOrFail();

        Livewire::actingAs($admin)
            ->test(ListPolicies::class)
            ->assertSuccessful()
            ->mountTableAction('view', $groupedRow)
            ->assertSuccessful();
    }

    public function test_sort_by_resource_type_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $typeA = 'zz-sort-policy-aaa';
        $typeZ = 'zz-sort-policy-zzz';
        ReservationPolicy::factory()->create(['resource_type' => $typeA, 'key' => 'window_days', 'value' => [21]]);
        ReservationPolicy::factory()->create(['resource_type' => $typeZ, 'key' => 'window_days', 'value' => [21]]);

        $rowA = ReservationPolicy::groupedByResourceType()->where('resource_type', $typeA)->firstOrFail();
        $rowZ = ReservationPolicy::groupedByResourceType()->where('resource_type', $typeZ)->firstOrFail();

        $component = Livewire::actingAs($admin)->test(ListPolicies::class)
            ->sortTable('resource_type')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$rowA->id, $rowZ->id], array_values(array_intersect($ids, [$rowA->id, $rowZ->id])));

        $component->sortTable('resource_type', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$rowZ->id, $rowA->id], array_values(array_intersect($ids, [$rowA->id, $rowZ->id])));
    }

    public function test_sort_by_last_updated_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $typeOld = $this->uniqueResourceType();
        $typeNew = $this->uniqueResourceType();
        ReservationPolicy::factory()->create(['resource_type' => $typeOld, 'key' => 'window_days', 'value' => [21], 'updated_at' => '2020-01-15']);
        ReservationPolicy::factory()->create(['resource_type' => $typeNew, 'key' => 'window_days', 'value' => [21], 'updated_at' => '2030-01-15']);

        $rowOld = ReservationPolicy::groupedByResourceType()->where('resource_type', $typeOld)->firstOrFail();
        $rowNew = ReservationPolicy::groupedByResourceType()->where('resource_type', $typeNew)->firstOrFail();

        $component = Livewire::actingAs($admin)->test(ListPolicies::class)
            ->sortTable('last_updated')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$rowOld->id, $rowNew->id], array_values(array_intersect($ids, [$rowOld->id, $rowNew->id])));

        $component->sortTable('last_updated', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$rowNew->id, $rowOld->id], array_values(array_intersect($ids, [$rowOld->id, $rowNew->id])));
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            \App\Filament\Resources\ReservationPolicyResource\Pages\EditPolicy::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\ReservationPolicyResource\Pages\ListPolicies::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\ReservationPolicyResource\Pages\EditPolicy::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }
    }
}