<?php

namespace Tests\Feature\Services\ProjectTask;

use App\Filament\Resources\TaskResource\Enums\TaskState;
use App\Models\Project;
use App\Models\User;
use App\Models\Workflow;
use App\Services\ProjectTask\PopulateStepTaskAction;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PopulateStepTaskActionTest extends TestCase
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

    private function step(array $overrides = []): array
    {
        return array_merge([
            'label' => 'بازبینی',
            'assignee_user_ids' => [],
            'type' => 'action',
            'populate_task' => true,
            'task_title' => 'وظیفه چرخه',
            'task_description' => null,
            'deadline_days' => null,
        ], $overrides);
    }

    public function test_maps_the_driver_and_collaborators_from_assignee_ids(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $driver = User::factory()->create(['status' => 'active']);
        $collaboratorA = User::factory()->create(['status' => 'active']);
        $collaboratorB = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$driver->id, $collaboratorA->id, $collaboratorB->id], 'departments' => []]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id]);

        $task = app(PopulateStepTaskAction::class)->execute(
            $workflow,
            $this->step(['assignee_user_ids' => [$driver->id, $collaboratorA->id, $collaboratorB->id]]),
            $project
        );

        $this->assertSame($driver->id, $task->assigned_to);
        $this->assertSame([$collaboratorA->id, $collaboratorB->id], $task->detail->collaborators);
    }

    public function test_single_assignee_step_leaves_collaborators_null(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $driver = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$driver->id], 'departments' => []]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id]);

        $task = app(PopulateStepTaskAction::class)->execute($workflow, $this->step(['assignee_user_ids' => [$driver->id]]), $project);

        $this->assertNull($task->detail->collaborators);
    }

    public function test_stamps_the_task_detail_completion_state_and_workflow_action_source(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $driver = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$driver->id], 'departments' => []]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'name' => 'چرخه استاندارد']);

        $task = app(PopulateStepTaskAction::class)->execute(
            $workflow,
            $this->step(['assignee_user_ids' => [$driver->id], 'label' => 'تأیید نهایی']),
            $project
        );

        $this->assertSame(TaskState::Completion->value, $task->detail->state);
        $this->assertSame('چرخه کاری', $task->detail->action_source_domain);
        $this->assertSame('چرخه استاندارد · تأیید نهایی', $task->detail->action_source);
        $this->assertSame($project->id, $task->project_id);
    }

    public function test_creator_falls_back_from_workflow_owner_to_project_owner_to_first_assignee(): void
    {
        $wfOwner = User::factory()->create(['status' => 'active']);
        $projectOwner = User::factory()->create(['status' => 'active']);
        $driver = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $projectOwner->id, 'member_ids' => [$driver->id], 'departments' => []]);

        $withWfOwner = Workflow::factory()->active()->create(['owner_id' => $wfOwner->id, 'project_id' => $project->id]);
        $task = app(PopulateStepTaskAction::class)->execute($withWfOwner, $this->step(['assignee_user_ids' => [$driver->id]]), $project);
        $this->assertSame($wfOwner->id, $task->user_id);

        $withoutWfOwner = Workflow::factory()->active()->create(['owner_id' => null, 'project_id' => $project->id]);
        $task = app(PopulateStepTaskAction::class)->execute($withoutWfOwner, $this->step(['assignee_user_ids' => [$driver->id]]), $project);
        $this->assertSame($projectOwner->id, $task->user_id);

        $task = app(PopulateStepTaskAction::class)->execute($withoutWfOwner, $this->step(['assignee_user_ids' => [$driver->id]]), null);
        $this->assertSame($driver->id, $task->user_id);
    }

    public function test_deadline_is_null_when_deadline_days_is_not_set(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $driver = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$driver->id], 'departments' => []]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id]);

        $task = app(PopulateStepTaskAction::class)->execute($workflow, $this->step(['assignee_user_ids' => [$driver->id]]), $project);

        $this->assertNull($task->deadline);
    }

    public function test_deadline_resolves_from_deadline_days_at_noon(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $driver = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$driver->id], 'departments' => []]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id]);

        $task = app(PopulateStepTaskAction::class)->execute($workflow, $this->step(['assignee_user_ids' => [$driver->id], 'deadline_days' => 5]), $project);

        $this->assertSame(now()->addDays(5)->toDateString(), $task->deadline->toDateString());
        $this->assertSame('12:00:00', $task->deadline->format('H:i:s'));
    }

    public function test_deadline_is_clamped_to_the_project_deadline_cap(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $driver = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [$driver->id],
            'departments' => [],
            'settings' => ['deadline' => now()->addDays(5)->toDateString()],
        ]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id]);

        $task = app(PopulateStepTaskAction::class)->execute($workflow, $this->step(['assignee_user_ids' => [$driver->id], 'deadline_days' => 30]), $project);

        $this->assertSame(now()->addDays(5)->setTime(12, 0)->toDateTimeString(), $task->deadline->toDateTimeString());
    }
}
