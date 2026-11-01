<?php

namespace Tests\Feature\Models;

use App\Models\ReservationPolicy;
use App\Services\Cache\ModelCacheVersion;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReservationPolicyModelTest extends TestCase
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

    public function test_value_cast_serializes_array_to_json_on_set(): void
    {
        $policy = new ReservationPolicy();
        $policy->value = ['window_days' => 7];

        $this->assertSame('{"window_days":7}', $policy->getAttributes()['value']);
    }

    public function test_value_cast_decodes_json_to_array_on_get(): void
    {
        $policy = new ReservationPolicy();
        $policy->value = ['min_duration_minutes' => 30];

        $this->assertSame(['min_duration_minutes' => 30], $policy->value);
    }

    public function test_value_cast_returns_null_when_attribute_is_null(): void
    {
        $policy = new ReservationPolicy();
        $policy->value = null;

        $this->assertNull($policy->value);
        $this->assertNull($policy->getAttributes()['value']);
    }

    public function test_value_cast_round_trips_through_database(): void
    {
        $policy = ReservationPolicy::create([
            'resource_type' => 'test_seat',
            'key' => 'window_days',
            'value' => ['window_days' => 5],
        ]);

        $loaded = ReservationPolicy::find($policy->id);

        $this->assertSame(['window_days' => 5], $loaded->value);
    }

    public function test_fillable_allows_mass_assignment_of_resource_type_key_and_value(): void
    {
        $policy = ReservationPolicy::create([
            'resource_type' => 'test_meeting',
            'key' => 'max_cancel_count',
            'value' => ['max_cancel_count' => 3],
        ]);

        $this->assertSame('test_meeting', $policy->resource_type);
        $this->assertSame('max_cancel_count', $policy->key);
        $this->assertSame(['max_cancel_count' => 3], $policy->value);
    }

    public function test_grouped_by_resource_type_scope_aggregates_per_type(): void
    {
        $before = ReservationPolicy::query()->groupedByResourceType()->count();
        $now = now();
        DB::table('reservation_policies')->insert([
            ['resource_type' => 'test_a', 'key' => 'window_days', 'value' => json_encode([7]), 'created_at' => $now, 'updated_at' => $now],
            ['resource_type' => 'test_a', 'key' => 'min_duration_minutes', 'value' => json_encode([30]), 'created_at' => $now, 'updated_at' => $now],
            ['resource_type' => 'test_b', 'key' => 'window_days', 'value' => json_encode([1]), 'created_at' => $now, 'updated_at' => $now],
        ]);

        $rows = ReservationPolicy::query()->groupedByResourceType()->get();

        $this->assertCount($before + 2, $rows);
        $a = $rows->firstWhere('resource_type', 'test_a');
        $this->assertNotNull($a);
        $this->assertSame(2, (int) $a->rules_count);
        $b = $rows->firstWhere('resource_type', 'test_b');
        $this->assertNotNull($b);
        $this->assertSame(1, (int) $b->rules_count);
    }

    public function test_grouped_by_resource_type_scope_still_returns_all_rows_without_scope(): void
    {
        $before = ReservationPolicy::query()->count();
        $now = now();
        DB::table('reservation_policies')->insert([
            ['resource_type' => 'test_c', 'key' => 'a', 'value' => json_encode([1]), 'created_at' => $now, 'updated_at' => $now],
            ['resource_type' => 'test_c', 'key' => 'b', 'value' => json_encode([2]), 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->assertSame($before + 2, ReservationPolicy::query()->count());
    }

    public function test_saving_a_policy_rotates_the_policy_and_disabled_types_cache_keys(): void
    {
        $policiesKeyBefore = ModelCacheVersion::key(ReservationPolicy::class, 'reservation_policies_test_seat');
        $disabledKeyBefore = ModelCacheVersion::key(ReservationPolicy::class, 'reservation_disabled_types');

        ReservationPolicy::create([
            'resource_type' => 'test_seat',
            'key' => 'window_days',
            'value' => [7],
        ]);

        $this->assertNotSame($policiesKeyBefore, ModelCacheVersion::key(ReservationPolicy::class, 'reservation_policies_test_seat'));
        $this->assertNotSame($disabledKeyBefore, ModelCacheVersion::key(ReservationPolicy::class, 'reservation_disabled_types'));
    }

    public function test_deleting_a_policy_rotates_the_policy_and_disabled_types_cache_keys(): void
    {
        $policy = ReservationPolicy::create([
            'resource_type' => 'test_car',
            'key' => 'window_days',
            'value' => [3],
        ]);
        $policiesKeyBefore = ModelCacheVersion::key(ReservationPolicy::class, 'reservation_policies_test_car');
        $disabledKeyBefore = ModelCacheVersion::key(ReservationPolicy::class, 'reservation_disabled_types');

        $policy->delete();

        $this->assertNotSame($policiesKeyBefore, ModelCacheVersion::key(ReservationPolicy::class, 'reservation_policies_test_car'));
        $this->assertNotSame($disabledKeyBefore, ModelCacheVersion::key(ReservationPolicy::class, 'reservation_disabled_types'));
    }

    public function test_updating_a_policy_rotates_the_policy_and_disabled_types_cache_keys(): void
    {
        $policy = ReservationPolicy::create([
            'resource_type' => 'test_spot',
            'key' => 'window_days',
            'value' => [2],
        ]);
        $policiesKeyBefore = ModelCacheVersion::key(ReservationPolicy::class, 'reservation_policies_test_spot');
        $disabledKeyBefore = ModelCacheVersion::key(ReservationPolicy::class, 'reservation_disabled_types');

        $policy->value = [4];
        $policy->save();

        $this->assertNotSame($policiesKeyBefore, ModelCacheVersion::key(ReservationPolicy::class, 'reservation_policies_test_spot'));
        $this->assertNotSame($disabledKeyBefore, ModelCacheVersion::key(ReservationPolicy::class, 'reservation_disabled_types'));
    }
}