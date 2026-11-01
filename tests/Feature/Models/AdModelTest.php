<?php

namespace Tests\Feature\Models;

use App\Models\Ad;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdModelTest extends TestCase
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

    public function test_avatar_accessor_returns_male_mapping_for_male_gender(): void
    {
        $ad = new Ad(['gender' => 'Male']);

        $this->assertSame(['icon' => 'manage_accounts', 'color' => 'blue', 'title' => 'آقایان'], $ad->avatar);
    }

    public function test_avatar_accessor_returns_female_mapping_for_female_gender(): void
    {
        $ad = new Ad(['gender' => 'Female']);

        $this->assertSame(['icon' => 'badge', 'color' => 'pink', 'title' => 'خانم‌ها'], $ad->avatar);
    }

    public function test_avatar_accessor_returns_any_mapping_for_any_gender(): void
    {
        $ad = new Ad(['gender' => 'Any']);

        $this->assertSame(['icon' => 'group', 'color' => 'green', 'title' => 'همه'], $ad->avatar);
    }

    public function test_avatar_accessor_returns_any_mapping_when_gender_null(): void
    {
        $ad = new Ad();

        $this->assertSame(['icon' => 'group', 'color' => 'green', 'title' => 'همه'], $ad->avatar);
    }

    public function test_active_cast_round_trips_boolean_through_db(): void
    {
        $ad = Ad::factory()->create(['active' => true]);

        $this->assertTrue($ad->fresh()->active);

        $ad->active = false;
        $ad->save();

        $this->assertFalse($ad->fresh()->active);
    }

    public function test_extra_cast_round_trips_array_through_db(): void
    {
        $payload = [['key' => 'شرح شغل', 'value' => 'تست']];
        $ad = Ad::factory()->create(['extra' => $payload]);

        $this->assertSame($payload, $ad->fresh()->extra);
    }

    public function test_extra_cast_returns_null_when_column_empty(): void
    {
        $ad = Ad::factory()->create(['extra' => null]);

        $this->assertNull($ad->fresh()->extra);
    }

    public function test_scope_active_filters_to_active_ads(): void
    {
        $baseline = Ad::active()->count();
        $active = Ad::factory()->create(['active' => true]);
        $inactive = Ad::factory()->create(['active' => false]);

        $ids = Ad::active()->pluck('id')->all();

        $this->assertContains($active->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
        $this->assertSame($baseline + 1, Ad::active()->count());
    }

    public function test_scope_inactive_filters_to_inactive_ads(): void
    {
        $baseline = Ad::inactive()->count();
        $active = Ad::factory()->create(['active' => true]);
        $inactive = Ad::factory()->create(['active' => false]);

        $ids = Ad::inactive()->pluck('id')->all();

        $this->assertContains($inactive->id, $ids);
        $this->assertNotContains($active->id, $ids);
        $this->assertSame($baseline + 1, Ad::inactive()->count());
    }

    public function test_has_active_trait_keeps_active_mass_assignable_and_boolean_cast(): void
    {
        $ad = new Ad(['active' => 0]);

        $this->assertFalse($ad->active);
        $this->assertTrue((new Ad(['active' => 1]))->active);
        $this->assertContains('active', $ad->getFillable());
    }

    public function test_scope_gender_filters_by_gender_value(): void
    {
        $baseline = Ad::gender('Female')->count();
        $female = Ad::factory()->create(['gender' => 'Female']);
        $male = Ad::factory()->create(['gender' => 'Male']);

        $ids = Ad::gender('Female')->pluck('id')->all();

        $this->assertContains($female->id, $ids);
        $this->assertNotContains($male->id, $ids);
        $this->assertSame($baseline + 1, Ad::gender('Female')->count());
    }

    public function test_get_active_counts_returns_delta_for_active_and_inactive(): void
    {
        $before = Ad::getActiveCounts();
        Ad::factory()->create(['active' => true]);
        Ad::factory()->create(['active' => false]);

        $after = Ad::getActiveCounts();

        $this->assertSame($before['active'] + 1, $after['active']);
        $this->assertSame($before['inactive'] + 1, $after['inactive']);
    }

    public function test_get_gender_counts_returns_delta_for_male_and_female(): void
    {
        $before = Ad::getGenderCounts();
        Ad::factory()->create(['gender' => 'Male']);
        Ad::factory()->create(['gender' => 'Female']);

        $after = Ad::getGenderCounts();

        $this->assertSame($before['Male'] + 1, $after['Male']);
        $this->assertSame($before['Female'] + 1, $after['Female']);
    }

    public function test_count_active_jobs_and_count_inactive_jobs_match_get_active_counts(): void
    {
        Ad::factory()->create(['active' => true]);
        Ad::factory()->create(['active' => false]);

        $counts = Ad::getActiveCounts();

        $this->assertSame($counts['active'], Ad::countActiveJobs());
        $this->assertSame($counts['inactive'], Ad::countInactiveJobs());
    }

    public function test_count_males_and_count_females_match_get_gender_counts(): void
    {
        Ad::factory()->create(['gender' => 'Male']);
        Ad::factory()->create(['gender' => 'Female']);

        $counts = Ad::getGenderCounts();

        $this->assertSame($counts['Male'], Ad::countMales());
        $this->assertSame($counts['Female'], Ad::countFemales());
    }
}