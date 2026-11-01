<?php

namespace Tests\Feature\Models;

use App\Enums\TaskActivityType;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Reply;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WorkflowModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Cache::flush();
        DB::beginTransaction();
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

    public function test_sanitize_steps_strips_runtime_only_keys_and_filters_phantom_assignees(): void
    {
        $real = User::factory()->create(['status' => 'active']);

        $result = Workflow::sanitizeSteps([
            [
                'label' => 'مرحله اول',
                'assignee_user_ids' => [$real->id, 999999999],
                'task_id' => 42,
                'activated_at' => now()->toISOString(),
            ],
        ]);

        $this->assertArrayNotHasKey('task_id', $result[0]);
        $this->assertArrayNotHasKey('activated_at', $result[0]);
        $this->assertSame([$real->id], $result[0]['assignee_user_ids']);
    }

    public function test_sanitize_steps_dedupes_a_submitted_id_repeated_in_a_tampered_payload(): void
    {
        $real = User::factory()->create(['status' => 'active']);

        $result = Workflow::sanitizeSteps([
            ['label' => 'مرحله', 'assignee_user_ids' => [$real->id, $real->id]],
        ]);

        $this->assertSame([$real->id], $result[0]['assignee_user_ids']);
    }

    public function test_sanitize_steps_is_null_safe_on_explicit_null_assignee_user_ids(): void
    {
        $result = Workflow::sanitizeSteps([
            ['label' => 'بدون مسئول', 'assignee_user_ids' => null],
        ]);

        $this->assertSame([], $result[0]['assignee_user_ids']);
    }

    public function test_sanitize_steps_reindexes_the_array(): void
    {
        $steps = [
            2 => ['label' => 'دوم', 'assignee_user_ids' => []],
            5 => ['label' => 'پنجم', 'assignee_user_ids' => []],
        ];

        $result = Workflow::sanitizeSteps($steps);

        $this->assertSame([0, 1], array_keys($result));
        $this->assertSame('دوم', $result[0]['label']);
        $this->assertSame('پنجم', $result[1]['label']);
    }

    public function test_sanitize_steps_preserves_the_submitted_assignee_order_so_the_driver_is_the_owners_first_pick(): void
    {
        $firstCreated = User::factory()->create(['status' => 'active']);
        $secondCreated = User::factory()->create(['status' => 'active']);

        $result = Workflow::sanitizeSteps([
            ['label' => 'مرحله', 'assignee_user_ids' => [$secondCreated->id, $firstCreated->id]],
        ]);

        $this->assertSame([$secondCreated->id, $firstCreated->id], $result[0]['assignee_user_ids']);
    }

    public function test_filter_assignees_preserves_the_submitted_order_not_the_database_lookup_order(): void
    {
        $firstCreated = User::factory()->create(['status' => 'active']);
        $secondCreated = User::factory()->create(['status' => 'active']);

        $filtered = Workflow::filterAssignees([$secondCreated->id, $firstCreated->id], null);

        $this->assertSame([$secondCreated->id, $firstCreated->id], $filtered);
    }

    public function test_filter_assignees_excludes_users_not_visible_to_the_project(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $member = User::factory()->create(['status' => 'active']);
        $deptUser = User::factory()->create(['status' => 'active']);
        Profile::factory()->for($deptUser)->create(['department_id' => 'MA']);
        $stranger = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$member->id], 'departments' => ['MA']]);

        $filtered = Workflow::filterAssignees([$owner->id, $member->id, $deptUser->id, $stranger->id], $project);

        sort($filtered);
        $expected = [$owner->id, $member->id, $deptUser->id];
        sort($expected);
        $this->assertSame($expected, $filtered);
    }

    public function test_filter_assignees_without_a_project_skips_visibility_filtering(): void
    {
        $users = User::factory()->count(3)->create(['status' => 'active']);

        $filtered = Workflow::filterAssignees($users->pluck('id')->all(), null);

        $this->assertSame($users->pluck('id')->sort()->values()->all(), collect($filtered)->sort()->values()->all());
    }

    public function test_filter_assignees_dedupes_ids(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $filtered = Workflow::filterAssignees([$user->id, $user->id, $user->id], null);

        $this->assertSame([$user->id], $filtered);
    }

    public function test_filter_assignees_caps_at_the_default_max_of_twenty(): void
    {
        $users = User::factory()->count(25)->create(['status' => 'active']);

        $filtered = Workflow::filterAssignees($users->pluck('id')->all(), null);

        $this->assertCount(20, $filtered);
    }

    public function test_filter_assignees_honors_an_explicit_max(): void
    {
        $users = User::factory()->count(5)->create(['status' => 'active']);

        $filtered = Workflow::filterAssignees($users->pluck('id')->all(), null, max: 3);

        $this->assertCount(3, $filtered);
    }

    public function test_resolve_user_names_reads_names_from_the_warm_options_cache(): void
    {
        $sara = User::factory()->create(['status' => 'active', 'name' => 'سارا احمدی']);
        $reza = User::factory()->create(['status' => 'active', 'name' => 'رضا کریمی']);

        $resolved = Workflow::resolveUserNames([$sara->id, $reza->id, 999999999]);

        $this->assertSame('سارا احمدی', $resolved[$sara->id]);
        $this->assertSame('رضا کریمی', $resolved[$reza->id]);
        $this->assertCount(2, $resolved, 'a nonexistent id resolves to nothing, same as the old whereIn lookup');
        $this->assertSame([], Workflow::resolveUserNames([]));

        User::find($sara->id)->update(['name' => 'سارا کریمی']);

        $this->assertSame('سارا کریمی', Workflow::resolveUserNames([$sara->id])[$sara->id], 'a User save bumps the options-cache version, so the next resolve reads the fresh name');
    }

    public function test_resolve_user_names_ignores_null_actor_ids_from_the_step_log(): void
    {
        $sara = User::factory()->create(['status' => 'active', 'name' => 'سارا احمدی']);

        // recordEvent() takes ?int $actorId — a system/console action stores completed_by: null,
        // and renderStepLog passes the whole column through. array_flip() throws on null, the old whereIn ignored it.
        $resolved = Workflow::resolveUserNames([null, $sara->id, null]);

        $this->assertSame('سارا احمدی', $resolved[$sara->id]);
        $this->assertCount(1, $resolved);
        $this->assertSame([], Workflow::resolveUserNames([null]));
    }

    public function test_scope_templates_excludes_project_scoped_instances(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $template = Workflow::factory()->asTemplate()->create(['owner_id' => $owner->id]);
        $instance = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id]);

        $ids = Workflow::templates()->pluck('id')->all();

        $this->assertContains($template->id, $ids);
        $this->assertNotContains($instance->id, $ids);
    }

    public function test_scope_for_project_only_returns_that_projects_rows(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $projectA = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $projectB = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $inA = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $projectA->id]);
        $inB = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $projectB->id]);

        $ids = Workflow::forProject($projectA->id)->pluck('id')->all();

        $this->assertContains($inA->id, $ids);
        $this->assertNotContains($inB->id, $ids);
    }

    public function test_current_step_returns_the_indexed_step_or_null(): void
    {
        $workflow = new Workflow([
            'steps' => [
                ['label' => 'اول', 'assignee_user_ids' => [1, 2]],
                ['label' => 'دوم', 'assignee_user_ids' => [3]],
            ],
            'current_step' => 1,
        ]);

        $this->assertSame('دوم', $workflow->currentStep()['label']);

        $workflow->current_step = null;
        $this->assertNull($workflow->currentStep());
    }

    public function test_is_assignee_checks_the_current_step_only(): void
    {
        $workflow = new Workflow([
            'steps' => [
                ['label' => 'اول', 'assignee_user_ids' => [1, 2]],
                ['label' => 'دوم', 'assignee_user_ids' => [3]],
            ],
            'current_step' => 0,
        ]);

        $this->assertTrue($workflow->isAssignee(1));
        $this->assertFalse($workflow->isAssignee(3));
    }

    public function test_status_helpers_reflect_the_status_constants(): void
    {
        $map = [
            Workflow::STATUS_DRAFT => 'isDraft',
            Workflow::STATUS_ACTIVE => 'isActive',
            Workflow::STATUS_COMPLETED => 'isCompleted',
            Workflow::STATUS_CANCELLED => 'isCancelled',
        ];

        foreach ($map as $status => $trueMethod) {
            $workflow = new Workflow(['status' => $status]);

            foreach (array_values($map) as $method) {
                $this->assertSame($method === $trueMethod, $workflow->$method());
            }
        }
    }

    public function test_booted_does_not_trigger_the_transition_handler_on_an_unrelated_field_change(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id]);

        Workflow::find($workflow->id)->update(['name' => 'renamed cycle']);

        $this->assertSame(0, Reply::where('repliable_type', Project::class)
            ->where('repliable_id', $project->id)
            ->where('type', TaskActivityType::WorkflowStep->value)
            ->count());
    }

    public function test_booted_triggers_the_transition_handler_when_status_changes(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id]);

        Workflow::find($workflow->id)->update(['status' => Workflow::STATUS_CANCELLED]);

        $this->assertSame(1, Reply::where('repliable_type', Project::class)
            ->where('repliable_id', $project->id)
            ->where('type', TaskActivityType::WorkflowStep->value)
            ->count());
    }
}
