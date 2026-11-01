<?php

namespace Tests\Feature\Jobs;

use App\Jobs\LogMissingSkillJob;
use App\Models\Skill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LogMissingSkillJobTest extends TestCase
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

    public function test_dispatch_after_commit_pushes_job_with_search_term_intact(): void
    {
        Queue::fake();

        dispatch(new LogMissingSkillJob('Kubernetes'))->afterCommit();

        Queue::assertPushed(LogMissingSkillJob::class, fn ($job) => $job->searchTerm === 'Kubernetes');
    }

    public function test_constructor_exposes_tries_backoff_and_search_term(): void
    {
        $job = new LogMissingSkillJob('Kubernetes');

        $this->assertSame(3, $job->tries);
        $this->assertSame([10, 30], $job->backoff);
        $this->assertSame('Kubernetes', $job->searchTerm);
    }

    public function test_handle_with_unmatched_term_creates_new_ghost_skill(): void
    {
        $term = 'Unmet Demand Skill ' . uniqid();

        (new LogMissingSkillJob($term))->handle();

        $skill = Skill::where('name', $term)->firstOrFail();
        $this->assertFalse($skill->is_active);
        $this->assertTrue($skill->is_ghost);
        $this->assertSame(1, $skill->search_count);
    }

    public function test_handle_called_again_on_existing_ghost_increments_search_count_and_bumps_last_searched_at(): void
    {
        $term = 'Repeated Ghost Skill ' . uniqid();
        $original = now()->subDay();
        $ghost = Skill::create(['name' => $term, 'is_active' => false, 'is_ghost' => true, 'search_count' => 1, 'last_searched_at' => $original]);

        (new LogMissingSkillJob($term))->handle();

        $this->assertSame(1, Skill::where('name', $term)->count());
        $fresh = $ghost->fresh();
        $this->assertSame(2, $fresh->search_count);
        $this->assertTrue($fresh->last_searched_at->greaterThan($original));
    }

    public function test_handle_with_term_matching_active_catalog_skill_does_nothing(): void
    {
        $term = 'Known Catalog Skill ' . uniqid();
        $active = Skill::create(['name' => $term, 'is_active' => true, 'is_ghost' => false, 'search_count' => 0]);

        (new LogMissingSkillJob($term))->handle();

        $fresh = $active->fresh();
        $this->assertSame(0, $fresh->search_count);
        $this->assertFalse($fresh->is_ghost);
        $this->assertSame(1, Skill::where('name', $term)->count());
    }

    public function test_handle_with_term_matching_inactive_proposal_draft_does_nothing(): void
    {
        $term = 'Pending Proposal Draft ' . uniqid();
        $draft = Skill::create(['name' => $term, 'is_active' => false, 'is_ghost' => false, 'search_count' => 0]);

        (new LogMissingSkillJob($term))->handle();

        $fresh = $draft->fresh();
        $this->assertSame(0, $fresh->search_count);
        $this->assertFalse($fresh->is_ghost);
        $this->assertFalse($fresh->is_active);
        $this->assertSame(1, Skill::where('name', $term)->count());
    }

    public function test_handle_with_blank_normalized_search_term_is_a_no_op(): void
    {
        (new LogMissingSkillJob('   '))->handle();

        $this->assertNull(Skill::where('name', '')->first());
    }
}
