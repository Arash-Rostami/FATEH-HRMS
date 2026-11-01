<?php

namespace Tests\Feature\Models;

use Illuminate\Validation\ValidationException;
use App\Models\Workflow;
use App\Models\TaskDetail;
use App\Models\Department;
use App\Enums\TaskActivityType;
use App\Models\Profile;
use App\Models\Project;
use App\Livewire\Dashboard\Ths\Actions\AssignTicketAction;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProjectModelTest extends TestCase
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

    public function test_generate_slug_is_deterministic_and_persian_safe(): void
    {
        $slug = Project::generateSlug('پروژهٔ من');

        $this->assertNotSame('', $slug);
        $this->assertStringNotContainsString(' ', $slug);
    }

    public function test_generate_slug_falls_back_to_project_prefix_for_empty_input(): void
    {
        $slug = Project::generateSlug('');

        $this->assertStringStartsWith('project-', $slug);
    }

    public function test_owner_is_always_visible_to_themselves(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $this->assertTrue($project->isVisibleTo($owner));
        $this->assertTrue(Project::visibleTo($owner)->whereKey($project->id)->exists());
    }

    public function test_project_with_empty_audience_is_private_to_a_stranger(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $this->assertFalse($project->isVisibleTo($stranger));
        $this->assertFalse(Project::visibleTo($stranger)->whereKey($project->id)->exists());
    }

    public function test_explicit_member_id_grants_visibility(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $member = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$member->id], 'departments' => []]);

        $this->assertTrue($project->isVisibleTo($member));
        $this->assertTrue(Project::visibleTo($member)->whereKey($project->id)->exists());
    }

    public function test_matching_department_grants_visibility(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $deptUser = User::factory()->create(['status' => 'active']);
        Profile::factory()->for($deptUser)->create(['department_id' => 'MA']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => ['MA']]);

        $deptUser->refresh();
        $this->assertTrue($project->isVisibleTo($deptUser));
    }

    public function test_creating_a_project_dispatches_channel_membership_sync(): void
    {
        Queue::fake();
        $owner = User::factory()->create(['status' => 'active']);

        Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        Queue::assertPushed(\App\Jobs\SyncProjectChannelMembershipJob::class);
    }

    public function test_member_ids_change_on_update_writes_an_audit_reply(): void
    {
        Queue::fake();
        $owner = User::factory()->create(['status' => 'active']);
        $newMember = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $this->actingAs($owner);
        $project = $project->fresh();
        $project->update(['member_ids' => [$newMember->id]]);

        $reply = $project->replies()->reorder()->latest('id')->first();
        $this->assertNotNull($reply);
        $this->assertSame([$newMember->id], $reply->payload['added']);
        $this->assertSame([], $reply->payload['removed']);
    }

    public function test_rename_alone_does_not_dispatch_channel_membership_sync(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        Queue::fake();
        $project = $project->fresh();
        $project->update(['name' => 'renamed project']);

        Queue::assertNotPushed(\App\Jobs\SyncProjectChannelMembershipJob::class);
    }

    public function test_shrinking_the_project_deadline_setting_never_mutates_existing_task_deadlines(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['deadline' => now()->addDays(30)->toDateString()],
        ]);
        $task = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('todo')->create(['deadline' => now()->addDays(5)]);
        $original = $task->deadline->format('Y-m-d H:i:s');

        $project->fresh()->update(['settings' => ['deadline' => now()->addDays(1)->toDateString()]]);

        $this->assertSame($original, $task->fresh()->deadline->format('Y-m-d H:i:s'));
    }

    private function lifeUser(array $attrs = []): User
    {
        return User::factory()->create(['status' => 'active', 'role' => 'user', ...$attrs]);
    }

    private function lifeStep(array $assignees, array $extra = []): array
    {
        return ['label' => 'step', 'assignee_user_ids' => $assignees, 'type' => 'action', 'reject_to_step' => null, 'populate_task' => false, ...$extra];
    }

    public function test_creation_writes_one_settings_row_naming_the_owner(): void
    {
        $owner = $this->lifeUser(['name' => 'Creator Name']);
        $this->actingAs($owner);

        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $rows = $project->replies()->reorder()->where('type', TaskActivityType::SettingsChange->value)->get();
        $this->assertCount(1, $rows);
        $this->assertTrue($rows[0]->payload['created']);
        $this->assertSame('Creator Name', $rows[0]->payload['owner_name']);
    }

    public function test_owner_transfer_keeps_the_old_owner_as_member_and_moves_open_cycles(): void
    {
        $owner = $this->lifeUser();
        $heir = $this->lifeUser();
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$heir->id], 'departments' => []]);
        $open = Workflow::factory()->active()->create(['project_id' => $project->id, 'owner_id' => $owner->id, 'steps' => [$this->lifeStep([$heir->id])]]);
        $done = Workflow::factory()->completed()->create(['project_id' => $project->id, 'owner_id' => $owner->id, 'steps' => [$this->lifeStep([$heir->id])]]);

        Project::find($project->id)->update(['owner_id' => $heir->id]);

        $fresh = Project::find($project->id);
        $this->assertSame($heir->id, $fresh->owner_id);
        $this->assertContains($owner->id, $fresh->member_ids);
        $this->assertSame($heir->id, Workflow::find($open->id)->owner_id);
        $this->assertSame($owner->id, Workflow::find($done->id)->owner_id);
    }

    public function test_owner_transfer_requires_an_active_non_guest_user(): void
    {
        $owner = $this->lifeUser();
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        foreach ([$this->lifeUser(['status' => 'inactive']), $this->lifeUser(['type' => 'guest'])] as $bad) {
            try {
                Project::find($project->id)->update(['owner_id' => $bad->id]);
                $this->fail('inactive or guest owner must be refused');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('owner_id', $e->errors());
            }
        }

        $this->assertSame($owner->id, Project::find($project->id)->owner_id);
    }

    public function test_removing_a_member_releases_open_tasks_collaborations_and_cycle_steps_but_keeps_done_work(): void
    {
        $owner = $this->lifeUser();
        $leaver = $this->lifeUser();
        $stayer = $this->lifeUser();
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$leaver->id, $stayer->id], 'departments' => []]);
        $assigned = Task::factory()->forProject($project)->create(['user_id' => $owner->id, 'assigned_to' => $leaver->id, 'status' => 'todo', 'deadline' => now()->addDays(9)]);
        $finished = Task::factory()->forProject($project)->create(['user_id' => $owner->id, 'assigned_to' => $leaver->id, 'status' => 'done', 'approved_at' => now(), 'deadline' => now()->addDays(9)]);
        $helped = Task::factory()->forProject($project)->create(['user_id' => $owner->id, 'assigned_to' => $stayer->id, 'status' => 'todo', 'deadline' => now()->addDays(9)]);
        TaskDetail::factory()->create(['task_id' => $helped->id, 'collaborators' => [$leaver->id, $stayer->id], 'responsible_user_id' => $leaver->id]);
        $draft = Workflow::factory()->create(['project_id' => $project->id, 'owner_id' => $owner->id, 'steps' => [$this->lifeStep([$leaver->id, $stayer->id])]]);

        Project::find($project->id)->update(['member_ids' => [$stayer->id]]);

        $this->assertNull(Task::find($assigned->id)->assigned_to);
        $this->assertSame($leaver->id, Task::find($finished->id)->assigned_to);
        $detail = TaskDetail::where('task_id', $helped->id)->first();
        $this->assertSame([$stayer->id], $detail->collaborators);
        $this->assertNull($detail->responsible_user_id);
        $this->assertSame([$stayer->id], Workflow::find($draft->id)->steps[0]['assignee_user_ids']);
    }

    public function test_deactivating_a_user_releases_both_a_project_task_and_an_in_progress_ticket_with_its_mirror(): void
    {
        $owner = $this->lifeUser();
        $leaver = $this->lifeUser();
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$leaver->id], 'departments' => []]);
        $task = Task::factory()->forProject($project)->create(['user_id' => $owner->id, 'assigned_to' => $leaver->id, 'status' => 'todo', 'deadline' => now()->addDays(9)]);
        $ticket = Ticket::factory()->create(['status' => 'in-progress', 'assigned_to' => $leaver->id]);
        app(AssignTicketAction::class)->syncForAdmin($ticket->fresh(), $leaver->id);
        $mirror = Task::where('ticket_id', $ticket->id)->first();
        $this->assertNotNull($mirror);

        User::find($leaver->id)->update(['status' => 'inactive']);

        $this->assertNull(Task::find($task->id)->assigned_to);
        $fresh = Ticket::find($ticket->id);
        $this->assertNull($fresh->assigned_to);
        $this->assertSame('open', $fresh->status);
        $this->assertNull(Task::where('ticket_id', $ticket->id)->first());
    }

    public function test_ensure_active_owner_hands_over_to_the_highest_ranking_active_member(): void
    {
        $owner = $this->lifeUser();
        $junior = $this->lifeUser();
        $senior = $this->lifeUser();
        $gone = $this->lifeUser(['status' => 'inactive']);
        $dept = Department::factory()->create();
        Profile::factory()->create(['user_id' => $junior->id, 'department_id' => $dept->code, 'position' => 'employee']);
        Profile::factory()->create(['user_id' => $senior->id, 'department_id' => $dept->code, 'position' => 'manager']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$junior->id, $senior->id, $gone->id], 'departments' => []]);

        Project::find($project->id)->ensureActiveOwner();
        $this->assertSame($owner->id, Project::find($project->id)->owner_id);

        $owner->forceFill(['status' => 'inactive'])->saveQuietly();
        Project::find($project->id)->ensureActiveOwner();

        $this->assertSame($senior->id, Project::find($project->id)->owner_id);
    }

    public function test_ensure_active_owner_keeps_the_owner_when_nobody_can_take_over(): void
    {
        $owner = $this->lifeUser();
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $owner->forceFill(['status' => 'inactive'])->saveQuietly();

        Project::find($project->id)->ensureActiveOwner();

        $this->assertSame($owner->id, Project::find($project->id)->owner_id);
    }

    public function test_deleting_a_project_cancels_its_open_cycles_and_restoring_revives_them_as_drafts(): void
    {
        $owner = $this->lifeUser();
        $member = $this->lifeUser();
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$member->id], 'departments' => []]);
        $steps = [$this->lifeStep([$member->id], ['populate_task' => true, 'task_title' => 'work']), $this->lifeStep([$member->id])];
        $active = Workflow::factory()->active()->create(['project_id' => $project->id, 'owner_id' => $owner->id, 'steps' => $steps]);
        $draft = Workflow::factory()->create(['project_id' => $project->id, 'owner_id' => $owner->id, 'steps' => $steps]);
        $finished = Workflow::factory()->completed()->create(['project_id' => $project->id, 'owner_id' => $owner->id, 'steps' => $steps]);

        Project::find($project->id)->delete();

        foreach ([$active, $draft] as $wf) {
            $fresh = Workflow::find($wf->id);
            $this->assertSame(Workflow::STATUS_CANCELLED, $fresh->status);
            $this->assertSame('project_deleted', collect($fresh->step_log)->last()['event']);
        }
        $this->assertSame(Workflow::STATUS_COMPLETED, Workflow::find($finished->id)->status);

        Project::withTrashed()->find($project->id)->restore();

        foreach ([$active, $draft] as $wf) {
            $fresh = Workflow::find($wf->id);
            $this->assertSame(Workflow::STATUS_DRAFT, $fresh->status);
            $this->assertNull($fresh->current_step);
            $this->assertArrayNotHasKey('task_id', $fresh->steps[0]);
        }
        $this->assertSame(Workflow::STATUS_COMPLETED, Workflow::find($finished->id)->status);
    }
}
