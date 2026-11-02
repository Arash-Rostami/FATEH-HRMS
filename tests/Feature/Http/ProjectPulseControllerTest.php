<?php

namespace Tests\Feature\Http;

use App\Models\Project;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use App\Services\ProjectTask\ChannelProvisioner;
use App\Services\ProjectTask\ProjectHeartbeat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectPulseControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        Cache::flush();
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

    private function ownerWithProject(array $memberIds = []): array
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => $memberIds, 'departments' => []]);

        return [$owner, $project];
    }

    public function test_redirects_an_unauthenticated_request(): void
    {
        $project = Project::factory()->create(['member_ids' => [], 'departments' => []]);

        $this->get(route('projects.pulse', ['id' => $project->id]))
            ->assertRedirect();
    }

    public function test_a_non_numeric_id_is_a_plain_404(): void
    {
        [$owner] = $this->ownerWithProject();

        $this->actingAs($owner)->get('/projects/abc/pulse')->assertNotFound();
    }

    public function test_returns_404_for_a_project_the_user_cannot_see(): void
    {
        [, $project] = $this->ownerWithProject();
        $stranger = User::factory()->create(['status' => 'active']);

        $this->actingAs($stranger)
            ->get(route('projects.pulse', ['id' => $project->id]))
            ->assertNotFound();
    }

    public function test_returns_all_three_domain_versions_for_a_visible_project(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        foreach (ProjectHeartbeat::DOMAINS as $domain) {
            ProjectHeartbeat::bump($project->id, $domain);
        }

        $this->actingAs($owner)
            ->get(route('projects.pulse', ['id' => $project->id]))
            ->assertOk()
            ->assertJsonStructure(['activity', 'chat', 'task'])
            ->assertJson(ProjectHeartbeat::versions($project->id));
    }

    public function test_teamchat_tab_returns_gone_for_a_non_member(): void
    {
        $stranger = User::factory()->create(['status' => 'active']);
        [, $project] = $this->ownerWithProject([$stranger->id]);

        app(ChannelProvisioner::class)->resolve($project)->memberUsers()->detach($stranger->id);

        $this->actingAs($stranger)
            ->get(route('projects.pulse', ['id' => $project->id, 'tab' => 'teamChat']))
            ->assertOk()
            ->assertJson(['gone' => true]);
    }

    public function test_teamchat_tab_returns_versions_for_a_channel_member(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        app(ChannelProvisioner::class)->resolve($project);

        $this->actingAs($owner)
            ->get(route('projects.pulse', ['id' => $project->id, 'tab' => 'teamChat']))
            ->assertOk()
            ->assertJsonMissing(['gone' => true])
            ->assertJsonStructure(['activity', 'chat', 'task']);
    }

    public function test_non_teamchat_tabs_skip_the_channel_membership_check(): void
    {
        $stranger = User::factory()->create(['status' => 'active']);
        [, $project] = $this->ownerWithProject([$stranger->id]);

        app(ChannelProvisioner::class)->resolve($project)->memberUsers()->detach($stranger->id);

        $this->actingAs($stranger)
            ->get(route('projects.pulse', ['id' => $project->id, 'tab' => 'kanban']))
            ->assertOk()
            ->assertJsonMissing(['gone' => true]);
    }

    public function test_response_is_never_cached(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $response = $this->actingAs($owner)
            ->get(route('projects.pulse', ['id' => $project->id]))
            ->assertOk();

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_only_a_positive_grant_is_ever_cached(): void
    {
        $stranger = User::factory()->create(['status' => 'active']);
        [$owner, $project] = $this->ownerWithProject([$stranger->id]);
        $key = fn (User $user, int $teamChat) => ModelCacheVersion::key(Project::class, "pulse-grant:{$user->id}:{$project->id}:{$teamChat}");

        app(ChannelProvisioner::class)->resolve($project)->memberUsers()->detach($stranger->id);

        $this->actingAs($stranger)
            ->get(route('projects.pulse', ['id' => $project->id, 'tab' => 'teamChat']))
            ->assertJson(['gone' => true]);
        $this->assertFalse(Cache::has($key($stranger, 1)));

        $outsider = User::factory()->create(['status' => 'active']);
        $this->actingAs($outsider)->get(route('projects.pulse', ['id' => $project->id]))->assertNotFound();
        $this->assertFalse(Cache::has($key($outsider, 0)));

        $this->actingAs($owner)->get(route('projects.pulse', ['id' => $project->id]))->assertOk();
        $this->assertTrue(Cache::has($key($owner, 0)));
    }

    public function test_access_grant_is_reused_but_dies_the_moment_the_project_changes(): void
    {
        $member = User::factory()->create(['status' => 'active']);
        [, $project] = $this->ownerWithProject([$member->id]);
        $url = route('projects.pulse', ['id' => $project->id]);

        $this->actingAs($member)->get($url)->assertOk();

        DB::table('projects')->where('id', $project->id)->update(['member_ids' => json_encode([])]);

        $this->actingAs($member)->get($url)->assertOk();

        $project->fresh()->update(['name' => 'Renamed ' . uniqid()]);

        $this->actingAs($member)->get($url)->assertNotFound();
    }
}
