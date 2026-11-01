<?php

namespace Tests\Feature\Services\ProjectTask;

use App\Enums\TaskActivityType;
use App\Models\Project;
use App\Models\Reply;
use App\Models\Task;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HandleWorkflowTransitionTest extends TestCase
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

    private function twoActionSteps(int $assigneeId): array
    {
        return [
            ['label' => 'مرحله اول', 'assignee_user_ids' => [$assigneeId], 'type' => 'action', 'reject_to_step' => null, 'populate_task' => false, 'task_title' => null, 'task_description' => null, 'deadline_days' => null],
            ['label' => 'مرحله دوم', 'assignee_user_ids' => [$assigneeId], 'type' => 'approval', 'reject_to_step' => 0, 'populate_task' => false, 'task_title' => null, 'task_description' => null, 'deadline_days' => null],
        ];
    }

    private function latestMirror(int $projectId): ?Reply
    {
        return Reply::where('repliable_type', Project::class)
            ->where('repliable_id', $projectId)
            ->where('type', TaskActivityType::WorkflowStep->value)
            ->latest('id')
            ->first();
    }

    public function test_resolves_started_event_on_first_activation(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $workflow = Workflow::factory()->create([
            'owner_id' => $owner->id,
            'project_id' => $project->id,
            'status' => Workflow::STATUS_DRAFT,
            'current_step' => null,
            'steps' => $this->twoActionSteps($assignee->id),
        ]);

        Workflow::find($workflow->id)->update(['status' => Workflow::STATUS_ACTIVE, 'current_step' => 0, 'started_at' => now()]);

        $reply = $this->latestMirror($project->id);
        $this->assertSame('started', $reply->payload['event']);
        $this->assertSame('مرحله اول', $reply->payload['label']);
        $this->assertArrayNotHasKey('note', $reply->payload);
    }

    public function test_resolves_advanced_event_on_forward_move(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $workflow = Workflow::factory()->active()->create([
            'owner_id' => $owner->id,
            'project_id' => $project->id,
            'current_step' => 0,
            'steps' => $this->twoActionSteps($assignee->id),
            'step_log' => [],
        ]);

        Workflow::find($workflow->id)->update([
            'current_step' => 1,
            'step_log' => [['step' => 0, 'completed_by' => $assignee->id, 'completed_at' => now()->toISOString()]],
        ]);

        $reply = $this->latestMirror($project->id);
        $this->assertSame('advanced', $reply->payload['event']);
        $this->assertSame(1, $reply->payload['to_step']);
    }

    public function test_resolves_rejected_event_on_backward_move(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $workflow = Workflow::factory()->active()->create([
            'owner_id' => $owner->id,
            'project_id' => $project->id,
            'current_step' => 1,
            'steps' => $this->twoActionSteps($assignee->id),
            'step_log' => [],
        ]);

        Workflow::find($workflow->id)->update([
            'current_step' => 0,
            'step_log' => [['step' => 1, 'completed_by' => $assignee->id, 'completed_at' => now()->toISOString(), 'approved' => false, 'note' => 'نیاز به اصلاح دارد']],
        ]);

        $reply = $this->latestMirror($project->id);
        $this->assertSame('rejected', $reply->payload['event']);
        $this->assertSame(0, $reply->payload['to_step']);
        $this->assertSame('نیاز به اصلاح دارد', $reply->payload['note']);
    }

    public function test_resolves_completed_event_when_status_flips_without_current_step_moving(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $workflow = Workflow::factory()->active()->create([
            'owner_id' => $owner->id,
            'project_id' => $project->id,
            'current_step' => 1,
            'steps' => $this->twoActionSteps($assignee->id),
            'step_log' => [],
        ]);

        Workflow::find($workflow->id)->update([
            'status' => Workflow::STATUS_COMPLETED,
            'completed_at' => now(),
            'step_log' => [['step' => 1, 'completed_by' => $assignee->id, 'completed_at' => now()->toISOString(), 'approved' => true, 'note' => 'تمام شد']],
        ]);

        $reply = $this->latestMirror($project->id);
        $this->assertSame('completed', $reply->payload['event']);
        $this->assertSame('تمام شد', $reply->payload['note']);
    }

    public function test_resolves_cancelled_event_and_never_carries_a_note(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $workflow = Workflow::factory()->active()->create([
            'owner_id' => $owner->id,
            'project_id' => $project->id,
            'current_step' => 0,
            'steps' => $this->twoActionSteps($assignee->id),
            'step_log' => [['step' => 0, 'completed_by' => $assignee->id, 'completed_at' => now()->toISOString(), 'note' => 'یادداشت قبلی']],
        ]);

        Workflow::find($workflow->id)->update(['status' => Workflow::STATUS_CANCELLED]);

        $reply = $this->latestMirror($project->id);
        $this->assertSame('cancelled', $reply->payload['event']);
        $this->assertArrayNotHasKey('note', $reply->payload);
    }

    private function populatedStep(int $driverId, ?int $taskId = null): array
    {
        return array_merge(
            ['label' => 'اقدام', 'assignee_user_ids' => [$driverId], 'type' => 'action', 'reject_to_step' => null, 'populate_task' => true, 'task_title' => 'وظیفه چرخه', 'task_description' => null, 'deadline_days' => null],
            $taskId ? ['task_id' => $taskId] : []
        );
    }

    public function test_populate_task_creates_a_new_task_on_first_activation(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $driver = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$driver->id], 'departments' => []]);
        $workflow = Workflow::factory()->create([
            'owner_id' => $owner->id,
            'project_id' => $project->id,
            'status' => Workflow::STATUS_DRAFT,
            'current_step' => null,
            'steps' => [$this->populatedStep($driver->id), ['label' => 'دوم', 'assignee_user_ids' => [$driver->id], 'type' => 'action', 'populate_task' => false]],
        ]);

        Workflow::find($workflow->id)->update(['status' => Workflow::STATUS_ACTIVE, 'current_step' => 0, 'started_at' => now()]);

        $taskId = $workflow->fresh()->steps[0]['task_id'] ?? null;
        $this->assertNotNull($taskId);
        $this->assertSame($project->id, Task::find($taskId)->project_id);
    }

    public function test_reuse_guard_re_points_to_an_existing_open_task_in_the_same_project(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $driver = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$driver->id], 'departments' => []]);
        $existingTask = Task::factory()->create(['project_id' => $project->id, 'status' => 'todo', 'assigned_to' => $driver->id]);
        $workflow = Workflow::factory()->active()->create([
            'owner_id' => $owner->id,
            'project_id' => $project->id,
            'current_step' => 1,
            'steps' => [$this->populatedStep($driver->id, $existingTask->id), ['label' => 'دوم', 'assignee_user_ids' => [$driver->id], 'type' => 'action', 'populate_task' => false]],
        ]);

        $countBefore = Task::count();
        Workflow::find($workflow->id)->update(['current_step' => 0, 'step_log' => []]);

        $this->assertSame($countBefore, Task::count());
        $this->assertSame($existingTask->id, $workflow->fresh()->steps[0]['task_id']);
    }

    public function test_reuse_guard_creates_a_fresh_task_when_the_existing_one_is_genuinely_done(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $driver = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$driver->id], 'departments' => []]);
        $doneTask = Task::factory()->create(['project_id' => $project->id, 'status' => 'done', 'approved_at' => now(), 'assigned_to' => $driver->id]);
        $workflow = Workflow::factory()->active()->create([
            'owner_id' => $owner->id,
            'project_id' => $project->id,
            'current_step' => 1,
            'steps' => [$this->populatedStep($driver->id, $doneTask->id), ['label' => 'دوم', 'assignee_user_ids' => [$driver->id], 'type' => 'action', 'populate_task' => false]],
        ]);

        $countBefore = Task::count();
        Workflow::find($workflow->id)->update(['current_step' => 0, 'step_log' => []]);

        $this->assertSame($countBefore + 1, Task::count());
        $this->assertNotSame($doneTask->id, $workflow->fresh()->steps[0]['task_id']);
    }

    public function test_reuse_guard_creates_a_fresh_task_when_the_existing_one_moved_to_a_different_project(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $driver = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$driver->id], 'departments' => []]);
        $otherProject = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$driver->id], 'departments' => []]);
        $movedTask = Task::factory()->create(['project_id' => $otherProject->id, 'status' => 'todo', 'assigned_to' => $driver->id]);
        $workflow = Workflow::factory()->active()->create([
            'owner_id' => $owner->id,
            'project_id' => $project->id,
            'current_step' => 1,
            'steps' => [$this->populatedStep($driver->id, $movedTask->id), ['label' => 'دوم', 'assignee_user_ids' => [$driver->id], 'type' => 'action', 'populate_task' => false]],
        ]);

        $countBefore = Task::count();
        Workflow::find($workflow->id)->update(['current_step' => 0, 'step_log' => []]);

        $this->assertSame($countBefore + 1, Task::count());
        $freshTaskId = $workflow->fresh()->steps[0]['task_id'];
        $this->assertNotSame($movedTask->id, $freshTaskId);
        $this->assertSame($otherProject->id, $movedTask->fresh()->project_id, 'the stale foreign task must be left untouched');
    }
}
