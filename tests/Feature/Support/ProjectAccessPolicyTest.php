<?php

namespace Tests\Feature\Support;

use App\Models\Project;
use App\Models\User;
use App\Support\ProjectAccessPolicy;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectAccessPolicyTest extends TestCase
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

    public function test_can_view_returns_true_for_visible_project(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $this->assertTrue(ProjectAccessPolicy::canView($project, $owner));
    }

    public function test_can_view_returns_false_for_stranger_and_null_user(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $this->assertFalse(ProjectAccessPolicy::canView($project, $stranger));
        $this->assertFalse(ProjectAccessPolicy::canView($project, null));
    }

    public function test_can_manage_audience_returns_true_only_for_owner(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $member = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$member->id], 'departments' => []]);

        $this->assertTrue(ProjectAccessPolicy::canManageAudience($project, $owner));
        $this->assertFalse(ProjectAccessPolicy::canManageAudience($project, $member));
        $this->assertFalse(ProjectAccessPolicy::canManageAudience($project, null));
    }

    public function test_can_manage_audience_allows_non_owner_inside_admin_panel_context(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $stranger = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $this->assertFalse(ProjectAccessPolicy::canManageAudience($project, $stranger));

        Filament::setCurrentPanel('admin');

        $this->assertTrue(ProjectAccessPolicy::canManageAudience($project, $stranger));
    }

    public function test_can_manage_audience_rejects_elevated_role_user_outside_admin_panel_context(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $elevated = User::factory()->create(['status' => 'active', 'role' => 'developer']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $this->assertFalse(ProjectAccessPolicy::canManageAudience($project, $elevated));
    }

    public function test_update_project_action_rejects_non_owner(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $member = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$member->id], 'departments' => []]);

        $component = \Livewire\Livewire::actingAs($member)->test(\App\Livewire\Dashboard\Project\Main::class);
        $form = $component->instance()->projectForm;
        $form->name = 'Renamed';
        $form->memberIds = [];
        $form->departments = [];

        try {
            app(\App\Livewire\Dashboard\Project\Actions\UpdateProjectAction::class)->execute($project, $form);
            $this->fail('Expected HttpException was not thrown');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertNotSame('Renamed', $project->fresh()->name, 'a rejected update must never reach persistence');
    }
}
