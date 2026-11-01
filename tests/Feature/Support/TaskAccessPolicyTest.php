<?php

namespace Tests\Feature\Support;

use App\Models\Workflow;
use App\Models\TaskDetail;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\TaskAccessPolicy;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TaskAccessPolicyTest extends TestCase
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

    public function test_can_change_status_returns_true_for_assignee(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->create(['user_id' => $owner->id, 'assigned_to' => $assignee->id]);

        $this->assertTrue(TaskAccessPolicy::canChangeStatus($task, $assignee));
    }

    public function test_can_change_status_returns_true_for_unassigned_owner(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->create(['user_id' => $owner->id, 'assigned_to' => null]);

        $this->assertTrue(TaskAccessPolicy::canChangeStatus($task, $owner));
    }

    public function test_can_change_status_returns_false_for_stranger_and_null_user(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->create(['user_id' => $owner->id, 'assigned_to' => $assignee->id]);

        $this->assertFalse(TaskAccessPolicy::canChangeStatus($task, $stranger));
        $this->assertFalse(TaskAccessPolicy::canChangeStatus($task, null));
    }

    public function test_can_delete_returns_true_only_for_creator(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->create(['user_id' => $owner->id, 'assigned_to' => $assignee->id]);

        $this->assertTrue(TaskAccessPolicy::canDelete($task, $owner));
        $this->assertFalse(TaskAccessPolicy::canDelete($task, $assignee));
        $this->assertFalse(TaskAccessPolicy::canDelete($task, null));
    }

    public function test_can_undo_assignment_returns_true_only_for_creator_with_assignee_that_is_not_self(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->create(['user_id' => $owner->id, 'assigned_to' => $assignee->id]);

        $this->assertTrue(TaskAccessPolicy::canUndoAssignment($task, $owner));
    }

    public function test_can_undo_assignment_returns_false_when_not_creator(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->create(['user_id' => $owner->id, 'assigned_to' => $assignee->id]);

        $this->assertFalse(TaskAccessPolicy::canUndoAssignment($task, $assignee));
    }

    public function test_can_undo_assignment_returns_false_when_unassigned(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->create(['user_id' => $owner->id, 'assigned_to' => null]);

        $this->assertFalse(TaskAccessPolicy::canUndoAssignment($task, $owner));
    }

    public function test_can_undo_assignment_returns_false_when_assignee_is_creator(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->create(['user_id' => $owner->id, 'assigned_to' => $owner->id]);

        $this->assertFalse(TaskAccessPolicy::canUndoAssignment($task, $owner));
    }

    public function test_can_undo_assignment_returns_false_for_null_user(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->create(['user_id' => $owner->id, 'assigned_to' => $assignee->id]);

        $this->assertFalse(TaskAccessPolicy::canUndoAssignment($task, null));
    }

    public function test_can_approve_returns_true_only_for_project_owner(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $member = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$member->id], 'departments' => []]);
        $task = Task::factory()->create(['project_id' => $project->id, 'user_id' => $member->id, 'assigned_to' => null]);

        $this->assertTrue(TaskAccessPolicy::canApprove($task, $owner));
        $this->assertFalse(TaskAccessPolicy::canApprove($task, $member));
    }

    public function test_can_approve_returns_false_when_task_has_no_project(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->create(['project_id' => null, 'user_id' => $owner->id, 'assigned_to' => null]);

        $this->assertFalse(TaskAccessPolicy::canApprove($task, $owner));
    }

    public function test_can_approve_returns_false_for_null_user(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $task = Task::factory()->create(['project_id' => $project->id, 'user_id' => $owner->id, 'assigned_to' => null]);

        $this->assertFalse(TaskAccessPolicy::canApprove($task, null));
    }

    private function user(array $attrs = []): User
    {
        return User::factory()->create(['status' => 'active', 'role' => 'user', ...$attrs]);
    }

    private function projectTask(User $owner, array $project = [], array $task = [], array $detail = []): Task
    {
        $p = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => [], ...$project]);
        $t = Task::factory()->create(['project_id' => $p->id, 'user_id' => $owner->id, 'assigned_to' => null, ...$task]);

        if ($detail) {
            TaskDetail::factory()->create(['task_id' => $t->id, ...$detail]);
        }

        return Task::find($t->id);
    }

    public function test_view_and_reply_include_project_members_and_the_accountable_but_not_strangers(): void
    {
        $owner = $this->user();
        $member = $this->user();
        $accountable = $this->user();
        $stranger = $this->user();
        $task = $this->projectTask($owner, ['member_ids' => [$member->id]], [], ['responsible_user_id' => $accountable->id, 'collaborators' => []]);

        foreach ([$member, $accountable, $owner] as $u) {
            $this->assertTrue(TaskAccessPolicy::canView($task, $u));
            $this->assertTrue(TaskAccessPolicy::canReply($task, $u));
        }

        $this->assertFalse(TaskAccessPolicy::canView($task, $stranger));
        $this->assertFalse(TaskAccessPolicy::canReply($task, $stranger));
    }

    public function test_collaborators_and_project_owner_can_change_status_and_edit_but_plain_members_cannot(): void
    {
        $owner = $this->user();
        $collab = $this->user();
        $member = $this->user();
        $assignee = $this->user();
        $task = $this->projectTask($owner, ['member_ids' => [$member->id, $collab->id, $assignee->id]], ['assigned_to' => $assignee->id], ['collaborators' => [$collab->id]]);

        foreach ([$collab, $owner, $assignee] as $u) {
            $this->assertTrue(TaskAccessPolicy::canChangeStatus($task, $u));
            $this->assertTrue(TaskAccessPolicy::canEdit($task, $u));
        }

        $this->assertFalse(TaskAccessPolicy::canEdit($task, $member));
    }

    public function test_move_project_belongs_to_creator_and_project_owner_not_the_assignee(): void
    {
        $owner = $this->user();
        $creator = $this->user();
        $assignee = $this->user();
        $task = $this->projectTask($owner, ['member_ids' => [$creator->id, $assignee->id]], ['user_id' => $creator->id, 'assigned_to' => $assignee->id]);

        $this->assertFalse(TaskAccessPolicy::canMoveProject($task, $assignee));
        $this->assertTrue(TaskAccessPolicy::canMoveProject($task, $creator));
        $this->assertTrue(TaskAccessPolicy::canMoveProject($task, $owner));
    }

    public function test_current_step_cycle_task_cannot_be_deleted_or_moved(): void
    {
        $owner = $this->user();
        $task = $this->projectTask($owner);
        Workflow::factory()->active()->create([
            'project_id' => $task->project_id,
            'owner_id' => $owner->id,
            'steps' => [['label' => 'a', 'assignee_user_ids' => [$owner->id], 'type' => 'action', 'task_id' => $task->id]],
        ]);
        $task = Task::find($task->id);

        $this->assertFalse(TaskAccessPolicy::canDelete($task, $owner));
        $this->assertFalse(TaskAccessPolicy::canMoveProject($task, $owner));
    }

    public function test_ticket_tasks_refuse_status_change_edit_and_delete(): void
    {
        $owner = $this->user();
        $task = Task::factory()->create(['user_id' => $owner->id, 'assigned_to' => $owner->id]);
        $task->ticket_id = 999999;

        $this->assertFalse(TaskAccessPolicy::canEdit($task, $owner));
        $this->assertFalse(TaskAccessPolicy::canChangeStatus($task, $owner));
        $this->assertFalse(TaskAccessPolicy::canDelete($task, $owner));
    }

    public function test_archive_requires_done_and_not_pending_approval(): void
    {
        $owner = $this->user();
        $task = $this->projectTask($owner, ['settings' => ['requires_approval' => true]], ['status' => 'in-progress']);

        $this->assertFalse(TaskAccessPolicy::canArchive($task, $owner));

        $task->forceFill(['status' => 'done', 'approved_at' => null])->saveQuietly();
        $this->assertFalse(TaskAccessPolicy::canArchive(Task::find($task->id), $owner));

        $task->forceFill(['approved_at' => now()])->saveQuietly();
        $this->assertTrue(TaskAccessPolicy::canArchive(Task::find($task->id), $owner));
    }

    public function test_accountable_approves_only_when_owner_is_inactive_or_missing(): void
    {
        $owner = $this->user();
        $accountable = $this->user();
        $task = $this->projectTask($owner, [], [], ['responsible_user_id' => $accountable->id]);

        $this->assertFalse(TaskAccessPolicy::canApprove($task, $accountable));

        $owner->forceFill(['status' => 'inactive'])->saveQuietly();
        $this->assertTrue(TaskAccessPolicy::canApprove(Task::find($task->id), $accountable));
        $this->assertFalse(TaskAccessPolicy::canApprove(Task::find($task->id), $this->user()));
    }

    public function test_reassign_by_accountable_only_to_self(): void
    {
        $owner = $this->user();
        $accountable = $this->user();
        $other = $this->user();
        $task = $this->projectTask($owner, [], ['assigned_to' => $other->id], ['responsible_user_id' => $accountable->id]);

        $this->assertTrue(TaskAccessPolicy::canReassign($task, $accountable, $accountable->id));
        $this->assertFalse(TaskAccessPolicy::canReassign($task, $accountable, $other->id));
        $this->assertTrue(TaskAccessPolicy::canReassign($task, $other, $accountable->id));
    }

    public function test_assignable_ids_filter_inactive_guest_and_non_roster_users(): void
    {
        $owner = $this->user();
        $member = $this->user();
        $inactive = $this->user(['status' => 'inactive']);
        $guest = $this->user(['type' => 'guest']);
        $outsider = $this->user();
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$member->id, $inactive->id, $guest->id], 'departments' => []]);

        $ids = TaskAccessPolicy::assignableIds([$owner->id, $member->id, $inactive->id, $guest->id, $outsider->id, $member->id], $project);

        $this->assertEqualsCanonicalizing([$owner->id, $member->id], $ids);
        $this->assertContains($outsider->id, TaskAccessPolicy::assignableIds([$outsider->id]));
        $this->assertNotContains($inactive->id, TaskAccessPolicy::assignableIds([$inactive->id]));
    }
}
