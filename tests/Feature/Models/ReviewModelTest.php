<?php

namespace Tests\Feature\Models;

use App\Models\Department;
use App\Models\Review;
use App\Models\Suggestion;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReviewModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        ModelCacheVersion::bump(Department::class);
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        ModelCacheVersion::bump(Department::class);
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

    public function test_feedbacks_constant_maps_keys_to_persian_labels(): void
    {
        $this->assertCount(5, Review::FEEDBACKS);
        $this->assertSame('موافق', Review::FEEDBACKS['agree']);
        $this->assertSame('نیمه موافق', Review::FEEDBACKS['neutral']);
        $this->assertSame('مخالف', Review::FEEDBACKS['disagree']);
        $this->assertSame('ناقص', Review::FEEDBACKS['incomplete']);
        $this->assertSame('نامشخص', Review::FEEDBACKS['unknown']);
    }

    public function test_feedback_icons_constant_maps_keys_to_emojis(): void
    {
        $this->assertCount(5, Review::FEEDBACK_ICONS);
        $this->assertSame('👍', Review::FEEDBACK_ICONS['agree']);
        $this->assertSame('👎', Review::FEEDBACK_ICONS['disagree']);
        $this->assertSame('❓', Review::FEEDBACK_ICONS['unknown']);
        $this->assertSame(array_keys(Review::FEEDBACKS), array_keys(Review::FEEDBACK_ICONS));
    }

    public function test_complete_cast_round_trips_boolean_through_db(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $suggestion = Suggestion::factory()->create(['user_id' => $user->id]);
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'suggestion_id' => $suggestion->id,
            'complete' => true,
        ]);

        $this->assertTrue($review->fresh()->complete);
    }

    public function test_referral_cast_round_trips_array_through_db(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $suggestion = Suggestion::factory()->create(['user_id' => $user->id]);
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'suggestion_id' => $suggestion->id,
            'referral' => ['HR', 'IT'],
        ]);

        $this->assertSame(['HR', 'IT'], $review->fresh()->referral);
    }

    public function test_referral_cast_decodes_null_to_null_in_memory(): void
    {
        $review = new Review();

        $this->assertNull($review->referral);
    }

    public function test_is_complete_returns_bool_of_complete_flag(): void
    {
        $review = new Review(['complete' => true]);

        $this->assertTrue($review->isComplete());

        $review->complete = false;

        $this->assertFalse($review->isComplete());
    }

    public function test_referral_departments_filters_cached_models_by_referral_codes(): void
    {
        $deptA = Department::factory()->create(['code' => 'RV_A_' . uniqid(), 'name' => 'A']);
        $deptB = Department::factory()->create(['code' => 'RV_B_' . uniqid(), 'name' => 'B']);
        $user = User::factory()->create(['status' => 'active']);
        $suggestion = Suggestion::factory()->create(['user_id' => $user->id]);
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'suggestion_id' => $suggestion->id,
            'referral' => [$deptA->code, $deptB->code],
        ]);

        $codes = $review->referralDepartments()->pluck('code')->all();

        $this->assertContains($deptA->code, $codes);
        $this->assertContains($deptB->code, $codes);
    }

    public function test_referral_departments_returns_empty_collection_when_referral_is_empty_or_null(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $suggestion = Suggestion::factory()->create(['user_id' => $user->id]);

        $empty = Review::factory()->create([
            'user_id' => $user->id,
            'suggestion_id' => $suggestion->id,
            'referral' => [],
        ]);

        $this->assertSame([], $empty->referralDepartments()->all());

        $null = Review::factory()->create([
            'user_id' => $user->id,
            'suggestion_id' => $suggestion->id,
            'referral' => null,
        ]);

        $this->assertSame([], $null->referralDepartments()->all());
    }

    public function test_referral_departments_excludes_codes_not_in_referral(): void
    {
        $deptA = Department::factory()->create(['code' => 'RV_IN_' . uniqid(), 'name' => 'In']);
        $deptB = Department::factory()->create(['code' => 'RV_OUT_' . uniqid(), 'name' => 'Out']);
        $user = User::factory()->create(['status' => 'active']);
        $suggestion = Suggestion::factory()->create(['user_id' => $user->id]);
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'suggestion_id' => $suggestion->id,
            'referral' => [$deptA->code],
        ]);

        $codes = $review->referralDepartments()->pluck('code')->all();

        $this->assertContains($deptA->code, $codes);
        $this->assertNotContains($deptB->code, $codes);
    }
}