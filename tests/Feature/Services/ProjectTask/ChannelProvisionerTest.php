<?php

namespace Tests\Feature\Services\ProjectTask;

use App\Enums\ChannelType;
use App\Models\Channel;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectTask\ChannelProvisioner;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChannelProvisionerTest extends TestCase
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

    public function test_resolve_provisions_a_new_private_channel_and_stamps_it_on_the_project(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $member = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Renovation Plan',
            'member_ids' => [$member->id],
            'departments' => [],
            'channel_id' => null,
        ]);

        $channel = app(ChannelProvisioner::class)->resolve($project);

        $this->assertSame(ChannelType::Private, $channel->type);
        $this->assertSame($owner->id, $channel->owner_id);
        $this->assertSame($channel->id, $project->fresh()->channel_id);
        $this->assertTrue($channel->memberUsers()->where('users.id', $member->id)->exists());
        $this->assertTrue($channel->memberUsers()->where('users.id', $owner->id)->exists(), 'the owner is auto-attached as a member by the Channel model itself');
    }

    public function test_resolve_returns_the_existing_channel_without_creating_a_duplicate(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => [], 'channel_id' => null]);
        $first = app(ChannelProvisioner::class)->resolve($project->fresh());

        $second = app(ChannelProvisioner::class)->resolve($project->fresh());

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Channel::where('owner_id', $owner->id)->count());
    }

    public function test_resolve_reprovisions_when_the_linked_channel_no_longer_exists(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => [], 'channel_id' => null]);
        $stale = app(ChannelProvisioner::class)->resolve($project->fresh());
        $stale->forceDelete();

        $fresh = app(ChannelProvisioner::class)->resolve($project->fresh());

        $this->assertNotSame($stale->id, $fresh->id);
        $this->assertSame($fresh->id, $project->fresh()->channel_id);
    }

    public function test_resolve_ignores_member_ids_that_do_not_correspond_to_a_real_user(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $nonExistentId = (User::max('id') ?? 0) + 1000;
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [$nonExistentId],
            'departments' => [],
            'channel_id' => null,
        ]);

        $channel = app(ChannelProvisioner::class)->resolve($project);

        $this->assertFalse($channel->memberUsers()->where('users.id', $nonExistentId)->exists());
        $this->assertSame(1, $channel->memberUsers()->count(), 'only the auto-attached owner should be a member; the non-existent id must be skipped');
    }

    public function test_a_new_channel_is_seeded_from_the_shared_roster_including_department_members(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $listed = User::factory()->create(['status' => 'active']);
        $inactive = User::factory()->create(['status' => 'inactive']);
        $dept = \App\Models\Department::factory()->create();
        $deptMember = User::factory()->create(['status' => 'active']);
        \App\Models\Profile::factory()->create(['user_id' => $deptMember->id, 'department_id' => $dept->code]);
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [$listed->id, $inactive->id],
            'departments' => [$dept->code],
            'channel_id' => null,
        ]);

        $channel = app(ChannelProvisioner::class)->resolve($project->fresh());

        $ids = $channel->memberUsers()->pluck('users.id')->all();
        $this->assertContains($owner->id, $ids);
        $this->assertContains($listed->id, $ids);
        $this->assertContains($deptMember->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
    }
}
