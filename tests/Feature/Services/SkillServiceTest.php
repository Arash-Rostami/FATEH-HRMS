<?php

namespace Tests\Feature\Services;

use App\Enums\SkillRequestStatus;
use App\Models\Skill;
use App\Models\SkillUser;
use App\Models\User;
use App\Services\SkillService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SkillServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        app()->setLocale('fa');
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

    public function test_approve_is_a_no_op_when_record_already_approved(): void
    {
        $skill = Skill::create(['name' => 'Already Approved Skill ' . uniqid()]);
        $user = User::factory()->create(['status' => 'active']);
        $approver = User::factory()->create(['status' => 'active']);
        $approvedAt = now()->subDay();
        $row = SkillUser::create([
            'user_id' => $user->id,
            'skill_id' => $skill->id,
            'status' => 'approved',
            'approved_at' => $approvedAt,
            'approved_by' => $approver->id,
        ]);

        app(SkillService::class)->approve($row);

        $fresh = $row->fresh();
        $this->assertSame($approver->id, $fresh->approved_by);
        $this->assertSame($approvedAt->timestamp, $fresh->approved_at->timestamp);
    }

    public function test_reject_is_a_no_op_when_record_already_rejected(): void
    {
        $skill = Skill::create(['name' => 'Already Rejected Skill ' . uniqid()]);
        $user = User::factory()->create(['status' => 'active']);
        $row = SkillUser::create([
            'user_id' => $user->id,
            'skill_id' => $skill->id,
            'status' => 'rejected',
            'rejected_reason' => 'original reason',
        ]);

        app(SkillService::class)->reject($row, 'new reason');

        $fresh = $row->fresh();
        $this->assertSame(SkillRequestStatus::Rejected, $fresh->status);
        $this->assertSame('original reason', $fresh->rejected_reason);
    }

    public function test_approve_catalog_pick_does_not_reactivate_a_skill_deliberately_deactivated_by_admin(): void
    {
        $skill = Skill::create(['name' => 'Deactivated Catalog Skill ' . uniqid(), 'is_active' => false, 'is_ghost' => false]);
        $user = User::factory()->create(['status' => 'active']);
        $admin = User::factory()->create(['status' => 'active']);
        $row = SkillUser::create(['user_id' => $user->id, 'skill_id' => $skill->id, 'status' => 'pending']);

        $this->actingAs($admin);
        app(SkillService::class)->approve($row);

        $freshSkill = $skill->fresh();
        $this->assertFalse($freshSkill->is_active);
        $this->assertFalse($freshSkill->is_ghost);
    }

    public function test_approve_queues_success_notice_keyed_to_row_and_approval_timestamp(): void
    {
        $skill = Skill::create(['name' => 'Notice Approve Skill ' . uniqid()]);
        $user = User::factory()->create(['status' => 'active']);
        $admin = User::factory()->create(['status' => 'active']);
        $row = SkillUser::create(['user_id' => $user->id, 'skill_id' => $skill->id, 'status' => 'pending']);

        $this->actingAs($admin);
        $fresh = app(SkillService::class)->approve($row);

        $notices = Cache::get("skill_notices:{$user->id}", []);
        $dedupeKey = "skills.skill_user.{$fresh->id}.approved.{$fresh->approved_at->timestamp}";
        $this->assertArrayHasKey($dedupeKey, $notices);
        $this->assertSame('success', $notices[$dedupeKey]['type']);
        $this->assertStringContainsString($skill->name, $notices[$dedupeKey]['body']);
    }

    public function test_reject_queues_warning_notice_keyed_to_row_and_update_timestamp(): void
    {
        $skill = Skill::create(['name' => 'Notice Reject Skill ' . uniqid()]);
        $user = User::factory()->create(['status' => 'active']);
        $row = SkillUser::create(['user_id' => $user->id, 'skill_id' => $skill->id, 'status' => 'pending']);

        $fresh = app(SkillService::class)->reject($row, 'Not aligned with catalog');

        $notices = Cache::get("skill_notices:{$user->id}", []);
        $dedupeKey = "skills.skill_user.{$fresh->id}.rejected.{$fresh->updated_at->timestamp}";
        $this->assertArrayHasKey($dedupeKey, $notices);
        $this->assertSame('warning', $notices[$dedupeKey]['type']);
        $this->assertStringContainsString($skill->name, $notices[$dedupeKey]['body']);
    }

    public function test_approve_stamps_approved_by_from_the_currently_authenticated_admin(): void
    {
        $skill = Skill::create(['name' => 'Stamped Approver Skill ' . uniqid()]);
        $user = User::factory()->create(['status' => 'active']);
        $admin = User::factory()->create(['status' => 'active']);
        $row = SkillUser::create(['user_id' => $user->id, 'skill_id' => $skill->id, 'status' => 'pending']);

        $this->actingAs($admin);
        $fresh = app(SkillService::class)->approve($row);

        $this->assertSame($admin->id, $fresh->approved_by);
        $this->assertNotNull($fresh->approved_at);
    }

    public function test_reject_nulls_approved_at_and_approved_by_from_a_stale_approved_state(): void
    {
        $skill = Skill::create(['name' => 'Stale Approved State Skill ' . uniqid()]);
        $user = User::factory()->create(['status' => 'active']);
        $approver = User::factory()->create(['status' => 'active']);
        $row = SkillUser::create([
            'user_id' => $user->id,
            'skill_id' => $skill->id,
            'status' => 'pending',
            'approved_at' => now()->subDay(),
            'approved_by' => $approver->id,
        ]);

        $fresh = app(SkillService::class)->reject($row, 'Reconsidered');

        $this->assertSame(SkillRequestStatus::Rejected, $fresh->status);
        $this->assertSame('Reconsidered', $fresh->rejected_reason);
        $this->assertNull($fresh->approved_at);
        $this->assertNull($fresh->approved_by);
    }
}
