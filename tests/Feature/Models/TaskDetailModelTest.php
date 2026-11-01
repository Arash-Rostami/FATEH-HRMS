<?php

namespace Tests\Feature\Models;

use App\Enums\TaskActivityType;
use App\Jobs\SyncTaskCalendarEventJob;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskDetail;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class TaskDetailModelTest extends TestCase
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

    public function test_creating_a_second_detail_for_the_same_task_throws(): void
    {
        $task = Task::factory()->create();
        TaskDetail::where('task_id', $task->id)->delete();
        TaskDetail::create(['task_id' => $task->id]);

        $this->expectException(RuntimeException::class);

        TaskDetail::create(['task_id' => $task->id]);
    }

    public function test_collaborators_cast_round_trips_array_through_in_memory_instance(): void
    {
        $detail = new TaskDetail();
        $detail->collaborators = [1, 2, 3];

        $this->assertSame([1, 2, 3], $detail->collaborators);
    }

    public function test_collaborators_cast_returns_null_when_attribute_unset(): void
    {
        $this->assertNull((new TaskDetail())->collaborators);
    }

    public function test_attachments_cast_round_trips_associative_array_through_in_memory_instance(): void
    {
        $detail = new TaskDetail();
        $detail->attachments = [['path' => 'a.pdf', 'name' => 'a'], ['path' => 'b.pdf', 'name' => 'b']];

        $this->assertSame([['path' => 'a.pdf', 'name' => 'a'], ['path' => 'b.pdf', 'name' => 'b']], $detail->attachments);
    }

    public function test_collaborators_cast_persists_json_and_rehydrates_from_database(): void
    {
        $task = Task::factory()->create();
        $detail = TaskDetail::create(['task_id' => $task->id, 'collaborators' => [10, 20, 30]]);

        $reloaded = TaskDetail::where('task_id', $task->id)->first();

        $this->assertSame([10, 20, 30], $reloaded->collaborators);
        $this->assertSame([10, 20, 30], $detail->collaborators);
    }

    public function test_collaborator_names_returns_user_names_for_collaborator_ids(): void
    {
        $u1 = User::factory()->create(['status' => 'active', 'name' => 'Alice']);
        $u2 = User::factory()->create(['status' => 'active', 'name' => 'Bob']);
        $task = Task::factory()->create();
        $detail = TaskDetail::create(['task_id' => $task->id, 'collaborators' => [$u1->id, $u2->id]]);

        $names = $detail->collaboratorNames();

        $this->assertInstanceOf(Collection::class, $names);
        $this->assertEqualsCanonicalizing(['Alice', 'Bob'], $names->values()->all());
    }

    public function test_collaborator_names_returns_empty_collection_when_collaborators_null(): void
    {
        $task = Task::factory()->create();
        $detail = TaskDetail::create(['task_id' => $task->id, 'collaborators' => null]);

        $this->assertInstanceOf(Collection::class, $detail->collaboratorNames());
        $this->assertCount(0, $detail->collaboratorNames());
    }

    public function test_collaborator_names_returns_empty_collection_when_collaborators_empty_array(): void
    {
        $task = Task::factory()->create();
        $detail = TaskDetail::create(['task_id' => $task->id, 'collaborators' => []]);

        $this->assertCount(0, $detail->collaboratorNames());
    }

    public function test_collaborator_names_skips_nonexistent_user_ids(): void
    {
        $user = User::factory()->create(['status' => 'active', 'name' => 'Carol']);
        $task = Task::factory()->create();
        $detail = TaskDetail::create(['task_id' => $task->id, 'collaborators' => [$user->id]]);
        DB::table('task_details')->where('id', $detail->id)->update(['collaborators' => json_encode([$user->id, 99999999])]);

        $names = $detail->fresh()->collaboratorNames();

        $this->assertSame(['Carol'], $names->values()->all());
        $this->assertCount(1, $names);
    }

    public function test_collaborator_names_reflects_updated_collaborators_list(): void
    {
        $u1 = User::factory()->create(['status' => 'active', 'name' => 'Dave']);
        $u2 = User::factory()->create(['status' => 'active', 'name' => 'Eve']);
        $task = Task::factory()->create();
        $detail = TaskDetail::create(['task_id' => $task->id, 'collaborators' => [$u1->id]]);

        $this->assertSame(['Dave'], $detail->collaboratorNames()->values()->all());

        $detail->collaborators = [$u1->id, $u2->id];
        $detail->save();

        $this->assertEqualsCanonicalizing(['Dave', 'Eve'], $detail->collaboratorNames()->values()->all());
    }

    public function test_fillable_accepts_all_declared_fields(): void
    {
        $task = Task::factory()->create(['project_id' => null]);
        $owner = User::factory()->create(['status' => 'active']);

        $detail = TaskDetail::create([
            'task_id' => $task->id,
            'department_id' => 'MA',
            'unit' => 'unit-x',
            'section' => 'sec-y',
            'project' => 'proj-z',
            'scheme' => 'scheme-w',
            'action_source_domain' => 'example.com',
            'action_source' => 'manual',
            'collaborators' => [$owner->id],
            'responsible_user_id' => $owner->id,
            'state' => 'active',
            'attachments' => [['path' => 'f.pdf']],
        ]);

        $this->assertSame('MA', $detail->department_id);
        $this->assertSame('unit-x', $detail->unit);
        $this->assertSame('sec-y', $detail->section);
        $this->assertSame('proj-z', $detail->project);
        $this->assertSame('scheme-w', $detail->scheme);
        $this->assertSame('example.com', $detail->action_source_domain);
        $this->assertSame('manual', $detail->action_source);
        $this->assertSame($owner->id, $detail->responsible_user_id);
        $this->assertSame('active', $detail->state);
        $this->assertSame([$owner->id], $detail->collaborators);
        $this->assertSame([['path' => 'f.pdf']], $detail->attachments);
    }

    public function test_checklist_cast_round_trips_through_the_database(): void
    {
        $task = Task::factory()->create();
        $detail = TaskDetail::create(['task_id' => $task->id, 'checklist' => [['text' => 'step 1', 'done' => false]]]);

        $this->assertEquals([['text' => 'step 1', 'done' => false]], $detail->fresh()->checklist);
    }

    public function test_checklist_cast_returns_null_when_unset(): void
    {
        $this->assertNull((new TaskDetail())->checklist);
    }

    public function test_saving_hook_mirrors_linked_project_name_into_the_project_string_column(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'name' => 'Renovation Plan', 'member_ids' => [], 'departments' => []]);
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);

        $detail = TaskDetail::create(['task_id' => $task->id]);

        $this->assertSame('Renovation Plan', $detail->fresh()->project, 'creating a detail on a project-linked task with no project value set must mirror the project name');
    }

    public function test_saving_hook_never_overwrites_an_already_set_project_string(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'name' => 'Renovation Plan', 'member_ids' => [], 'departments' => []]);
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);

        $detail = TaskDetail::create(['task_id' => $task->id, 'project' => 'manually chosen value']);
        $this->assertSame('manually chosen value', $detail->fresh()->project);

        $detail->update(['checklist' => [['text' => 'a', 'done' => false]]]);
        $this->assertSame('manually chosen value', $detail->fresh()->project, 'an unrelated save must not clobber a manually-set project value');
    }

    public function test_saving_hook_leaves_project_string_untouched_for_an_unfiled_task(): void
    {
        $task = Task::factory()->create(['project_id' => null]);

        $detail = TaskDetail::create(['task_id' => $task->id, 'project' => 'legacy free text']);

        $this->assertSame('legacy free text', $detail->fresh()->project);
    }

    public function test_collaborators_change_logs_assignment_activity_with_added_and_removed_diff(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $u1 = User::factory()->create(['status' => 'active']);
        $u2 = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($owner)->create();
        $detail = TaskDetail::create(['task_id' => $task->id, 'collaborators' => [$u1->id]]);
        $this->actingAs($owner);

        TaskDetail::find($detail->id)->update(['collaborators' => [$u2->id]]);

        $reply = $task->replies()->reorder()->latest('id')->first();
        $this->assertNotNull($reply);
        $this->assertSame(TaskActivityType::Assignment, $reply->type);
        $this->assertSame([$u2->id], $reply->payload['added']);
        $this->assertSame([$u1->id], $reply->payload['removed']);
    }

    public function test_collaborators_change_does_not_log_activity_on_detail_creation(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $collaborator = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($owner)->create();

        TaskDetail::create(['task_id' => $task->id, 'collaborators' => [$collaborator->id]]);

        $this->assertSame(1, $task->replies()->count(), 'seeding collaborators on a brand-new detail row must not itself log an activity entry (only the creation history row exists)');
    }

    public function test_collaborators_change_dispatches_calendar_sync(): void
    {
        Queue::fake();
        $owner = User::factory()->create(['status' => 'active']);
        $collaborator = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($owner)->create();
        $detail = TaskDetail::create(['task_id' => $task->id, 'collaborators' => []]);
        $this->actingAs($owner);
        Queue::fake();

        TaskDetail::find($detail->id)->update(['collaborators' => [$collaborator->id]]);

        Queue::assertPushed(SyncTaskCalendarEventJob::class, 1);
    }

    public function test_responsible_user_change_logs_responsible_change_activity_and_dispatches_calendar_sync(): void
    {
        Queue::fake();
        $owner = User::factory()->create(['status' => 'active']);
        $oldResponsible = User::factory()->create(['status' => 'active']);
        $newResponsible = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($owner)->create();
        $detail = TaskDetail::create(['task_id' => $task->id, 'responsible_user_id' => $oldResponsible->id]);
        $this->actingAs($owner);
        Queue::fake();

        TaskDetail::find($detail->id)->update(['responsible_user_id' => $newResponsible->id]);

        $reply = $task->replies()->reorder()->latest('id')->first();
        $this->assertNotNull($reply);
        $this->assertSame(TaskActivityType::ResponsibleChange, $reply->type);
        $this->assertSame($oldResponsible->id, $reply->payload['from']);
        $this->assertSame($newResponsible->id, $reply->payload['to']);
        Queue::assertPushed(SyncTaskCalendarEventJob::class, 1);
    }

    public function test_responsible_user_id_set_at_detail_creation_does_not_log_activity(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $responsible = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($owner)->create();

        TaskDetail::create(['task_id' => $task->id, 'responsible_user_id' => $responsible->id]);

        $this->assertSame(1, $task->replies()->count(), 'seeding responsible_user_id on a brand-new detail row must not itself log an activity entry (only the creation history row exists)');
    }

    public function test_department_change_logs_department_change_activity_without_calendar_sync(): void
    {
        Queue::fake();
        $owner = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($owner)->create();
        $detail = TaskDetail::create(['task_id' => $task->id, 'department_id' => 'MA']);
        $this->actingAs($owner);
        Queue::fake();

        TaskDetail::find($detail->id)->update(['department_id' => 'FN']);

        $reply = $task->replies()->reorder()->latest('id')->first();
        $this->assertNotNull($reply);
        $this->assertSame(TaskActivityType::DepartmentChange, $reply->type);
        $this->assertSame('MA', $reply->payload['from']);
        $this->assertSame('FN', $reply->payload['to']);
        Queue::assertNotPushed(SyncTaskCalendarEventJob::class);
    }

    public function test_state_change_logs_state_change_activity(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($owner)->create();
        $detail = TaskDetail::create(['task_id' => $task->id, 'state' => 'extension']);
        $this->actingAs($owner);

        TaskDetail::find($detail->id)->update(['state' => 'completion']);

        $reply = $task->replies()->reorder()->latest('id')->first();
        $this->assertNotNull($reply);
        $this->assertSame(TaskActivityType::StateChange, $reply->type);
        $this->assertSame('extension', $reply->payload['from']);
        $this->assertSame('completion', $reply->payload['to']);
    }

    public function test_state_set_at_detail_creation_does_not_log_activity(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($owner)->create();

        TaskDetail::create(['task_id' => $task->id, 'state' => 'extension']);

        $this->assertSame(1, $task->replies()->count(), 'seeding state on a brand-new detail row must not itself log an activity entry (only the creation history row exists)');
    }

    public function test_malformed_meta_keys_are_rejected_by_the_task_form_validation_closure(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        foreach ([
            ['UpperCamel' => 'مقدار'],
            ['bad key' => 'مقدار'],
            ['bad.key' => 'مقدار'],
            ['bad"key' => 'مقدار'],
        ] as $meta) {
            \Livewire\Livewire::actingAs($user)
                ->test(\App\Livewire\Dashboard\TaskBoard\Main::class)
                ->set('form.newTitle', 'Tinker meta key probe')
                ->set('form.meta', $meta)
                ->call('createTask')
                ->assertHasErrors(['form.meta']);
        }

        $this->assertSame(0, Task::where('title', 'Tinker meta key probe')->count());
    }

    public function test_valid_meta_keys_pass_task_form_validation_and_persist_to_the_detail(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        \Livewire\Livewire::actingAs($user)
            ->test(\App\Livewire\Dashboard\TaskBoard\Main::class)
            ->set('form.newTitle', 'Tinker valid meta key probe')
            ->set('form.meta', ['cost_center' => 'CC-9'])
            ->call('createTask')
            ->assertHasNoErrors()
            ->assertOk();

        $task = Task::where('title', 'Tinker valid meta key probe')->firstOrFail();

        $this->assertSame(['cost_center' => 'CC-9'], $task->detail->meta);
    }

    public function test_collaborators_cast_stores_and_reads_numbers_even_when_given_text_ids(): void
    {
        $detail = new TaskDetail();
        $detail->collaborators = ['5', '7', '5'];

        $this->assertSame('[5,7]', $detail->getAttributes()['collaborators']);
        $this->assertSame([5, 7], $detail->collaborators);

        $legacy = (new TaskDetail())->setRawAttributes(['collaborators' => '["9","3"]']);
        $this->assertSame([9, 3], $legacy->collaborators);
    }
}
