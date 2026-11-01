<?php

namespace Tests\Feature\Models;

use App\Models\Skill;
use App\Models\SkillUser;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SkillModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        ModelCacheVersion::bump(Skill::class);
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        ModelCacheVersion::bump(Skill::class);
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

    public function test_activate_sets_active_and_clears_ghost_flag_and_persists(): void
    {
        $skill = Skill::create(['name' => 'Ghost To Activate ' . uniqid(), 'is_active' => false, 'is_ghost' => true]);

        $skill->activate();

        $fresh = $skill->fresh();
        $this->assertTrue($fresh->is_active);
        $this->assertFalse($fresh->is_ghost);
    }

    public function test_record_search_increments_search_count_and_sets_last_searched_at(): void
    {
        $skill = Skill::create(['name' => 'Search Tracked Skill ' . uniqid(), 'is_active' => false, 'is_ghost' => true, 'search_count' => 2]);

        $skill->recordSearch();

        $fresh = $skill->fresh();
        $this->assertSame(3, $fresh->search_count);
        $this->assertNotNull($fresh->last_searched_at);
        $this->assertTrue($fresh->last_searched_at->diffInSeconds(now()) < 5);
    }

    public function test_scope_active_catalog_returns_only_active_non_ghost_rows(): void
    {
        $active = Skill::create(['name' => 'Active Catalog Skill ' . uniqid(), 'is_active' => true, 'is_ghost' => false]);
        $draft = Skill::create(['name' => 'Draft Skill ' . uniqid(), 'is_active' => false, 'is_ghost' => false]);
        $ghost = Skill::create(['name' => 'Ghost Skill ' . uniqid(), 'is_active' => false, 'is_ghost' => true]);

        $ids = Skill::activeCatalog()->pluck('id')->all();

        $this->assertContains($active->id, $ids);
        $this->assertNotContains($draft->id, $ids);
        $this->assertNotContains($ghost->id, $ids);
    }

    public function test_scope_ghost_returns_only_ghost_rows(): void
    {
        $active = Skill::create(['name' => 'Active Skill ' . uniqid(), 'is_active' => true, 'is_ghost' => false]);
        $ghost = Skill::create(['name' => 'Ghost Only Skill ' . uniqid(), 'is_active' => false, 'is_ghost' => true]);

        $ids = Skill::ghost()->pluck('id')->all();

        $this->assertContains($ghost->id, $ids);
        $this->assertNotContains($active->id, $ids);
    }

    public function test_scope_matching_name_matches_by_name_or_name_en(): void
    {
        $skill = Skill::create(['name' => 'نام فارسی ' . uniqid(), 'name_en' => 'MatchingNameEN' . uniqid()]);

        $this->assertSame($skill->id, Skill::matchingName($skill->name)->first()?->id);
        $this->assertSame($skill->id, Skill::matchingName($skill->name_en)->first()?->id);
        $this->assertNull(Skill::matchingName('No Such Skill ' . uniqid())->first());
    }

    public function test_unscoped_query_still_returns_active_ghost_and_draft_rows(): void
    {
        $active = Skill::create(['name' => 'Unscoped Active ' . uniqid(), 'is_active' => true, 'is_ghost' => false]);
        $draft = Skill::create(['name' => 'Unscoped Draft ' . uniqid(), 'is_active' => false, 'is_ghost' => false]);
        $ghost = Skill::create(['name' => 'Unscoped Ghost ' . uniqid(), 'is_active' => false, 'is_ghost' => true]);

        $ids = Skill::whereIn('id', [$active->id, $draft->id, $ghost->id])->pluck('id')->all();

        $this->assertCount(3, $ids);
    }

    public function test_deleting_skill_with_skill_user_rows_throws_runtime_exception(): void
    {
        $skill = Skill::create(['name' => 'Guarded Model Skill ' . uniqid()]);
        $member = User::factory()->create(['status' => 'active']);
        SkillUser::create(['user_id' => $member->id, 'skill_id' => $skill->id, 'status' => 'approved']);

        $this->expectException(\RuntimeException::class);

        $skill->delete();
    }

    public function test_deleting_skill_with_no_members_succeeds(): void
    {
        $skill = Skill::create(['name' => 'Freely Deletable Skill ' . uniqid()]);

        $skill->delete();

        $this->assertDatabaseMissing('skills', ['id' => $skill->id]);
    }

    public function test_cached_active_catalog_returns_only_active_rows_ordered_by_category_then_name(): void
    {
        $zeta = Skill::create(['name' => 'Zeta Skill ' . uniqid(), 'category' => 'B', 'is_active' => true, 'is_ghost' => false]);
        $alpha = Skill::create(['name' => 'Alpha Skill ' . uniqid(), 'category' => 'A', 'is_active' => true, 'is_ghost' => false]);
        $beta = Skill::create(['name' => 'Beta Skill ' . uniqid(), 'category' => 'B', 'is_active' => true, 'is_ghost' => false]);
        $draft = Skill::create(['name' => 'Hidden Draft ' . uniqid(), 'category' => 'A', 'is_active' => false, 'is_ghost' => false]);
        $ghost = Skill::create(['name' => 'Hidden Ghost ' . uniqid(), 'category' => 'A', 'is_active' => false, 'is_ghost' => true]);

        $mine = Skill::cachedActiveCatalog()
            ->whereIn('id', [$zeta->id, $alpha->id, $beta->id, $draft->id, $ghost->id])
            ->values();

        $this->assertSame([$alpha->id, $beta->id, $zeta->id], $mine->pluck('id')->all());
    }

    public function test_saving_skill_busts_active_catalog_cache(): void
    {
        Skill::create(['name' => 'Cache Seed Skill ' . uniqid(), 'is_active' => true, 'is_ghost' => false]);
        Skill::cachedActiveCatalog();
        $keyBefore = ModelCacheVersion::key(Skill::class, 'skill_active_catalog');

        Skill::create(['name' => 'Cache Busting Skill ' . uniqid(), 'is_active' => true, 'is_ghost' => false]);

        $this->assertNotSame($keyBefore, ModelCacheVersion::key(Skill::class, 'skill_active_catalog'));
    }

    public function test_deleting_skill_busts_active_catalog_cache(): void
    {
        $skill = Skill::create(['name' => 'Cache Delete Skill ' . uniqid(), 'is_active' => true, 'is_ghost' => false]);
        Skill::cachedActiveCatalog();
        $keyBefore = ModelCacheVersion::key(Skill::class, 'skill_active_catalog');

        $skill->delete();

        $this->assertNotSame($keyBefore, ModelCacheVersion::key(Skill::class, 'skill_active_catalog'));
    }
}
