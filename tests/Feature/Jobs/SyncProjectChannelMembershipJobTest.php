<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SyncProjectChannelMembershipJob;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectTask\ChannelProvisioner;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SyncProjectChannelMembershipJobTest extends TestCase
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

    public function test_handle_attaches_current_member_ids_to_the_project_channel(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $member = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [$member->id],
            'departments' => [],
            'channel_id' => null,
        ]);

        (new SyncProjectChannelMembershipJob($project->id))->handle(app(ChannelProvisioner::class));

        $channel = $project->fresh()->channel;
        $this->assertNotNull($channel);
        $this->assertTrue($channel->memberUsers()->where('users.id', $member->id)->exists());
        $this->assertTrue($channel->memberUsers()->where('users.id', $owner->id)->exists());
    }

    public function test_handle_is_self_converging_and_detaches_removed_members(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $member = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [$member->id],
            'departments' => [],
            'channel_id' => null,
        ]);

        (new SyncProjectChannelMembershipJob($project->id))->handle(app(ChannelProvisioner::class));
        $channel = $project->fresh()->channel;
        $this->assertTrue($channel->memberUsers()->where('users.id', $member->id)->exists());

        $project->update(['member_ids' => []]);
        (new SyncProjectChannelMembershipJob($project->id))->handle(app(ChannelProvisioner::class));

        $this->assertFalse($channel->fresh()->memberUsers()->where('users.id', $member->id)->exists());
        $this->assertTrue($channel->fresh()->memberUsers()->where('users.id', $owner->id)->exists());
    }

    public function test_handle_is_a_safe_no_op_when_the_project_no_longer_exists(): void
    {
        $job = new SyncProjectChannelMembershipJob(999999999);

        $job->handle(app(ChannelProvisioner::class));

        $this->assertTrue(true);
    }

    public function test_handle_syncs_the_roster_including_department_members_and_never_detaches_the_owner(): void
    {
        $dept = \App\Models\Department::factory()->create();
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $member = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $deptUser = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $deptGone = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $stranger = User::factory()->create(['status' => 'active', 'role' => 'user']);
        \App\Models\Profile::factory()->create(['user_id' => $deptUser->id, 'department_id' => $dept->code]);
        \App\Models\Profile::factory()->create(['user_id' => $deptGone->id, 'department_id' => $dept->code]);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$member->id], 'departments' => [$dept->code], 'channel_id' => null]);
        $channel = app(ChannelProvisioner::class)->resolve($project->fresh());
        $channel->memberUsers()->attach($stranger->id, ['joined_at' => now(), 'entered_at' => now()]);
        $deptGone->forceFill(['status' => 'inactive'])->saveQuietly();
        $owner->forceFill(['status' => 'inactive'])->saveQuietly();

        (new SyncProjectChannelMembershipJob($project->id))->handle(app(ChannelProvisioner::class));

        $ids = $channel->fresh()->memberUsers()->pluck('users.id')->all();
        $this->assertEqualsCanonicalizing([$owner->id, $member->id, $deptUser->id], $ids);
        $this->assertNull($channel->fresh()->memberUsers()->where('users.id', $deptUser->id)->first()->pivot->entered_at);
    }

    public function test_handle_moves_channel_ownership_to_the_new_project_owner(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $heir = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$heir->id], 'departments' => [], 'channel_id' => null]);
        $channel = app(ChannelProvisioner::class)->resolve($project->fresh());

        Project::find($project->id)->update(['owner_id' => $heir->id]);
        (new SyncProjectChannelMembershipJob($project->id))->handle(app(ChannelProvisioner::class));

        $this->assertSame($heir->id, $channel->fresh()->owner_id);
        $this->assertTrue($channel->fresh()->memberUsers()->where('users.id', $owner->id)->exists());
    }

    public function test_a_newly_assigned_owner_is_entered_in_their_own_channel_so_no_invite_nudge_fires(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $invited = User::factory()->create(['status' => 'active']);
        $newcomer = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$invited->id], 'departments' => [], 'channel_id' => null]);
        (new SyncProjectChannelMembershipJob($project->id))->handle(app(ChannelProvisioner::class));
        $channelId = $project->fresh()->channel_id;
        $this->assertNull(DB::table('channel_members')->where('channel_id', $channelId)->where('user_id', $invited->id)->value('entered_at'));

        $project->update(['owner_id' => $invited->id]);
        (new SyncProjectChannelMembershipJob($project->id))->handle(app(ChannelProvisioner::class));
        $this->assertNotNull(DB::table('channel_members')->where('channel_id', $channelId)->where('user_id', $invited->id)->value('entered_at'));

        $project->update(['owner_id' => $newcomer->id]);
        (new SyncProjectChannelMembershipJob($project->id))->handle(app(ChannelProvisioner::class));
        $this->assertNotNull(DB::table('channel_members')->where('channel_id', $channelId)->where('user_id', $newcomer->id)->value('entered_at'));
    }
}
