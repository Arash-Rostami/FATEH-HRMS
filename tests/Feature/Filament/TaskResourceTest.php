<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\TaskResource\Pages\CreateTask;
use App\Filament\Resources\TaskResource\Pages\EditTask;
use App\Filament\Resources\TaskResource\Pages\ListTasks;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Morilog\Jalali\Jalalian;
use Tests\TestCase;

class TaskResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Storage::fake('public');
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

    private function developer(): User
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        Permission::create(['user_id' => $user->id, 'is_super_admin' => true]);

        return $user;
    }

    public function test_list_renders_and_shows_factory_task()
    {
        $admin = $this->developer();

        $task = Task::factory()->create(['title' => '0-test-list-render']);

        Livewire::actingAs($admin)
            ->test(ListTasks::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$task]);
    }

    public function test_create_task_persists_title_and_creator()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateTask::class)
            ->fillForm([
                'title' => '0-test-create-title',
                'user_id' => $creator->id,
                'status' => 'todo',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tasks', [
            'title' => '0-test-create-title',
            'user_id' => $creator->id,
            'status' => 'todo',
        ]);
    }

    public function test_create_task_validates_required_title()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateTask::class)
            ->fillForm([
                'title' => null,
                'user_id' => $creator->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required']);
    }

    public function test_create_task_validates_required_creator()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)
            ->test(CreateTask::class)
            ->fillForm([
                'title' => '0-test-missing-creator',
                'user_id' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['user_id' => 'required']);
    }

    public function test_edit_task_updates_title()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();

        $task = Task::factory()->create([
            'title' => '0-test-edit-before',
            'user_id' => $creator->id,
            'status' => 'todo',
        ]);

        Livewire::actingAs($admin)
            ->test(EditTask::class, ['record' => $task->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['title' => '0-test-edit-after'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('0-test-edit-after', $task->fresh()->title);
    }

    public function test_dropped_detail_attachments_are_deleted_through_the_relationship_tracked_column()
    {
        $admin = $this->developer();
        Storage::disk('public')->put('task/attachments/old.txt', 'x');
        Storage::disk('public')->put('task/attachments/kept.txt', 'x');
        $task = Task::factory()->create(['title' => '0-test-attachment-drop', 'user_id' => $admin->id]);
        $task->detail()->create(['attachments' => [
            ['path' => 'task/attachments/old.txt', 'name' => 'old.txt', 'mime' => 'text/plain', 'size' => 1],
            ['path' => 'task/attachments/kept.txt', 'name' => 'kept.txt', 'mime' => 'text/plain', 'size' => 1],
        ]]);

        $probe = new class {
            use \App\Filament\Traits\HandlesDroppedFiles {
                trackedFilePaths as public;
                deleteDroppedFiles as public;
            }
        };
        $tracked = (fn () => $this->trackedFileColumns())->call(new EditTask());
        $before = $probe->trackedFilePaths($task, $tracked);
        $this->assertEqualsCanonicalizing(['task/attachments/old.txt', 'task/attachments/kept.txt'], $before);

        $task->detail->update(['attachments' => [['path' => 'task/attachments/kept.txt', 'name' => 'kept.txt', 'mime' => 'text/plain', 'size' => 1]]]);
        $probe->deleteDroppedFiles($task, $before, $tracked);

        $this->assertSame(['task/attachments/kept.txt'], array_column($task->detail->fresh()->attachments, 'path'));
        Storage::disk('public')->assertMissing('task/attachments/old.txt');
        Storage::disk('public')->assertExists('task/attachments/kept.txt');
    }

    public function test_delete_action_soft_deletes_task()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();

        $task = Task::factory()->create([
            'title' => '0-test-delete',
            'user_id' => $creator->id,
        ]);

        Livewire::actingAs($admin)
            ->test(ListTasks::class)
            ->callTableAction(DeleteAction::class, $task);

        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    public function test_tasksheet_action_url_targets_assignee_or_falls_back_to_creator()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();
        $assignee = User::factory()->create();

        $assignedTask = Task::factory()->create([
            'title' => '0-test-tasksheet-assignee',
            'user_id' => $creator->id,
            'assigned_to' => $assignee->id,
        ]);

        $unassignedTask = Task::factory()->create([
            'title' => '0-test-tasksheet-creator',
            'user_id' => $creator->id,
            'assigned_to' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(ListTasks::class)
            ->assertTableActionVisible('tasksheet', $assignedTask)
            ->assertTableActionHasUrl('tasksheet', route('tasksheet', ['user' => $assignee->id]), $assignedTask)
            ->assertTableActionVisible('tasksheet', $unassignedTask)
            ->assertTableActionHasUrl('tasksheet', route('tasksheet', ['user' => $creator->id]), $unassignedTask);
    }

    public function test_status_filter_scopes_list_to_matching_tasks()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();

        $todo = Task::factory()->create([
            'title' => '0-test-filter-todo',
            'user_id' => $creator->id,
            'status' => 'todo',
        ]);
        $done = Task::factory()->create([
            'title' => '0-test-filter-done',
            'user_id' => $creator->id,
            'status' => 'done',
        ]);

        Livewire::actingAs($admin)
            ->test(ListTasks::class)
            ->filterTable('status', 'todo')
            ->assertCanSeeTableRecords([$todo])
            ->assertCanNotSeeTableRecords([$done]);
    }

    public function test_delegated_filter_true_scopes_to_delegated_tasks()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();
        $assignee = User::factory()->create();

        $delegated = Task::factory()->create([
            'title' => '0-test-delegated-yes',
            'user_id' => $creator->id,
            'assigned_to' => $assignee->id,
        ]);
        $owned = Task::factory()->create([
            'title' => '0-test-delegated-no',
            'user_id' => $creator->id,
            'assigned_to' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(ListTasks::class)
            ->filterTable('delegated', true)
            ->assertCanSeeTableRecords([$delegated])
            ->assertCanNotSeeTableRecords([$owned]);
    }

    public function test_archived_filter_true_scopes_to_archived_tasks()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();

        $archived = Task::factory()->create([
            'title' => '0-test-archived-yes',
            'user_id' => $creator->id,
            'status' => 'done',
            'archived_at' => now(),
        ]);
        $active = Task::factory()->create([
            'title' => '0-test-archived-no',
            'user_id' => $creator->id,
            'status' => 'done',
            'archived_at' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(ListTasks::class)
            ->filterTable('is_archived', true)
            ->assertCanSeeTableRecords([$archived])
            ->assertCanNotSeeTableRecords([$active]);
    }

    public function test_create_task_persists_project_labels_and_priority()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $creator->id, 'member_ids' => [], 'departments' => []]);

        Livewire::actingAs($admin)
            ->test(CreateTask::class)
            ->fillForm([
                'title' => '0-test-create-project-labels',
                'user_id' => $creator->id,
                'status' => 'todo',
                'project_id' => $project->id,
                'labels' => ['bug', 'urgent'],
                'priority' => 'high',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $task = Task::where('title', '0-test-create-project-labels')->firstOrFail();
        $this->assertSame($project->id, $task->project_id);
        $this->assertSame(['bug', 'urgent'], $task->labels);
        $this->assertSame('high', $task->priority->value);
    }

    public function test_create_task_rejects_deadline_past_project_cap()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();
        $project = Project::factory()->create([
            'owner_id' => $creator->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['deadline' => now()->addDays(2)->format('Y-m-d')],
        ]);

        Livewire::actingAs($admin)
            ->test(CreateTask::class)
            ->fillForm([
                'title' => '0-test-create-over-cap',
                'user_id' => $creator->id,
                'status' => 'todo',
                'project_id' => $project->id,
                'deadline_date' => now()->addDays(3)->format('Y-m-d'),
            ])
            ->call('create')
            ->assertHasFormErrors(['deadline_date']);

        $this->assertDatabaseMissing('tasks', ['title' => '0-test-create-over-cap']);
    }

    public function test_create_task_allows_deadline_on_project_cap_day()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();
        $project = Project::factory()->create([
            'owner_id' => $creator->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['deadline' => now()->addDays(2)->format('Y-m-d')],
        ]);

        Livewire::actingAs($admin)
            ->test(CreateTask::class)
            ->fillForm([
                'title' => '0-test-create-on-cap-day',
                'user_id' => $creator->id,
                'status' => 'todo',
                'project_id' => $project->id,
                'deadline_date' => now()->addDays(2)->format('Y-m-d'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tasks', ['title' => '0-test-create-on-cap-day']);
    }

    public function test_edit_task_rejects_deadline_past_project_cap()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();
        $project = Project::factory()->create([
            'owner_id' => $creator->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['deadline' => now()->addDays(2)->format('Y-m-d')],
        ]);
        $task = Task::factory()->create([
            'title' => '0-test-edit-over-cap',
            'user_id' => $creator->id,
            'project_id' => $project->id,
            'deadline' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(EditTask::class, ['record' => $task->getRouteKey()])
            ->fillForm([
                'deadline_date' => now()->addDays(3)->format('Y-m-d'),
            ])
            ->call('save')
            ->assertHasFormErrors(['deadline_date']);

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
            'deadline' => now()->addDays(3)->setTime(12, 0, 0)->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_create_task_rejects_deadline_past_project_cap_via_year_month_day_selects()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();
        $project = Project::factory()->create([
            'owner_id' => $creator->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['deadline' => now()->addDays(2)->format('Y-m-d')],
        ]);
        $overCap = Jalalian::fromCarbon(now()->addDays(3));

        Livewire::actingAs($admin)
            ->test(CreateTask::class)
            ->fillForm([
                'title' => '0-test-create-over-cap-selects',
                'user_id' => $creator->id,
                'status' => 'todo',
                'project_id' => $project->id,
            ])
            ->set('data.deadline_date_year', $overCap->getYear())
            ->set('data.deadline_date_month', $overCap->getMonth())
            ->set('data.deadline_date_day', $overCap->getDay())
            ->call('create')
            ->assertHasFormErrors(['deadline_date']);

        $this->assertDatabaseMissing('tasks', ['title' => '0-test-create-over-cap-selects']);
    }

    public function test_project_filter_scopes_list_to_matching_tasks()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $creator->id, 'member_ids' => [], 'departments' => []]);

        $inProject = Task::factory()->create([
            'title' => '0-test-project-filter-yes',
            'user_id' => $creator->id,
            'project_id' => $project->id,
        ]);
        $unfiled = Task::factory()->create([
            'title' => '0-test-project-filter-no',
            'user_id' => $creator->id,
            'project_id' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(ListTasks::class)
            ->filterTable('project_id', $project->id)
            ->assertCanSeeTableRecords([$inProject])
            ->assertCanNotSeeTableRecords([$unfiled]);
    }

    public function test_priority_filter_scopes_list_to_matching_tasks()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();

        $urgent = Task::factory()->create([
            'title' => '0-test-priority-filter-yes',
            'user_id' => $creator->id,
            'priority' => 'urgent',
        ]);
        $low = Task::factory()->create([
            'title' => '0-test-priority-filter-no',
            'user_id' => $creator->id,
            'priority' => 'low',
        ]);

        Livewire::actingAs($admin)
            ->test(ListTasks::class)
            ->filterTable('priority', 'urgent')
            ->assertCanSeeTableRecords([$urgent])
            ->assertCanNotSeeTableRecords([$low]);
    }

    public function test_creator_filter_scopes_list_to_matching_tasks()
    {
        $admin = $this->developer();
        $creatorA = User::factory()->create();
        $creatorB = User::factory()->create();

        $taskA = Task::factory()->create(['title' => '0-test-creator-filter-a', 'user_id' => $creatorA->id]);
        $taskB = Task::factory()->create(['title' => '0-test-creator-filter-b', 'user_id' => $creatorB->id]);

        Livewire::actingAs($admin)
            ->test(ListTasks::class)
            ->filterTable('user_id', $creatorA->id)
            ->assertCanSeeTableRecords([$taskA])
            ->assertCanNotSeeTableRecords([$taskB]);
    }

    public function test_assignee_filter_scopes_list_to_matching_tasks()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();
        $assigneeA = User::factory()->create();
        $assigneeB = User::factory()->create();

        $taskA = Task::factory()->create(['title' => '0-test-assignee-filter-a', 'user_id' => $creator->id, 'assigned_to' => $assigneeA->id]);
        $taskB = Task::factory()->create(['title' => '0-test-assignee-filter-b', 'user_id' => $creator->id, 'assigned_to' => $assigneeB->id]);

        Livewire::actingAs($admin)
            ->test(ListTasks::class)
            ->filterTable('assigned_to', $assigneeA->id)
            ->assertCanSeeTableRecords([$taskA])
            ->assertCanNotSeeTableRecords([$taskB]);
    }

    public function test_created_at_filter_scopes_by_from_until()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();

        $old = Task::factory()->create(['title' => '0-test-created-old', 'user_id' => $creator->id, 'created_at' => '2026-01-15']);
        $recent = Task::factory()->create(['title' => '0-test-created-recent', 'user_id' => $creator->id, 'created_at' => '2026-03-20']);

        Livewire::actingAs($admin)
            ->test(ListTasks::class)
            ->filterTable('created_at', ['from' => '2026-02-01', 'until' => '2026-04-01'])
            ->assertCanSeeTableRecords([$recent])
            ->assertCanNotSeeTableRecords([$old]);
    }

    public function test_overdue_filter_scopes_to_past_deadline_unfinished_tasks()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();

        $overdue = Task::factory()->create([
            'title' => '0-test-overdue-yes',
            'user_id' => $creator->id,
            'status' => 'todo',
            'deadline' => now()->subDays(2),
        ]);
        $doneOverdue = Task::factory()->create([
            'title' => '0-test-overdue-done',
            'user_id' => $creator->id,
            'status' => 'done',
            'deadline' => now()->subDays(2),
        ]);
        $future = Task::factory()->create([
            'title' => '0-test-overdue-future',
            'user_id' => $creator->id,
            'status' => 'todo',
            'deadline' => now()->addDays(2),
        ]);

        Livewire::actingAs($admin)
            ->test(ListTasks::class)
            ->filterTable('overdue')
            ->assertCanSeeTableRecords([$overdue])
            ->assertCanNotSeeTableRecords([$doneOverdue, $future]);
    }

    public function test_pruning_soon_filter_scopes_to_tasks_deleted_over_thirty_days_ago()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();

        $pruningSoon = Task::factory()->create([
            'title' => '0-test-pruning-soon-yes',
            'user_id' => $creator->id,
            'deleted_at' => now()->subDays(31),
        ]);
        $recentlyDeleted = Task::factory()->create([
            'title' => '0-test-pruning-soon-no',
            'user_id' => $creator->id,
            'deleted_at' => now()->subDays(5),
        ]);

        Livewire::actingAs($admin)
            ->test(ListTasks::class)
            ->filterTable('trashed', true)
            ->filterTable('pruning_soon')
            ->assertCanSeeTableRecords([$pruningSoon])
            ->assertCanNotSeeTableRecords([$recentlyDeleted]);
    }

    public function test_trashed_filter_scopes_list_to_trashed_tasks_only()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();

        $trashed = Task::factory()->create([
            'title' => '0-test-trashed-yes',
            'user_id' => $creator->id,
            'deleted_at' => now(),
        ]);
        $active = Task::factory()->create([
            'title' => '0-test-trashed-no',
            'user_id' => $creator->id,
            'deleted_at' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(ListTasks::class)
            ->filterTable('trashed', false)
            ->assertCanSeeTableRecords([$trashed])
            ->assertCanNotSeeTableRecords([$active]);
    }

    public function test_edit_task_updates_project_labels_and_priority()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $creator->id, 'member_ids' => [], 'departments' => []]);
        $task = Task::factory()->create([
            'title' => '0-test-edit-project',
            'user_id' => $creator->id,
            'status' => 'todo',
            'project_id' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(EditTask::class, ['record' => $task->getRouteKey()])
            ->assertSuccessful()
            ->fillForm([
                'project_id' => $project->id,
                'labels' => ['reviewed'],
                'priority' => 'medium',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $task->refresh();
        $this->assertSame($project->id, $task->project_id);
        $this->assertSame(['reviewed'], $task->labels);
        $this->assertSame('medium', $task->priority->value);
    }

    public function test_edit_task_persists_checklist()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();
        $task = Task::factory()->create([
            'title' => '0-test-checklist',
            'user_id' => $creator->id,
            'status' => 'todo',
        ]);
        $task->detail()->create(['checklist' => []]);

        Livewire::actingAs($admin)
            ->test(EditTask::class, ['record' => $task->getRouteKey()])
            ->fillForm([
                'detail' => [
                    'checklist' => [
                        ['text' => 'step one', 'done' => true],
                        ['text' => 'step two', 'done' => false],
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $task->refresh()->load('detail');
        $checklist = $task->detail->checklist;
        $this->assertSame('step one', $checklist[0]['text']);
        $this->assertSame(true, $checklist[0]['done']);
        $this->assertSame('step two', $checklist[1]['text']);
        $this->assertSame(false, $checklist[1]['done']);
    }

    public function test_create_task_rejects_label_over_thirty_chars()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateTask::class)
            ->fillForm([
                'title' => '0-test-long-label',
                'user_id' => $creator->id,
                'status' => 'todo',
                'labels' => [str_repeat('x', 31)],
            ])
            ->call('create')
            ->assertHasFormErrors(['labels']);
    }

    public function test_create_and_edit_task_forms_have_no_deadline_time_field_and_save_at_noon()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateTask::class)
            ->assertFormFieldDoesNotExist('deadline_time')
            ->fillForm([
                'title' => '0-test-deadline-noon',
                'user_id' => $creator->id,
                'status' => 'todo',
                'deadline_date_year' => '1403',
                'deadline_date_month' => '5',
                'deadline_date_day' => '10',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $task = Task::where('title', '0-test-deadline-noon')->firstOrFail();
        $this->assertSame('12:00:00', $task->deadline->format('H:i:s'));

        Livewire::actingAs($admin)
            ->test(EditTask::class, ['record' => $task->getRouteKey()])
            ->assertFormFieldDoesNotExist('deadline_time')
            ->fillForm([
                'deadline_date_year' => '1403',
                'deadline_date_month' => '6',
                'deadline_date_day' => '15',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $task->refresh();
        $this->assertSame('12:00:00', $task->deadline->format('H:i:s'));
    }

    public function test_create_task_rejects_more_than_ten_labels()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateTask::class)
            ->fillForm([
                'title' => '0-test-too-many-labels',
                'user_id' => $creator->id,
                'status' => 'todo',
                'labels' => array_map(fn($i) => 'tag' . $i, range(1, 11)),
            ])
            ->call('create')
            ->assertHasFormErrors(['labels']);
    }

    public function test_create_task_rejects_done_status_without_state()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateTask::class)
            ->fillForm([
                'title' => '0-test-done-no-state',
                'user_id' => $creator->id,
                'status' => 'done',
            ])
            ->call('create')
            ->assertHasFormErrors(['status']);
    }

    public function test_create_task_allows_done_status_with_state()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateTask::class)
            ->fillForm([
                'title' => '0-test-done-with-state',
                'user_id' => $creator->id,
                'status' => 'done',
                'detail' => ['state' => 'completion'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tasks', ['title' => '0-test-done-with-state', 'status' => 'done']);
    }

    public function test_edit_task_persists_checklist_weights()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();
        $task = Task::factory()->create([
            'title' => '0-test-checklist-weights',
            'user_id' => $creator->id,
            'status' => 'todo',
        ]);
        $task->detail()->create(['checklist' => []]);

        Livewire::actingAs($admin)
            ->test(EditTask::class, ['record' => $task->getRouteKey()])
            ->fillForm([
                'detail' => [
                    'checklist' => [
                        ['text' => 'step one', 'done' => true, 'weight' => 60],
                        ['text' => 'step two', 'done' => false, 'weight' => 40],
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $task->refresh()->load('detail');
        $checklist = $task->detail->checklist;
        $this->assertSame(60, $checklist[0]['weight']);
        $this->assertSame(40, $checklist[1]['weight']);
        $this->assertSame(100, array_sum(array_map(fn($i) => $i['weight'], $checklist)));
    }

    public function test_all_table_groups_render_without_error()
    {
        $admin = $this->developer();
        Task::factory()->create(['title' => '0-test-group-render']);

        $component = Livewire::actingAs($admin)->test(ListTasks::class)->assertSuccessful();

        foreach (['status', 'creator.name', 'assignee.name'] as $group) {
            $component->set('tableGrouping', $group)->assertSuccessful();
        }
    }

    public function test_sort_by_id_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();
        $first = Task::factory()->create(['title' => '0-sort-id-a', 'user_id' => $creator->id]);
        $second = Task::factory()->create(['title' => '0-sort-id-b', 'user_id' => $creator->id]);

        $component = Livewire::actingAs($admin)->test(ListTasks::class)
            ->searchTable('0-sort-id-')
            ->sortTable('id')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('id', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sort_by_title_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();
        $first = Task::factory()->create(['title' => '0-AAA-sort-title', 'user_id' => $creator->id]);
        $second = Task::factory()->create(['title' => '0-ZZZ-sort-title', 'user_id' => $creator->id]);

        $component = Livewire::actingAs($admin)->test(ListTasks::class)
            ->searchTable('sort-title')
            ->sortTable('title')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('title', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sort_by_status_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();
        $done = Task::factory()->create(['title' => '0-sort-status-done', 'user_id' => $creator->id, 'status' => 'done']);
        $todo = Task::factory()->create(['title' => '0-sort-status-todo', 'user_id' => $creator->id, 'status' => 'todo']);

        $component = Livewire::actingAs($admin)->test(ListTasks::class)
            ->searchTable('0-sort-status-')
            ->sortTable('status')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$todo->id, $done->id], array_values(array_intersect($ids, [$done->id, $todo->id])));

        $component->sortTable('status', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$done->id, $todo->id], array_values(array_intersect($ids, [$done->id, $todo->id])));
    }

    public function test_sort_by_priority_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();
        $high = Task::factory()->create(['title' => '0-sort-priority-high', 'user_id' => $creator->id, 'priority' => 'high']);
        $low = Task::factory()->create(['title' => '0-sort-priority-low', 'user_id' => $creator->id, 'priority' => 'low']);

        $component = Livewire::actingAs($admin)->test(ListTasks::class)
            ->searchTable('0-sort-priority-')
            ->sortTable('priority')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$low->id, $high->id], array_values(array_intersect($ids, [$high->id, $low->id])));

        $component->sortTable('priority', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$high->id, $low->id], array_values(array_intersect($ids, [$high->id, $low->id])));
    }

    public function test_sort_by_deadline_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();
        $earlier = Task::factory()->create(['title' => '0-sort-deadline-earlier', 'user_id' => $creator->id, 'deadline' => now()->addDay()]);
        $later = Task::factory()->create(['title' => '0-sort-deadline-later', 'user_id' => $creator->id, 'deadline' => now()->addYears(3)]);

        $component = Livewire::actingAs($admin)->test(ListTasks::class)
            ->searchTable('0-sort-deadline-')
            ->sortTable('deadline')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$earlier->id, $later->id], array_values(array_intersect($ids, [$earlier->id, $later->id])));

        $component->sortTable('deadline', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$later->id, $earlier->id], array_values(array_intersect($ids, [$earlier->id, $later->id])));
    }

    public function test_sort_by_created_at_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();
        $older = Task::factory()->create(['title' => '0-sort-created-old', 'user_id' => $creator->id, 'created_at' => '2020-01-15']);
        $newer = Task::factory()->create(['title' => '0-sort-created-new', 'user_id' => $creator->id, 'created_at' => '2030-01-15']);

        $component = Livewire::actingAs($admin)->test(ListTasks::class)
            ->searchTable('0-sort-created-')
            ->sortTable('created_at')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('created_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));
    }

    public function test_sort_by_creator_name_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $creatorA = User::factory()->create(['name' => '0-AAA-sort-creator']);
        $creatorZ = User::factory()->create(['name' => '0-ZZZ-sort-creator']);
        $first = Task::factory()->create(['title' => '0-sort-creator-a', 'user_id' => $creatorA->id]);
        $second = Task::factory()->create(['title' => '0-sort-creator-z', 'user_id' => $creatorZ->id]);

        $component = Livewire::actingAs($admin)->test(ListTasks::class)
            ->searchTable('0-sort-creator-')
            ->sortTable('creator.name')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('creator.name', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sort_by_archived_at_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();
        $earlier = Task::factory()->create(['title' => '0-sort-archived-earlier', 'user_id' => $creator->id, 'status' => 'done', 'archived_at' => '2020-01-15']);
        $later = Task::factory()->create(['title' => '0-sort-archived-later', 'user_id' => $creator->id, 'status' => 'done', 'archived_at' => '2030-01-15']);

        $component = Livewire::actingAs($admin)->test(ListTasks::class)
            ->searchTable('0-sort-archived-')
            ->sortTable('archived_at')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$earlier->id, $later->id], array_values(array_intersect($ids, [$earlier->id, $later->id])));

        $component->sortTable('archived_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$later->id, $earlier->id], array_values(array_intersect($ids, [$earlier->id, $later->id])));
    }

    public function test_sort_by_approved_at_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();
        $earlier = Task::factory()->create(['title' => '0-sort-approved-earlier', 'user_id' => $creator->id, 'status' => 'done', 'approved_at' => '2020-01-15']);
        $later = Task::factory()->create(['title' => '0-sort-approved-later', 'user_id' => $creator->id, 'status' => 'done', 'approved_at' => '2030-01-15']);

        $component = Livewire::actingAs($admin)->test(ListTasks::class)
            ->searchTable('0-sort-approved-')
            ->sortTable('approved_at')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$earlier->id, $later->id], array_values(array_intersect($ids, [$earlier->id, $later->id])));

        $component->sortTable('approved_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$later->id, $earlier->id], array_values(array_intersect($ids, [$earlier->id, $later->id])));
    }

    public function test_sort_by_deleted_at_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $creator = User::factory()->create();
        $earlier = Task::factory()->create(['title' => '0-sort-deleted-earlier', 'user_id' => $creator->id, 'deleted_at' => '2020-01-15']);
        $later = Task::factory()->create(['title' => '0-sort-deleted-later', 'user_id' => $creator->id, 'deleted_at' => '2030-01-15']);

        $component = Livewire::actingAs($admin)->test(ListTasks::class)
            ->filterTable('trashed', false)
            ->searchTable('0-sort-deleted-')
            ->sortTable('deleted_at')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$earlier->id, $later->id], array_values(array_intersect($ids, [$earlier->id, $later->id])));

        $component->sortTable('deleted_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$later->id, $earlier->id], array_values(array_intersect($ids, [$earlier->id, $later->id])));
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            \App\Filament\Resources\TaskResource\Pages\CreateTask::class => \App\Filament\Pages\CreateRecord::class,
            \App\Filament\Resources\TaskResource\Pages\EditTask::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\TaskResource\Pages\ListTasks::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\TaskResource\Pages\CreateTask::class, \App\Filament\Resources\TaskResource\Pages\EditTask::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/TaskResource/Schemas/TaskTablePresenter.php'),
        ];

        $recordMethods = ['getTitleFromRecordUsing', 'getKeyFromRecordUsing', 'getDescriptionFromRecordUsing'];
        $queryMethods = ['modifyQueryUsing', 'groupQueryUsing', 'orderQueryUsing', 'scopeQueryByKeyUsing', 'scopeQueryUsing', 'query'];

        $offenders = [];

        foreach ([[$recordMethods, 'record'], [$queryMethods, 'query']] as [$methods, $expectedName]) {
            $pattern = '/\\b(' . implode('|', $methods) . ')\\s*\\(\\s*(?:static\\s+)?(?:function|fn)\\s*\\(([^)]*)\\)/s';

            foreach ($files as $path) {
                $contents = file_get_contents($path);

                if (!preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                    continue;
                }

                foreach ($matches as $match) {
                    $method = $match[1][0];
                    $paramList = $match[2][0];
                    $offset = $match[0][1];

                    $firstParam = trim(explode(',', $paramList)[0] ?? '');

                    if ($firstParam === '') {
                        continue;
                    }

                    if (!preg_match('/^(?<type>[^$]*)\\$(?<name>[A-Za-z_][A-Za-z0-9_]*)/', $firstParam, $m)) {
                        continue;
                    }

                    $hasType = trim($m['type']) !== '';
                    $name = $m['name'];

                    if ($name === $expectedName || $hasType) {
                        continue;
                    }

                    $line = substr_count(substr($contents, 0, $offset), "\n") + 1;
                    $offenders[] = basename($path) . ":{$line} {$method}(fn(\${$name}) => ...)";
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Closure param must be named \$record/\$query (or carry an explicit type hint) so Filament's by-name evaluate() injection binds correctly:\n" . implode("\n", $offenders),
        );
    }

    private function viewOnlyAdmin(): User
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'admin']);
        Permission::create(['user_id' => $user->id, 'is_super_admin' => false, 'abilities' => [['module' => 'task', 'actions' => ['view']]]]);

        return $user;
    }

    private function pendingApprovalTask(): Task
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => [], 'settings' => ['requires_approval' => true]]);
        $task = Task::factory()->forProject($project)->create(['user_id' => $owner->id, 'assigned_to' => $owner->id, 'status' => 'done', 'deadline' => now()->addDays(9)]);
        Task::whereKey($task->id)->toBase()->update(['approved_at' => null]);

        return Task::find($task->id);
    }

    public function test_approve_action_needs_update_permission_and_approves_for_those_who_have_it(): void
    {
        $task = $this->pendingApprovalTask();
        \Filament\Facades\Filament::setCurrentPanel('admin');

        Livewire::actingAs($this->viewOnlyAdmin())->test(ListTasks::class)
            ->assertTableActionHidden('approve', $task);

        Livewire::actingAs($this->developer())->test(ListTasks::class)
            ->assertTableActionVisible('approve', $task)
            ->callTableAction('approve', $task);

        $this->assertNotNull(Task::find($task->id)->approved_at);
    }

    public function test_creator_select_is_locked_on_edit_but_open_on_create(): void
    {
        $task = Task::factory()->create();

        Livewire::actingAs($this->developer())->test(EditTask::class, ['record' => $task->getRouteKey()])
            ->assertFormFieldDisabled('user_id');

        Livewire::actingAs($this->developer())->test(CreateTask::class)
            ->assertFormFieldEnabled('user_id');
    }

    public function test_ticket_tasks_have_no_edit_or_delete_action(): void
    {
        $ticketTask = Task::factory()->create();
        $plain = Task::factory()->create();
        $ticketTask->ticket_id = \App\Models\Ticket::factory()->create()->id;
        $ticketTask->saveQuietly();

        Livewire::actingAs($this->developer())->test(ListTasks::class)
            ->assertTableActionHidden('edit', $ticketTask)
            ->assertTableActionHidden('delete', $ticketTask)
            ->assertTableActionVisible('edit', $plain)
            ->assertTableActionVisible('delete', $plain);
    }

    public function test_bulk_delete_skips_ticket_tasks_without_aborting_the_rest(): void
    {
        $ticketTask = Task::factory()->create();
        $plain = Task::factory()->create();
        $ticketTask->ticket_id = \App\Models\Ticket::factory()->create()->id;
        $ticketTask->saveQuietly();

        Livewire::actingAs($this->developer())->test(ListTasks::class)
            ->callTableBulkAction('delete', [$ticketTask, $plain]);

        $this->assertNull(Task::find($plain->id));
        $this->assertNotNull(Task::find($ticketTask->id));
    }

    public function test_create_task_refuses_an_assignee_outside_the_project_roster(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $outsider = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        Livewire::actingAs($this->developer())->test(CreateTask::class)
            ->fillForm(['title' => '0-test-roster-create', 'user_id' => $owner->id, 'status' => 'todo', 'project_id' => $project->id, 'assigned_to' => $outsider->id])
            ->call('create');

        $this->assertDatabaseMissing('tasks', ['title' => '0-test-roster-create']);

        Livewire::actingAs($this->developer())->test(CreateTask::class)
            ->fillForm(['title' => '0-test-roster-create', 'user_id' => $owner->id, 'status' => 'todo', 'project_id' => $project->id, 'assigned_to' => $owner->id])
            ->call('create');

        $this->assertDatabaseHas('tasks', ['title' => '0-test-roster-create', 'assigned_to' => $owner->id]);
    }

    public function test_edit_task_refuses_reassigning_to_someone_outside_the_project_roster(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $member = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $outsider = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$member->id], 'departments' => []]);
        $task = Task::factory()->forProject($project)->create(['user_id' => $owner->id, 'assigned_to' => $owner->id, 'deadline' => now()->addDays(9)]);

        Livewire::actingAs($this->developer())->test(EditTask::class, ['record' => $task->getRouteKey()])
            ->fillForm(['assigned_to' => $member->id])
            ->call('save');
        $this->assertSame($member->id, Task::find($task->id)->assigned_to);

        Livewire::actingAs($this->developer())->test(EditTask::class, ['record' => $task->getRouteKey()])
            ->fillForm(['assigned_to' => $outsider->id])
            ->call('save');
        $this->assertSame($member->id, Task::find($task->id)->assigned_to);
    }

    public function test_create_task_without_a_deadline_gets_the_project_sla_default(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => [], 'settings' => ['sla' => 72]]);

        Livewire::actingAs($this->developer())->test(CreateTask::class)
            ->fillForm(['title' => '0-test-sla-default', 'user_id' => $owner->id, 'status' => 'todo', 'project_id' => $project->id])
            ->call('create')
            ->assertHasNoFormErrors();

        $deadline = Task::where('title', '0-test-sla-default')->firstOrFail()->deadline;
        $this->assertEqualsWithDelta(now()->addHours(72)->timestamp, $deadline->timestamp, 600);
    }

    public function test_edit_task_refuses_a_changed_deadline_in_the_past_but_keeps_an_untouched_one(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $stale = Task::factory()->ownedBy($owner)->create(['deadline' => now()->subDays(5)->setTime(12, 0)]);
        $original = $stale->deadline->toDateString();

        Livewire::actingAs($this->developer())->test(EditTask::class, ['record' => $stale->getRouteKey()])
            ->fillForm(['title' => '0-test-keep-old-deadline'])
            ->call('save');
        $this->assertSame('0-test-keep-old-deadline', Task::find($stale->id)->title);
        $this->assertSame($original, Task::find($stale->id)->deadline->toDateString());

        Livewire::actingAs($this->developer())->test(EditTask::class, ['record' => $stale->getRouteKey()])
            ->fillForm(['deadline_date' => now()->subDays(2)->format('Y-m-d')])
            ->call('save');
        $this->assertSame($original, Task::find($stale->id)->deadline->toDateString());
    }
}
