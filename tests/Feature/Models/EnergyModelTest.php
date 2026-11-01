<?php

namespace Tests\Feature\Models;

use App\Models\Department;
use App\Models\EnergyTest;
use App\Models\Profile;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EnergyModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Cache::flush();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Cache::flush();
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

    public function test_answers_cast_round_trips_array_through_db(): void
    {
        $payload = [['q' => 1, 'a' => 3], ['q' => 2, 'a' => 1]];
        $test = EnergyTest::factory()->create(['answers' => $payload]);

        $this->assertSame($payload, $test->fresh()->answers);
    }

    public function test_answers_cast_returns_null_when_column_empty(): void
    {
        $this->markTestSkipped('answers column is NOT NULL in migration 2026_09_19_000018; null branch unreachable');
    }

    public function test_completed_at_cast_round_trips_datetime_through_db(): void
    {
        $when = now();
        $test = EnergyTest::factory()->create(['completed_at' => $when]);

        $this->assertEquals($when->toDateTimeString(), $test->fresh()->completed_at->toDateTimeString());
    }

    public function test_completed_at_cast_returns_null_when_column_empty(): void
    {
        $test = EnergyTest::factory()->create(['completed_at' => null]);

        $this->assertNull($test->fresh()->completed_at);
    }

    public function test_get_average_scores_for_user_returns_zero_records_when_none_exist(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $result = EnergyTest::getAverageScoresForUser($user->id);

        $this->assertSame(0, $result['records_count']);
        $this->assertSame(0.0, $result['overall']);
        $this->assertSame(0.0, $result['categories']['mind']);
        $this->assertSame(0.0, $result['categories']['emotion']);
        $this->assertSame(0.0, $result['categories']['physique']);
        $this->assertSame(0.0, $result['categories']['soul']);
    }

    public function test_get_average_scores_for_user_aggregates_scores_for_user_records(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        EnergyTest::factory()->create([
            'user_id' => $user->id,
            'mind_score' => 2, 'emotion_score' => 1, 'physique_score' => 3, 'soul_score' => 4, 'overall_score' => 10,
        ]);
        EnergyTest::factory()->create([
            'user_id' => $user->id,
            'mind_score' => 4, 'emotion_score' => 3, 'physique_score' => 1, 'soul_score' => 2, 'overall_score' => 10,
        ]);

        $result = EnergyTest::getAverageScoresForUser($user->id);

        $this->assertSame(2, $result['records_count']);
        $this->assertSame(10.0, $result['overall']);
        $this->assertSame(3.0, $result['categories']['mind']);
        $this->assertSame(2.0, $result['categories']['emotion']);
        $this->assertSame(2.0, $result['categories']['physique']);
        $this->assertSame(3.0, $result['categories']['soul']);
    }

    public function test_get_average_scores_for_user_caches_result_under_user_key(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        EnergyTest::factory()->create(['user_id' => $user->id]);

        EnergyTest::getAverageScoresForUser($user->id);

        $this->assertTrue(Cache::has(ModelCacheVersion::key(EnergyTest::class, "user_averages.{$user->id}")));
    }

    public function test_saved_event_forgets_user_averages_cache(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        EnergyTest::factory()->create(['user_id' => $user->id]);
        EnergyTest::getAverageScoresForUser($user->id);
        $keyBefore = ModelCacheVersion::key(EnergyTest::class, "user_averages.{$user->id}");

        EnergyTest::factory()->create(['user_id' => $user->id]);

        $this->assertNotSame($keyBefore, ModelCacheVersion::key(EnergyTest::class, "user_averages.{$user->id}"));
    }

    public function test_deleted_event_forgets_user_averages_cache(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $test = EnergyTest::factory()->create(['user_id' => $user->id]);
        EnergyTest::getAverageScoresForUser($user->id);
        $keyBefore = ModelCacheVersion::key(EnergyTest::class, "user_averages.{$user->id}");

        $test->delete();

        $this->assertNotSame($keyBefore, ModelCacheVersion::key(EnergyTest::class, "user_averages.{$user->id}"));
    }

    public function test_can_submit_returns_true_when_user_has_no_test(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->assertTrue(EnergyTest::canSubmit($user->id));
    }

    public function test_can_submit_returns_false_when_test_completed_within_25_days(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        EnergyTest::factory()->create(['user_id' => $user->id, 'completed_at' => now()->subDays(10)]);

        $this->assertFalse(EnergyTest::canSubmit($user->id));
    }

    public function test_can_submit_returns_true_when_only_test_is_older_than_25_days(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        EnergyTest::factory()->create(['user_id' => $user->id, 'completed_at' => now()->subYears(2)]);

        $this->assertTrue(EnergyTest::canSubmit($user->id));
    }

    public function test_can_submit_with_lock_matches_unlocked_result_when_recent_test_exists(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        EnergyTest::factory()->create(['user_id' => $user->id, 'completed_at' => now()->subDays(5)]);

        $this->assertFalse(EnergyTest::canSubmit($user->id));
        $this->assertFalse(EnergyTest::canSubmit($user->id, lock: true));
    }

    public function test_can_submit_with_lock_returns_true_when_no_test_exists(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->assertTrue(EnergyTest::canSubmit($user->id, lock: true));
    }

    public function test_get_average_scores_by_department_uses_description_as_label(): void
    {
        $dept = Department::factory()->create([
            'code' => 'ZZ-E1', 'name' => 'ZZ-Name-E1', 'description' => 'ZZ-Desc-E1',
        ]);
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => $dept->code]);
        EnergyTest::factory()->create(['user_id' => $user->id, 'overall_score' => 12]);

        $result = EnergyTest::getAverageScoresByDepartment();

        $this->assertTrue($result->has('ZZ-Desc-E1'));
        $this->assertSame(12.0, (float) $result->get('ZZ-Desc-E1'));
    }

    public function test_get_average_scores_by_department_falls_back_to_name_when_description_empty(): void
    {
        $dept = Department::factory()->create([
            'code' => 'ZZ-E2', 'name' => 'ZZ-Name-E2', 'description' => '',
        ]);
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => $dept->code]);
        EnergyTest::factory()->create(['user_id' => $user->id, 'overall_score' => 8]);

        $result = EnergyTest::getAverageScoresByDepartment();

        $this->assertTrue($result->has('ZZ-Name-E2'));
        $this->assertFalse($result->has('ZZ-E2'));
    }

    public function test_get_average_scores_by_department_last_month_flag_excludes_old_records(): void
    {
        $dept = Department::factory()->create([
            'code' => 'ZZ-E3', 'name' => 'ZZ-Name-E3', 'description' => 'ZZ-Desc-E3',
        ]);
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => $dept->code]);
        EnergyTest::factory()->create(['user_id' => $user->id, 'overall_score' => 5, 'completed_at' => now()->subDays(60)]);

        $withoutFlag = EnergyTest::getAverageScoresByDepartment(false);
        $withFlag = EnergyTest::getAverageScoresByDepartment(true);

        $this->assertTrue($withoutFlag->has('ZZ-Desc-E3'));
        $this->assertFalse($withFlag->has('ZZ-Desc-E3'));
    }
}