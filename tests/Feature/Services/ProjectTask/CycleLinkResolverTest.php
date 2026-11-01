<?php

namespace Tests\Feature\Services\ProjectTask;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workflow;
use App\Services\ProjectTask\CycleLinkResolver;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CycleLinkResolverTest extends TestCase
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

    private function step(string $label, ?int $taskId, array $assignees): array
    {
        return ['label' => $label, 'assignee_user_ids' => $assignees, 'type' => 'action', 'reject_to_step' => null, 'task_id' => $taskId];
    }

    private function fixture(): array
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $first = Task::factory()->forProject($project)->create(['user_id' => $owner->id, 'assigned_to' => $owner->id, 'deadline' => now()->addDays(5)]);
        $second = Task::factory()->forProject($project)->create(['user_id' => $owner->id, 'assigned_to' => $owner->id, 'deadline' => now()->addDays(5)]);

        return [$owner, $project, $first, $second];
    }

    public function test_resolve_flags_the_current_step_task_and_labels_later_steps(): void
    {
        [$owner, $project, $first, $second] = $this->fixture();
        $workflow = Workflow::factory()->active()->create([
            'project_id' => $project->id,
            'owner_id' => $owner->id,
            'steps' => [$this->step('one', $first->id, [$owner->id]), $this->step('two', $second->id, [$owner->id])],
        ]);

        $links = app(CycleLinkResolver::class)->resolve(collect([$first, $second]));

        $this->assertTrue($links[$first->id]['is_current']);
        $this->assertFalse($links[$second->id]['is_current']);
        $this->assertSame('two', $links[$second->id]['step_label']);
        $this->assertSame($workflow->id, $links[$first->id]['workflow_id']);
        $this->assertTrue(Task::find($first->id)->isCycleLocked());
        $this->assertFalse(Task::find($second->id)->isCycleLocked());
    }

    public function test_resolve_ignores_inactive_workflows(): void
    {
        [$owner, $project, $first] = $this->fixture();
        Workflow::factory()->cancelled()->create([
            'project_id' => $project->id,
            'owner_id' => $owner->id,
            'steps' => [$this->step('one', $first->id, [$owner->id])],
        ]);

        $this->assertSame([], app(CycleLinkResolver::class)->resolve(collect([$first])));
    }

    public function test_changing_the_current_task_assignee_mirrors_into_the_step_and_without_sync_suspends_it(): void
    {
        [$owner, $project, $first] = $this->fixture();
        $colleague = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $project->update(['member_ids' => [$colleague->id]]);
        $workflow = Workflow::factory()->active()->create([
            'project_id' => $project->id,
            'owner_id' => $owner->id,
            'steps' => [$this->step('one', $first->id, [$owner->id])],
        ]);

        CycleLinkResolver::withoutSync(fn() => Task::find($first->id)->update(['assigned_to' => $colleague->id]));
        $this->assertSame([$owner->id], Workflow::find($workflow->id)->steps[0]['assignee_user_ids']);

        Task::find($first->id)->update(['assigned_to' => $owner->id]);
        Task::find($first->id)->update(['assigned_to' => $colleague->id]);
        $this->assertSame([$colleague->id], array_map('intval', Workflow::find($workflow->id)->steps[0]['assignee_user_ids']));
    }
}
