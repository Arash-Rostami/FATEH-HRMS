<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Filament\Resources\TaskResource\Enums\TaskPriority;
use App\Filament\Resources\TaskResource\Enums\TaskState;
use App\Services\ProjectTask\ApproveTaskAction;
use App\Services\ProjectTask\BoardCollaboratorResolver;
use App\Services\ProjectTask\CreateTaskAction;
use App\Livewire\Dashboard\TaskBoard\Actions\BulkAssignTasksAction;
use App\Livewire\Dashboard\TaskBoard\Actions\BulkDeleteTasksAction;
use App\Livewire\Dashboard\TaskBoard\Actions\BulkMoveTasksAction;
use App\Services\ProjectTask\ReopenTaskAction;
use App\Livewire\Dashboard\TaskBoard\Actions\ForceDeleteTaskAction;
use App\Services\ProjectTask\CyclePriorityAction;
use App\Services\ProjectTask\DuplicateTaskAction;
use App\Services\ProjectTask\ReorderTaskAction;
use App\Services\ProjectTask\UpdateTaskAction;
use App\Services\ProjectTask\UpdateTaskStatusAction;
use App\Livewire\Dashboard\TaskBoard\Main;
use App\Livewire\Dashboard\TaskBoard\Presentation\TaskBoardPresenter;
use App\Models\Department;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskDetail;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Menu\NudgeService;
use App\Services\ProjectTask\ActivityLogger;
use App\Services\ProjectTask\RankGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

class TaskBoardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        Livewire::withoutLazyLoading();
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

    public function test_taskboard_crud_lifecycle()
    {
        $creator = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);

        // CREATE
        Livewire::withoutLazyLoading();
        Livewire::actingAs($creator)
            ->test(Main::class)
            ->set('form.newTitle', 'Tinker task probe 1')
            ->set('form.newDescription', 'Minimal description for automated probe.')
            ->call('createTask')
            ->assertHasNoErrors()
            ->assertOk();

        $task = Task::where('title', 'Tinker task probe 1')->firstOrFail();
        $this->assertSame('todo', $task->status);
        $this->assertNotNull($task->detail);

        // UPDATE
        Livewire::withoutLazyLoading();
        Livewire::actingAs($creator)
            ->test(Main::class)
            ->call('editTask', $task->id)
            ->set('form.newTitle', 'Tinker task probe 1 (edited)')
            ->call('updateTask')
            ->assertHasNoErrors()
            ->assertOk();

        $task->refresh();
        $this->assertSame('Tinker task probe 1 (edited)', $task->title);

        // UPDATE STATUS — creator, unassigned, allowed
        Livewire::withoutLazyLoading();
        Livewire::actingAs($creator)
            ->test(Main::class)
            ->call('updateTaskStatus', $task->id, 'in-progress')
            ->assertHasNoErrors();

        $task->refresh();
        $this->assertSame('in-progress', $task->status);

        // ASSIGN
        Livewire::withoutLazyLoading();
        Livewire::actingAs($creator)
            ->test(Main::class)
            ->call('assignTask', $task->id, $assignee->id)
            ->assertHasNoErrors();

        $task->refresh();
        $this->assertSame($assignee->id, $task->assigned_to);

        // AUTHZ — creator can no longer change status once delegated away
        Livewire::withoutLazyLoading();
        Livewire::actingAs($creator)
            ->test(Main::class)
            ->call('updateTaskStatus', $task->id, 'done');

        $task->refresh();
        $this->assertNotSame('done', $task->status);

        $task->detail()->update(['state' => TaskState::Completion->value]);

        // UPDATE STATUS as assignee — now allowed
        Livewire::withoutLazyLoading();
        Livewire::actingAs($assignee)
            ->test(Main::class)
            ->call('updateTaskStatus', $task->id, 'done')
            ->assertHasNoErrors();

        $task->refresh();
        $this->assertSame('done', $task->status);

        // UNDO ASSIGNMENT — fresh task, before it's done
        Livewire::withoutLazyLoading();
        Livewire::actingAs($creator)
            ->test(Main::class)
            ->set('form.newTitle', 'Tinker task probe 2')
            ->call('createTask')
            ->assertHasNoErrors();

        $task2 = Task::where('title', 'Tinker task probe 2')->firstOrFail();

        Livewire::withoutLazyLoading();
        Livewire::actingAs($creator)
            ->test(Main::class)
            ->call('assignTask', $task2->id, $assignee->id)
            ->assertHasNoErrors();

        Livewire::withoutLazyLoading();
        Livewire::actingAs($creator)
            ->test(Main::class)
            ->call('undoAssignment', $task2->id)
            ->assertHasNoErrors();

        $task2->refresh();
        $this->assertNull($task2->assigned_to);

        // DELETE — soft delete, creator only
        Livewire::withoutLazyLoading();
        Livewire::actingAs($creator)
            ->test(Main::class)
            ->call('deleteTask', $task->id)
            ->assertHasNoErrors();

        $this->assertNull(Task::find($task->id));
        $this->assertTrue(Task::withTrashed()->find($task->id)->trashed());

        // FORCE DELETE — not wired to Main, used by pruning; called directly
        $trashed = Task::withTrashed()->find($task->id);
        app(ForceDeleteTaskAction::class)->execute($trashed);
        $this->assertNull(Task::withTrashed()->find($task->id));

        // BULK ASSIGN / MOVE / DELETE
        Livewire::withoutLazyLoading();
        Livewire::actingAs($creator)->test(Main::class)->set('form.newTitle', 'Tinker task probe 3')->call('createTask');
        Livewire::withoutLazyLoading();
        Livewire::actingAs($creator)->test(Main::class)->set('form.newTitle', 'Tinker task probe 4')->call('createTask');
        $task3 = Task::where('title', 'Tinker task probe 3')->firstOrFail();
        $task4 = Task::where('title', 'Tinker task probe 4')->firstOrFail();

        Livewire::withoutLazyLoading();
        Livewire::actingAs($creator)
            ->test(Main::class)
            ->set('selectedTasks', [$task3->id, $task4->id])
            ->call('bulkAssign', $assignee->id)
            ->assertHasNoErrors();

        $this->assertSame($assignee->id, $task3->refresh()->assigned_to);
        $this->assertSame($assignee->id, $task4->refresh()->assigned_to);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($assignee)
            ->test(Main::class)
            ->set('selectedTasks', [$task3->id, $task4->id])
            ->call('bulkMoveStatus', 'pending')
            ->assertHasNoErrors();

        $this->assertSame('pending', $task3->refresh()->status);
        $this->assertSame('pending', $task4->refresh()->status);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($creator)
            ->test(Main::class)
            ->set('selectedTasks', [$task3->id, $task4->id])
            ->call('bulkDelete')
            ->assertHasNoErrors();

        $this->assertNull(Task::find($task3->id));
        $this->assertNull(Task::find($task4->id));

        // READ
        Livewire::withoutLazyLoading();
        Livewire::actingAs($creator)
            ->test(Main::class)
            ->assertHasNoErrors();
    }

    public function test_done_column_hides_tasks_older_than_45_days()
    {
        $user = User::factory()->create(['status' => 'active']);

        Task::factory()->ownedBy($user)->withStatus('done')->updatedAt('-10 days')->create();
        Task::factory()->ownedBy($user)->withStatus('done')->updatedAt('-30 days')->create();
        Task::factory()->ownedBy($user)->withStatus('done')->updatedAt('-60 days')->create();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('totalCount.done', 2)
            ->assertSet('doneTotalCount.done', 3);
    }

    public function test_load_tasks_folds_the_column_counts_into_a_single_grouped_query(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Task::factory()->ownedBy($user)->withStatus('todo')->create();
        Task::factory()->ownedBy($user)->withStatus('done')->updatedAt('-5 days')->create();
        Task::factory()->ownedBy($user)->withStatus('done')->updatedAt('-60 days')->create();

        $component = Livewire::actingAs($user)->test(Main::class)->instance();

        $this->assertSame(1, $component->totalCount['todo']);
        $this->assertSame(1, $component->totalCount['done']); // only the task inside the 45-day window
        $this->assertSame(2, $component->doneTotalCount['done']); // the unwindowed tally from the same query

        DB::flushQueryLog();
        DB::enableQueryLog();
        $component->loadTasks();
        DB::disableQueryLog();

        $grouped = array_filter(DB::getQueryLog(), fn($q) => str_contains($q['query'], 'group by `status`'));
        $this->assertCount(1, $grouped, 'one grouped aggregate must carry the non-done counts, the done total and the windowed done count — never one count query per column');
    }

    public function test_toggle_show_all_done_reveals_old_done_tasks_and_resets_page()
    {
        $user = User::factory()->create(['status' => 'active']);

        // 6 done tasks inside the window (2 pages at perPage=4) + 4 older than the window.
        // We need page 2 to exist in the windowed view so we can prove the toggle resets page.done to 1.
        foreach (range(1, 6) as $i) {
            Task::factory()->ownedBy($user)->withStatus('done')->updatedAt('-5 days')->create();
        }
        foreach (range(1, 4) as $i) {
            Task::factory()->ownedBy($user)->withStatus('done')->updatedAt('-60 days')->create();
        }

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('totalCount.done', 6) // 6 inside the 45-day window
            ->assertSet('page.done', 1)
            ->call('nextPage', 'done') // advance to page 2 of the windowed set
            ->assertSet('page.done', 2)
            ->call('toggleShowAllDone') // bypass the window — must reset page.done to 1
            ->assertSet('showAllDone', true)
            ->assertSet('page.done', 1)
            ->assertSet('totalCount.done', 10); // now every done task counts
    }

    public function test_search_bypasses_done_window_without_toggling()
    {
        $user = User::factory()->create(['status' => 'active']);

        Task::factory()->ownedBy($user)->withStatus('done')->updatedAt('-60 days')->create(['title' => 'Legacy audit report']);
        Task::factory()->ownedBy($user)->withStatus('done')->updatedAt('-5 days')->create(['title' => 'Fresh review']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('totalCount.done', 1) // window hides the legacy one
            ->set('search', 'Legacy')
            ->assertSet('totalCount.done', 1) // search surfaces the legacy match despite the window
            ->assertSet('showAllDone', false); // toggle state untouched — search bypasses the window independently
    }

    public function test_search_with_single_zero_character_still_bypasses_window()
    {
        $user = User::factory()->create(['status' => 'active']);

        Task::factory()->ownedBy($user)->withStatus('done')->updatedAt('-60 days')->create(['title' => 'Phase 0 rollout']);
        Task::factory()->ownedBy($user)->withStatus('done')->updatedAt('-5 days')->create(['title' => 'Current sprint']);

        // Regression guard: "0" is falsy in PHP, so the truthiness and strict-empty checks
        // must agree or the window bypass and the search filter diverge.
        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('totalCount.done', 1)
            ->set('search', '0')
            ->assertSet('totalCount.done', 1); // "0" matches "Phase 0 rollout", bypassing the window
    }

    public function test_jump_to_page_clamps_to_valid_range()
    {
        $user = User::factory()->create(['status' => 'active']);

        // 10 done tasks inside the window → 3 pages at perPage=4.
        foreach (range(1, 10) as $i) {
            Task::factory()->ownedBy($user)->withStatus('done')->updatedAt('-1 day')->create();
        }

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('page.done', 1)
            ->call('jumpToPage', 'done', 2)
            ->assertSet('page.done', 2)
            ->call('jumpToPage', 'done', 99) // beyond last page → clamp to 3
            ->assertSet('page.done', 3)
            ->call('jumpToPage', 'done', 0) // below 1 → clamp to 1
            ->assertSet('page.done', 1)
            ->call('jumpToPage', 'done', -5) // negative → clamp to 1
            ->assertSet('page.done', 1);
    }

    public function test_jump_to_page_unknown_column_is_ignored()
    {
        $user = User::factory()->create(['status' => 'active']);
        Task::factory()->ownedBy($user)->withStatus('todo')->create();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('page.todo', 1)
            ->call('jumpToPage', 'nonexistent', 5)
            ->assertHasNoErrors()
            ->assertSet('page.todo', 1);
    }

    public function test_switch_tab_resets_pages_and_show_all_done()
    {
        $creator = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);

        Task::factory()->delegatedBy($creator, $assignee)->withStatus('done')->updatedAt('-60 days')->create();

        Livewire::actingAs($creator)
            ->test(Main::class)
            ->call('toggleShowAllDone')
            ->assertSet('showAllDone', true)
            ->call('nextPage', 'done')
            ->call('switchTab', 'assigned-tasks')
            ->assertSet('activeTab', 'assigned-tasks')
            ->assertSet('showAllDone', false)
            ->assertSet('page.done', 1);
    }

    public function test_updated_search_resets_all_column_pages()
    {
        $user = User::factory()->create(['status' => 'active']);
        foreach (range(1, 10) as $i) {
            Task::factory()->ownedBy($user)->withStatus('todo')->create();
        }

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('nextPage', 'todo')
            ->assertSet('page.todo', 2)
            ->set('search', 'anything')
            ->assertSet('page.todo', 1);
    }

    public function test_active_filter_count_is_a_reactive_snapshot_property()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('activeFilterCount', 0)
            ->set('deadlineFilter', 'week')
            ->assertSet('activeFilterCount', 1)
            ->set('priorityFilter', 'high')
            ->assertSet('activeFilterCount', 2)
            ->call('clearFilters')
            ->assertSet('activeFilterCount', 0);
    }

    public function test_filter_controls_live_in_the_shared_filters_panel_not_the_toolbar()
    {
        $user = User::factory()->create(['status' => 'active']);

        $html = html_entity_decode(Livewire::actingAs($user)->test(Main::class)->html());

        $this->assertSame(1, substr_count($html, 'x-data="filters('));
        $this->assertStringContainsString("this.\$wire.get('activeFilterCount') > 0", $html);
        $this->assertStringContainsString("this.\$wire.call('clearFilters'); this.\$wire.set('search', '')", $html);

        foreach (['deadlineFilter', 'projectFilter', 'priorityFilter', 'responsibleFilter', 'departmentFilter'] as $control) {
            $this->assertSame(1, substr_count($html, 'id="' . $control . '"'), $control);
            $this->assertStringContainsString('wire:model.live="' . $control . '"', $html);
        }

        foreach (['filter_alt_off', 'toggleFilters', 'filtersOpen', 'taskboard-clear-filters', 'taskboard-filter-count'] as $removed) {
            $this->assertStringNotContainsString($removed, $html, $removed);
        }
    }

    public function test_clear_filters_resets_every_filter_control()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('deadlineFilter', 'week')
            ->set('priorityFilter', 'high')
            ->set('schemeFilter', 'x')
            ->set('actionSourceFilter', 'y')
            ->set('labelFilter', ['a'])
            ->call('clearFilters')
            ->assertSet('deadlineFilter', '')
            ->assertSet('priorityFilter', '')
            ->assertSet('schemeFilter', '')
            ->assertSet('actionSourceFilter', '')
            ->assertSet('labelFilter', [])
            ->assertSet('activeFilterCount', 0);
    }

    public function test_done_empty_state_knows_older_tasks_exist()
    {
        $user = User::factory()->create(['status' => 'active']);

        // Window is active, nothing done in last 45 days, but one old done task exists.
        Task::factory()->ownedBy($user)->withStatus('done')->updatedAt('-60 days')->create();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('totalCount.done', 0)        // window hides it
            ->assertSet('doneTotalCount.done', 1)   // but the unwindowed tally says older exist
            ->assertSet('tasks.done', []);          // so the column renders the "show older" nudge
    }

    public function test_unit_and_section_options_resolve_from_selected_department_code()
    {
        $user = User::factory()->create(['status' => 'active']);

        $dept = Department::create([
            'code' => 'QA',
            'name' => 'کنترل کیفیت',
            'description' => 'probe dept',
            'units' => ['Lab', 'Field'],
            'sections' => ['Chemical', 'Mechanical'],
        ]);

        $component = Livewire::actingAs($user)->test(Main::class);

        $component->set('form.departmentId', 'QA');

        $this->assertSame(['Lab' => 'Lab', 'Field' => 'Field'], $component->instance()->availableUnits());
        $this->assertSame(['Chemical' => 'Chemical', 'Mechanical' => 'Mechanical'], $component->instance()->availableSections());

        $component->set('form.departmentId', null);
        $this->assertSame([], $component->instance()->availableUnits());
        $this->assertSame([], $component->instance()->availableSections());
    }

    public function test_task_reply_notifies_the_other_participant_not_the_author()
    {
        $creator = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $task = Task::factory()->create(['user_id' => $creator->id, 'assigned_to' => $assignee->id, 'status' => 'todo']);

        $task->addReply($assignee, 'شروع کردم.');
        NudgeService::reconcile('tasks-controller:nudge', Task::class, $task->id);

        $creatorNotif = $creator->fresh()->notifications()->latest()->first();
        $this->assertNotNull($creatorNotif, 'creator must be notified when the assignee replies');
        $this->assertStringContainsString('پاسخ جدید', $creatorNotif->data['title']);

        $this->assertSame(
            0,
            DB::table('notifications')->where('data->menu_key', 'tasks-controller:reply-nudge')->where('data->item_id', (string) $task->id)->count(),
            'task reply notifications must fold into the existing tasks-controller:nudge key, not a separate one',
        );
    }

    public function test_task_reply_author_is_not_self_notified_when_they_are_not_the_owner()
    {
        $creator = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $task = Task::factory()->create(['user_id' => $creator->id, 'assigned_to' => $assignee->id, 'status' => 'todo']);

        $task->addReply($creator, 'وضعیت چطور است؟');
        NudgeService::reconcile('tasks-controller:nudge', Task::class, $task->id);

        $creatorNotif = $creator->fresh()->notifications()->latest()->first();
        $this->assertNull($creatorNotif, 'the reply author (creator, who is not the assignee/owner) must not self-notify');

        $assigneeNotif = $assignee->fresh()->notifications()->latest()->first();
        $this->assertNotNull($assigneeNotif, 'assignee (task owner) must be notified when the creator replies');
    }

    public function test_task_reply_notifies_an_unmentioned_collaborator()
    {
        $creator = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $collaborator = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $task = Task::factory()->create(['user_id' => $creator->id, 'assigned_to' => $assignee->id, 'status' => 'todo']);
        TaskDetail::factory()->create(['task_id' => $task->id, 'collaborators' => [$collaborator->id]]);

        $task->addReply($assignee, 'شروع کردم.');
        NudgeService::reconcile('tasks-controller:nudge', Task::class, $task->id);

        $collaboratorNotif = $collaborator->fresh()->notifications()->latest()->first();
        $this->assertNotNull($collaboratorNotif, 'collaborator must be notified even without being @mentioned in the reply');
    }

    public function test_assignee_can_reply_to_a_task_from_the_edit_modal()
    {
        $creator = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $task = Task::factory()->create(['user_id' => $creator->id, 'assigned_to' => $assignee->id, 'status' => 'todo']);

        Livewire::actingAs($assignee)
            ->test(Main::class)
            ->call('editTask', $task->id)
            ->set('taskReplyForm.body', 'شروع کردم به بررسی.')
            ->call('postTaskReply')
            ->assertHasNoErrors()
            ->assertOk();

        $this->assertSame(2, $task->fresh()->replies()->count());
    }

    public function test_creator_can_reply_from_the_readonly_view_modal()
    {
        $creator = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $task = Task::factory()->create(['user_id' => $creator->id, 'assigned_to' => $assignee->id, 'status' => 'todo']);

        Livewire::actingAs($creator)
            ->test(Main::class)
            ->call('viewTask', $task->id)
            ->set('taskReplyForm.body', 'وضعیت چطور است؟')
            ->call('postTaskReply')
            ->assertHasNoErrors()
            ->assertOk();

        $this->assertSame(2, $task->fresh()->replies()->count());
    }

    public function test_unrelated_user_cannot_reply_to_a_task()
    {
        $creator = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $unrelated = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $task = Task::factory()->create(['user_id' => $creator->id, 'assigned_to' => $assignee->id, 'status' => 'todo']);

        // an unrelated user can't even open the modal (viewTask requires is_delegator, editTask
        // requires can_change_status) -- confirm both entry points stay closed to them.
        $unrelatedComponent = Livewire::actingAs($unrelated)->test(Main::class);
        $unrelatedComponent->call('viewTask', $task->id)->assertOk();
        $this->assertNull($unrelatedComponent->get('editingTaskId'), 'unrelated user must not be able to open the task at all via viewTask');

        $unrelatedComponent->call('editTask', $task->id)->assertOk();
        $this->assertNull($unrelatedComponent->get('editingTaskId'), 'unrelated user must not be able to open the task at all via editTask');

        $this->assertSame(1, $task->fresh()->replies()->count());
    }

    public function test_reply_with_only_attachment_and_no_body_passes_validation()
    {
        $creator = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $task = Task::factory()->ownedBy($creator)->withStatus('todo')->create();
        $file = UploadedFile::fake()->image('proof.jpg');

        Livewire::actingAs($creator)
            ->test(Main::class)
            ->call('editTask', $task->id)
            ->set('taskReplyForm.body', '')
            ->set('taskReplyForm.files', [$file])
            ->call('postTaskReply')
            ->assertHasNoErrors()
            ->assertOk();

        $this->assertSame(2, $task->fresh()->replies()->count());
    }

    public function test_remove_reply_attachment_drops_a_pending_upload()
    {
        $creator = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $task = Task::factory()->ownedBy($creator)->withStatus('todo')->create();
        $file1 = UploadedFile::fake()->image('a.jpg');
        $file2 = UploadedFile::fake()->image('b.jpg');

        $component = Livewire::actingAs($creator)
            ->test(Main::class)
            ->call('editTask', $task->id)
            ->assertOk()
            ->set('taskReplyForm.files', [$file1, $file2]);

        $this->assertCount(2, $component->instance()->taskReplyForm->files);

        $component->call('removeReplyAttachment', 0);
        $this->assertCount(1, $component->instance()->taskReplyForm->files);
        $this->assertSame('b.jpg', $component->instance()->taskReplyForm->files[0]->getClientOriginalName());
    }

    public function test_add_reply_rolls_back_reply_row_when_file_storage_fails()
    {
        $creator = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $task = Task::factory()->ownedBy($creator)->withStatus('todo')->create();

        $fakeImage = UploadedFile::fake()->image('x.jpg');
        $failingFile = new class('x.jpg', fopen($fakeImage->getPathname(), 'r')) extends \Illuminate\Http\Testing\File {
            public function storeAs($path, $name = null, $options = []): string
            {
                throw new \RuntimeException('simulated disk failure');
            }
        };

        try {
            $task->addReply($creator, '', [$failingFile]);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('simulated disk failure', $e->getMessage());
        }

        $this->assertSame(1, $task->fresh()->replies()->count());
    }

    public function test_add_reply_cleans_up_already_stored_files_when_a_later_file_fails()
    {
        Storage::fake('public');
        $creator = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $task = Task::factory()->ownedBy($creator)->withStatus('todo')->create();

        $goodFile = UploadedFile::fake()->image('good.jpg');

        $fakeImage = UploadedFile::fake()->image('bad.jpg');
        $failingFile = new class('bad.jpg', fopen($fakeImage->getPathname(), 'r')) extends \Illuminate\Http\Testing\File {
            public function storeAs($path, $name = null, $options = []): string
            {
                throw new \RuntimeException('simulated disk failure');
            }
        };

        try {
            $task->addReply($creator, '', [$goodFile, $failingFile]);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('simulated disk failure', $e->getMessage());
        }

        $this->assertSame(1, $task->fresh()->replies()->count());
        $this->assertEmpty(Storage::disk('public')->allFiles("task/replies/{$task->id}"));
    }

    public function test_editing_task_computed_returns_null_after_user_is_reassigned_away()
    {
        $creator = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $task = Task::factory()->create(['user_id' => $creator->id, 'assigned_to' => $assignee->id, 'status' => 'todo']);

        $assigneeComponent = Livewire::actingAs($assignee)->test(Main::class)->call('editTask', $task->id);
        $assigneeComponent->assertOk()->assertSet('editingTaskId', $task->id);
        $this->assertNotNull($assigneeComponent->instance()->editingTask());

        $task->update(['assigned_to' => $other->id]);

        $this->assertNull($assigneeComponent->instance()->editingTask());
    }

    public function test_search_with_zero_term_filters_to_matching_task_only()
    {
        $user = User::factory()->create(['status' => 'active']);

        Task::factory()->ownedBy($user)->withStatus('todo')->create(['title' => '0']);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['title' => 'alpha']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('totalCount.todo', 2)
            ->set('search', '0')
            ->assertSet('totalCount.todo', 1);
    }

    public function test_assignee_cannot_delete_a_task_they_did_not_create()
    {
        $creator = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($creator)->withStatus('todo')->create(['assigned_to' => $assignee->id]);

        Livewire::actingAs($assignee)
            ->test(Main::class)
            ->call('deleteTask', $task->id)
            ->assertHasNoErrors();

        $this->assertNotNull(Task::find($task->id));
    }

    public function test_stranger_cannot_change_status_of_task_they_do_not_own()
    {
        $creator = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($creator)->withStatus('todo')->create();

        Livewire::actingAs($stranger)
            ->test(Main::class)
            ->call('updateTaskStatus', $task->id, 'done');

        $this->assertSame('todo', $task->refresh()->status);
    }

    public function test_creator_cannot_undo_assignment_on_a_done_task()
    {
        $creator = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->delegatedBy($creator, $assignee)->withStatus('done')->create();

        Livewire::actingAs($creator)
            ->test(Main::class)
            ->call('undoAssignment', $task->id)
            ->assertHasNoErrors();

        $this->assertSame($assignee->id, $task->refresh()->assigned_to);
    }

    public function test_assignee_cannot_undo_assignment_of_a_task_delegated_to_them()
    {
        $creator = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->delegatedBy($creator, $assignee)->withStatus('todo')->create();

        Livewire::actingAs($assignee)
            ->test(Main::class)
            ->call('undoAssignment', $task->id)
            ->assertHasNoErrors();

        $this->assertSame($assignee->id, $task->refresh()->assigned_to);
    }

    public function test_update_task_status_with_invalid_status_is_ignored()
    {
        $creator = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($creator)->withStatus('todo')->create();

        Livewire::actingAs($creator)
            ->test(Main::class)
            ->call('updateTaskStatus', $task->id, 'archived')
            ->assertHasNoErrors();

        $this->assertSame('todo', $task->refresh()->status);
    }

    public function test_bulk_move_status_with_invalid_status_leaves_tasks_unchanged()
    {
        $creator = User::factory()->create(['status' => 'active']);
        $task1 = Task::factory()->ownedBy($creator)->withStatus('todo')->create();
        $task2 = Task::factory()->ownedBy($creator)->withStatus('todo')->create();

        Livewire::actingAs($creator)
            ->test(Main::class)
            ->set('selectedTasks', [$task1->id, $task2->id])
            ->call('bulkMoveStatus', 'archived')
            ->assertHasNoErrors();

        $this->assertSame('todo', $task1->refresh()->status);
        $this->assertSame('todo', $task2->refresh()->status);
    }

    public function test_bulk_assign_silently_drops_task_ids_the_actor_does_not_own()
    {
        $creator = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $mine = Task::factory()->ownedBy($creator)->withStatus('todo')->create();
        $foreign = Task::factory()->ownedBy($other)->withStatus('todo')->create();

        Livewire::actingAs($creator)
            ->test(Main::class)
            ->set('selectedTasks', [$mine->id, $foreign->id])
            ->call('bulkAssign', $assignee->id)
            ->assertHasNoErrors();

        $this->assertSame($assignee->id, $mine->refresh()->assigned_to);
        $this->assertNull($foreign->refresh()->assigned_to);
    }

    public function test_next_page_does_not_advance_past_the_last_page()
    {
        $user = User::factory()->create(['status' => 'active']);
        foreach (range(1, 5) as $i) {
            Task::factory()->ownedBy($user)->withStatus('todo')->create();
        }

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('nextPage', 'todo')
            ->assertSet('page.todo', 2)
            ->call('nextPage', 'todo')
            ->assertSet('page.todo', 2);
    }

    public function test_prev_page_does_not_decrement_below_one()
    {
        $user = User::factory()->create(['status' => 'active']);
        foreach (range(1, 5) as $i) {
            Task::factory()->ownedBy($user)->withStatus('todo')->create();
        }

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('nextPage', 'todo')
            ->assertSet('page.todo', 2)
            ->call('prevPage', 'todo')
            ->assertSet('page.todo', 1)
            ->call('prevPage', 'todo')
            ->assertSet('page.todo', 1);
    }

    public function test_toggle_selection_mode_clears_selected_tasks()
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('selectedTasks', [$task->id])
            ->call('toggleSelectionMode')
            ->assertSet('selectionMode', true)
            ->assertSet('selectedTasks', []);
    }

    public function test_toggle_task_selection_adds_and_removes_a_task_id()
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();

        $component = Livewire::actingAs($user)->test(Main::class);

        $component->call('toggleTaskSelection', $task->id)
            ->assertSet('selectedTasks', [$task->id]);

        $component->call('toggleTaskSelection', $task->id)
            ->assertSet('selectedTasks', []);
    }

    public function test_open_create_modal_resets_form_and_modal_state()
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('editTask', $task->id)
            ->assertSet('isEditMode', true)
            ->call('openCreateModal')
            ->assertOk()
            ->assertSet('isModalOpen', true)
            ->assertSet('isEditMode', false)
            ->assertSet('isReadOnly', false)
            ->assertSet('form.newTitle', '');
    }

    public function test_open_create_modal_after_canceling_an_edit_does_not_leave_the_create_dialog_pointed_at_the_previous_task()
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();

        $component = Livewire::actingAs($user)
            ->test(Main::class)
            ->call('editTask', $task->id)
            ->assertSet('editingTaskId', $task->id)
            ->call('openCreateModal')
            ->assertSet('editingTaskId', null);

        $component->set('taskReplyForm.body', 'stray reply')
            ->call('postTaskReply');

        $this->assertSame(1, $task->fresh()->replies()->count());
    }

    public function test_edit_task_is_refused_for_a_task_the_actor_can_no_longer_change()
    {
        $creator = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->delegatedBy($creator, $assignee)->withStatus('todo')->create();

        Livewire::actingAs($creator)
            ->test(Main::class)
            ->call('editTask', $task->id)
            ->assertOk()
            ->assertSet('editingTaskId', null)
            ->assertSet('isModalOpen', false);
    }

    public function test_view_task_succeeds_read_only_for_the_assignee_who_is_not_the_delegator()
    {
        $creator = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->delegatedBy($creator, $assignee)->withStatus('todo')->create();

        Livewire::actingAs($assignee)
            ->test(Main::class)
            ->call('viewTask', $task->id)
            ->assertOk()
            ->assertSet('editingTaskId', $task->id)
            ->assertSet('isModalOpen', true)
            ->assertSet('isReadOnly', true);
    }

    public function test_view_task_is_refused_for_a_stranger_with_no_relation_to_the_task()
    {
        $creator = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($creator)->withStatus('todo')->create();

        Livewire::actingAs($stranger)
            ->test(Main::class)
            ->call('viewTask', $task->id)
            ->assertOk()
            ->assertSet('editingTaskId', null)
            ->assertSet('isModalOpen', false);
    }

    public function test_create_task_persists_canonical_attachment_shape()
    {
        $user = User::factory()->create(['status' => 'active']);
        $file = UploadedFile::fake()->image('photo.jpg');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.newTitle', 'Tinker attachment probe')
            ->set('form.attachments', [$file])
            ->call('createTask')
            ->assertHasNoErrors()
            ->assertOk();

        $task = Task::with('detail')->where('title', 'Tinker attachment probe')->firstOrFail();
        $attachments = $task->detail->attachments;

        $this->assertCount(1, $attachments);
        $this->assertStringStartsWith('task/attachments/', $attachments[0]['path']);
        $this->assertSame('photo.jpg', $attachments[0]['name']);
        $this->assertSame('image/jpeg', $attachments[0]['mime']);
        $this->assertGreaterThan(0, $attachments[0]['size']);
    }

    public function test_create_task_rolls_back_task_row_when_attachment_storage_fails()
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user);

        $fakeImage = UploadedFile::fake()->image('x.jpg');
        $failingFile = new class('x.jpg', fopen($fakeImage->getPathname(), 'r')) extends \Illuminate\Http\Testing\File {
            public function store($path = '', $options = []): string|false
            {
                throw new \RuntimeException('simulated disk failure');
            }

            public function storeAs($path, $name = null, $options = []): string|false
            {
                throw new \RuntimeException('simulated disk failure');
            }
        };

        $form = Livewire::test(Main::class)->instance()->form;
        $form->newTitle = 'Rollback create probe';
        $form->attachments = [$failingFile];

        try {
            app(CreateTaskAction::class)->execute($form);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('simulated disk failure', $e->getMessage());
        }

        $this->assertTrue(Task::where('title', 'Rollback create probe')->doesntExist());
    }

    public function test_create_task_cleans_up_already_stored_files_when_a_later_file_fails()
    {
        Storage::fake('public');
        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user);

        $goodFile = UploadedFile::fake()->image('good.jpg');

        $fakeImage = UploadedFile::fake()->image('bad.jpg');
        $failingFile = new class('bad.jpg', fopen($fakeImage->getPathname(), 'r')) extends \Illuminate\Http\Testing\File {
            public function store($path = '', $options = []): string|false
            {
                throw new \RuntimeException('simulated disk failure');
            }

            public function storeAs($path, $name = null, $options = []): string|false
            {
                throw new \RuntimeException('simulated disk failure');
            }
        };

        $form = Livewire::test(Main::class)->instance()->form;
        $form->newTitle = 'Rollback cleanup probe';
        $form->attachments = [$goodFile, $failingFile];

        try {
            app(CreateTaskAction::class)->execute($form);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('simulated disk failure', $e->getMessage());
        }

        $this->assertTrue(Task::where('title', 'Rollback cleanup probe')->doesntExist());
        $this->assertEmpty(Storage::disk('public')->allFiles('task/attachments'));
    }

    public function test_update_task_rolls_back_changes_when_attachment_storage_fails()
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['title' => 'Original title']);

        $fakeImage = UploadedFile::fake()->image('x.jpg');
        $failingFile = new class('x.jpg', fopen($fakeImage->getPathname(), 'r')) extends \Illuminate\Http\Testing\File {
            public function store($path = '', $options = []): string|false
            {
                throw new \RuntimeException('simulated disk failure');
            }

            public function storeAs($path, $name = null, $options = []): string|false
            {
                throw new \RuntimeException('simulated disk failure');
            }
        };

        $form = Livewire::test(Main::class)->instance()->form;
        $form->newTitle = 'Changed title';
        $form->attachments = [$failingFile];
        $form->existingAttachments = [];

        try {
            app(UpdateTaskAction::class)->execute($task, $form);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('simulated disk failure', $e->getMessage());
        }

        $this->assertSame('Original title', $task->fresh()->title);
    }

    public function test_update_task_cleans_up_already_stored_files_when_a_later_file_fails()
    {
        Storage::fake('public');
        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['title' => 'Original title']);

        $goodFile = UploadedFile::fake()->image('good.jpg');

        $fakeImage = UploadedFile::fake()->image('bad.jpg');
        $failingFile = new class('bad.jpg', fopen($fakeImage->getPathname(), 'r')) extends \Illuminate\Http\Testing\File {
            public function store($path = '', $options = []): string|false
            {
                throw new \RuntimeException('simulated disk failure');
            }

            public function storeAs($path, $name = null, $options = []): string|false
            {
                throw new \RuntimeException('simulated disk failure');
            }
        };

        $form = Livewire::test(Main::class)->instance()->form;
        $form->newTitle = 'Changed title';
        $form->attachments = [$goodFile, $failingFile];
        $form->existingAttachments = [];

        try {
            app(UpdateTaskAction::class)->execute($task, $form);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('simulated disk failure', $e->getMessage());
        }

        $this->assertSame('Original title', $task->fresh()->title);
        $this->assertEmpty(Storage::disk('public')->allFiles('task/attachments'));
    }

    public function test_update_task_preserves_existing_attachments_and_appends_new_ones()
    {
        $user = User::factory()->create(['status' => 'active']);
        $seedFile = UploadedFile::fake()->image('seed.jpg');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.newTitle', 'Tinker merge probe')
            ->set('form.attachments', [$seedFile])
            ->call('createTask')
            ->assertHasNoErrors()
            ->assertOk();

        $task = Task::with('detail')->where('title', 'Tinker merge probe')->firstOrFail();
        $existingPath = $task->detail->attachments[0]['path'];

        $appendFile = UploadedFile::fake()->image('append.jpg');

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('editTask', $task->id)
            ->set('form.attachments', [$appendFile])
            ->call('updateTask')
            ->assertHasNoErrors()
            ->assertOk();

        $task->refresh()->load('detail');
        $attachments = $task->detail->attachments;

        $this->assertCount(2, $attachments);
        $this->assertSame($existingPath, $attachments[0]['path']);
        $this->assertSame('append.jpg', $attachments[1]['name']);
    }

    public function test_update_task_rejects_stale_save_when_detail_changed_since_form_opened()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.newTitle', 'Stale conflict probe')
            ->call('createTask')
            ->assertHasNoErrors()
            ->assertOk();

        $task = Task::with('detail')->where('title', 'Stale conflict probe')->firstOrFail();
        $originalDescription = $task->description;

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)
            ->test(Main::class)
            ->call('editTask', $task->id)
            ->assertOk();

        $this->travel(2)->seconds();
        $task->detail->touch();

        $component
            ->set('form.newDescription', 'updated while stale')
            ->call('updateTask')
            ->assertHasErrors(['form']);

        $this->assertSame($originalDescription, $task->fresh()->description);
    }

    public function test_remove_attachment_drops_a_pending_upload_from_the_form()
    {
        $user = User::factory()->create(['status' => 'active']);
        $file1 = UploadedFile::fake()->image('a.jpg');
        $file2 = UploadedFile::fake()->image('b.jpg');

        $component = Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.attachments', [$file1, $file2]);

        $this->assertCount(2, $component->instance()->form->attachments);

        $component->call('removeAttachment', 0);
        $this->assertCount(1, $component->instance()->form->attachments);
    }

    public function test_edit_task_populates_existing_attachments_on_the_form()
    {
        $user = User::factory()->create(['status' => 'active']);
        $seed = [['path' => 'task/attachments/seed.jpg', 'name' => 'seed.jpg', 'mime' => 'image/jpeg', 'size' => 100]];
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        $task->detail()->create(['attachments' => $seed]);

        $component = Livewire::actingAs($user)
            ->test(Main::class)
            ->call('editTask', $task->id)
            ->assertOk();

        $this->assertCount(1, $component->instance()->form->existingAttachments);

        $component->set('form.existingAttachments', []);
        $this->assertCount(0, $component->instance()->form->existingAttachments);
    }

    public function test_updated_form_department_id_clears_unit_and_section()
    {
        $user = User::factory()->create(['status' => 'active']);

        $component = Livewire::actingAs($user)->test(Main::class);
        $component->set('form.unit', 'Lab')
            ->set('form.section', 'Chemical')
            ->set('form.departmentId', 'QA');

        $this->assertNull($component->instance()->form->unit);
        $this->assertNull($component->instance()->form->section);
    }

    public function test_opening_via_url_narrows_the_board_to_only_the_focused_task_without_opening_a_modal()
    {
        $creator = User::factory()->create(['status' => 'active']);
        $focused = Task::factory()->ownedBy($creator)->withStatus('todo')->create();
        Task::factory()->ownedBy($creator)->withStatus('todo')->create();
        Task::factory()->ownedBy($creator)->withStatus('in-progress')->create();

        $component = Livewire::actingAs($creator)
            ->withQueryParams(['open' => $focused->id])
            ->test(Main::class)
            ->assertSet('isModalOpen', false)
            ->assertSet('editingTaskId', null);

        $this->assertSame([$focused->id], array_column($component->instance()->tasks['todo'], 'id'));
        $this->assertSame([], $component->instance()->tasks['in-progress']);
    }

    public function test_focused_task_is_shown_for_a_project_member_who_does_not_own_it()
    {
        $owner = User::factory()->create(['status' => 'active']);
        $member = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$member->id]]);
        $task = Task::factory()->forProject($project)->create([
            'user_id' => $owner->id,
            'assigned_to' => $owner->id,
            'status' => 'todo',
        ]);

        $component = Livewire::actingAs($member)
            ->withQueryParams(['open' => $task->id])
            ->test(Main::class)
            ->assertSet('isModalOpen', false);

        $this->assertSame([$task->id], array_column($component->instance()->tasks['todo'], 'id'));
    }

    public function test_focused_task_is_hidden_when_the_viewer_has_no_relation_to_it()
    {
        $owner = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($owner)->withStatus('todo')->create();

        $component = Livewire::actingAs($stranger)
            ->withQueryParams(['open' => $task->id])
            ->test(Main::class);

        $this->assertSame([], $component->instance()->tasks['todo']);
    }

    public function test_clear_focus_restores_the_normal_board()
    {
        $creator = User::factory()->create(['status' => 'active']);
        $focused = Task::factory()->ownedBy($creator)->withStatus('todo')->create();
        $other = Task::factory()->ownedBy($creator)->withStatus('todo')->create();

        $component = Livewire::actingAs($creator)
            ->withQueryParams(['open' => $focused->id])
            ->test(Main::class);

        $this->assertSame([$focused->id], array_column($component->instance()->tasks['todo'], 'id'));

        $component->call('clearFocus')->assertSet('open', null);

        $ids = array_column($component->instance()->tasks['todo'], 'id');
        $this->assertContains($focused->id, $ids);
        $this->assertContains($other->id, $ids);
    }

    public function test_assign_task_with_null_user_unassigns_the_task()
    {
        $creator = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->delegatedBy($creator, $assignee)->withStatus('todo')->create();

        Livewire::actingAs($assignee)
            ->test(Main::class)
            ->call('assignTask', $task->id, null)
            ->assertHasNoErrors();

        $this->assertNull($task->refresh()->assigned_to);
    }

    public function test_assign_task_to_self_keeps_my_tasks_tab_active()
    {
        $creator = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($creator)->withStatus('todo')->create();

        Livewire::actingAs($creator)
            ->test(Main::class)
            ->call('assignTask', $task->id, $creator->id)
            ->assertHasNoErrors()
            ->assertSet('activeTab', 'my-tasks');

        $this->assertSame($creator->id, $task->refresh()->assigned_to);
    }

    public function test_ticket_linked_task_cannot_be_reassigned_via_taskboard()
    {
        $requester = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $someoneElse = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->delegatedBy($requester, $assignee)->withStatus('todo')->create(['ticket_id' => Ticket::factory()->create()->id]);

        Livewire::actingAs($assignee)
            ->test(Main::class)
            ->call('assignTask', $task->id, $someoneElse->id);

        $this->assertSame($assignee->id, $task->refresh()->assigned_to, 'a ticket-linked task must not be reassignable from the TaskBoard side');
    }

    public function test_ticket_linked_task_undo_assignment_is_blocked()
    {
        $requester = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->delegatedBy($requester, $assignee)->withStatus('todo')->create(['ticket_id' => Ticket::factory()->create()->id]);

        Livewire::actingAs($requester)
            ->test(Main::class)
            ->call('undoAssignment', $task->id);

        $this->assertSame($assignee->id, $task->refresh()->assigned_to, 'undoing delegation must not be possible for a ticket-linked task');
    }

    public function test_ticket_linked_task_opens_in_read_only_mode()
    {
        $requester = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->delegatedBy($requester, $assignee)->withStatus('todo')->create(['ticket_id' => Ticket::factory()->create()->id]);

        Livewire::actingAs($assignee)
            ->test(Main::class)
            ->call('viewTask', $task->id)
            ->assertOk()
            ->assertSet('editingTaskId', $task->id)
            ->assertSet('isReadOnly', true)
            ->assertSet('isEditMode', false);
    }

    public function test_ticket_linked_task_status_cannot_be_updated_via_taskboard()
    {
        $requester = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($requester)->withStatus('todo')->create(['ticket_id' => Ticket::factory()->create()->id]);

        Livewire::actingAs($requester)
            ->test(Main::class)
            ->call('updateTaskStatus', $task->id, 'in-progress');

        $this->assertSame('todo', $task->fresh()->status, 'a ticket-linked task must not change status from the TaskBoard side');
    }

    public function test_ticket_linked_task_cannot_be_reordered_via_taskboard()
    {
        $requester = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($requester)->withStatus('todo')->create(['rank' => 'm', 'ticket_id' => Ticket::factory()->create()->id]);

        Livewire::actingAs($requester)
            ->test(Main::class)
            ->call('reorderTask', $task->id, null)
            ->assertStatus(403);

        $this->assertSame('m', $task->fresh()->rank, 'a ticket-linked task must not be reranked from the TaskBoard side');
    }

    public function test_search_filters_tasks_by_description()
    {
        $user = User::factory()->create(['status' => 'active']);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['title' => 'A', 'description' => 'Quarterly audit report']);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['title' => 'B', 'description' => 'Daily standup notes']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('totalCount.todo', 2)
            ->set('search', 'audit')
            ->assertSet('totalCount.todo', 1);
    }

    public function test_create_task_stores_deadline_at_noon()
    {
        $user = User::factory()->create(['status' => 'active']);
        $jalali = \Morilog\Jalali\Jalalian::fromCarbon(now()->addDays(40));

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.newTitle', 'Tinker noon deadline probe')
            ->set('form.deadlineYear', (string) $jalali->getYear())
            ->set('form.deadlineMonth', (string) $jalali->getMonth())
            ->set('form.deadlineDay', (string) $jalali->getDay())
            ->call('createTask')
            ->assertHasNoErrors()
            ->assertOk();

        $task = Task::where('title', 'Tinker noon deadline probe')->firstOrFail();

        $this->assertSame('12:00:00', $task->deadline->format('H:i:s'));
    }

    public function test_update_task_stores_deadline_at_noon()
    {
        $user = User::factory()->create(['status' => 'active']);
        $jalali = \Morilog\Jalali\Jalalian::fromCarbon(now()->addDays(40));
        $task = Task::factory()->ownedBy($user)->create(['deadline' => null]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('editTask', $task->id)
            ->set('form.deadlineYear', (string) $jalali->getYear())
            ->set('form.deadlineMonth', (string) $jalali->getMonth())
            ->set('form.deadlineDay', (string) $jalali->getDay())
            ->call('updateTask')
            ->assertHasNoErrors()
            ->assertOk();

        $task->refresh();

        $this->assertSame('12:00:00', $task->deadline->format('H:i:s'));
    }

    public function test_invalid_jalali_deadline_surfaces_form_deadline_error()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.newTitle', 'Tinker bad date probe')
            ->set('form.deadlineYear', '1403')
            ->set('form.deadlineMonth', '7')
            ->set('form.deadlineDay', '31')
            ->call('createTask')
            ->assertHasErrors(['form.deadline']);
    }

    public function test_create_task_validates_required_title()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.newTitle', '')
            ->call('createTask')
            ->assertHasErrors(['form.newTitle']);
    }

    public function test_my_tasks_tab_shows_owned_and_assigned_tasks()
    {
        $user = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);

        Task::factory()->ownedBy($user)->withStatus('todo')->create();
        Task::factory()->delegatedBy($other, $user)->withStatus('todo')->create();
        Task::factory()->delegatedBy($user, $assignee)->withStatus('todo')->create();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('activeTab', 'my-tasks')
            ->assertSet('totalCount.todo', 2);
    }

    public function test_assigned_tasks_tab_excludes_tasks_delegated_to_self()
    {
        $user = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);

        Task::factory()->delegatedBy($user, $other)->withStatus('todo')->create();
        Task::factory()->delegatedBy($user, $user)->withStatus('todo')->create();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('switchTab', 'assigned-tasks')
            ->assertSet('activeTab', 'assigned-tasks')
            ->assertSet('totalCount.todo', 1);
    }

    public function test_empty_board_renders_zero_counts_and_empty_columns()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('totalCount.todo', 0)
            ->assertSet('totalCount.in-progress', 0)
            ->assertSet('totalCount.pending', 0)
            ->assertSet('totalCount.done', 0)
            ->assertSet('doneTotalCount.done', 0)
            ->assertSet('tasks.todo', [])
            ->assertSet('tasks.done', [])
            ->assertSeeHtml('wire:key="taskboard-empty-none"');
    }

    public function test_task_card_renders_deep_link_copy_button()
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['title' => 'Deep link me']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSee(route('tasks', ['open' => $task->id]));
    }

    public function test_task_card_shows_checklist_progress(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        $task->detail()->create(['checklist' => [
            ['text' => 'a', 'done' => true],
            ['text' => 'b', 'done' => false],
            ['text' => 'c', 'done' => false],
        ]]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('tasks.todo.0.detail.checklist', [
                ['text' => 'a', 'done' => true],
                ['text' => 'b', 'done' => false],
                ['text' => 'c', 'done' => false],
            ])
            ->assertSee('1/3');
    }

    public function test_task_card_shows_reply_and_attachment_counts(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        $task->detail()->create(['attachments' => [
            ['path' => 'task/attachments/1/a.pdf', 'name' => 'a.pdf', 'mime' => 'application/pdf', 'size' => 100],
        ]]);
        app(ActivityLogger::class)->comment($task, $user, 'first note');
        app(ActivityLogger::class)->comment($task, $user, 'second note');

        $component = Livewire::actingAs($user)->test(Main::class);

        $this->assertSame(2, $component->instance()->tasks['todo'][0]['replies_count']);
        $component->assertSeeHtml('forum')->assertSeeHtml('attach_file');
    }

    public function test_label_chip_is_clickable_and_sets_the_label_filter(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['labels' => ['urgent-fix']]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSee('addLabelFilter(\'urgent-fix\')', false);
    }

    public function test_board_renders_card_field_toggle_and_card_parts_carry_data_col_markers(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Task::factory()->ownedBy($user)->withStatus('todo')->create([
            'description' => 'marker description',
            'priority' => 'high',
            'deadline' => now()->addDays(3),
            'labels' => ['urgent-fix'],
        ]);

        $component = Livewire::actingAs($user)->test(Main::class)->assertSee('نمایش و مخفی کردن بخش‌های کارت');

        foreach (['description', 'priority', 'deadline', 'labels'] as $key) {
            $component->assertSeeHtml('data-col="' . $key . '"');
        }
    }

    public function test_taskboard_exposes_idle_urgency_state_for_a_stale_in_progress_task()
    {
        $user = User::factory()->create(['status' => 'active']);
        Task::factory()->ownedBy($user)->withStatus('in-progress')->updatedAt('-8 days')->create(['deadline' => null]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('tasks.in-progress.0.urgency_state.kind', 'idle')
            ->assertSet('tasks.in-progress.0.urgency_state.label', 'چند روز بی‌تغیر');
    }

    public function test_taskboard_exposes_overdue_urgency_state_for_a_past_deadline_task()
    {
        $user = User::factory()->create(['status' => 'active']);
        Task::factory()->ownedBy($user)->withStatus('todo')->updatedAt('-2 days')->create(['deadline' => now()->subDays(3)]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('tasks.todo.0.urgency_state.kind', 'overdue')
            ->assertSet('tasks.todo.0.urgency_state.label', '3 روز تأخیر');
    }

    public function test_taskboard_suppresses_urgency_state_for_pending_tasks()
    {
        $user = User::factory()->create(['status' => 'active']);
        Task::factory()->ownedBy($user)->withStatus('pending')->create(['deadline' => now()->subDays(5)]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('tasks.pending.0.urgency_state.kind', null);
    }

    public function test_taskboard_proves_updated_at_is_projected_for_urgency_calc()
    {
        $user = User::factory()->create(['status' => 'active']);
        Task::factory()->ownedBy($user)->withStatus('todo')->updatedAt('-9 days')->create(['deadline' => null]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('tasks.todo.0.urgency_state.kind', 'idle');
    }

    public function test_archive_done_task_hides_it_from_done_column()
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('done')->updatedAt('-5 days')->create();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('totalCount.done', 1)
            ->call('archiveTask', $task->id)
            ->assertSet('totalCount.done', 0)
            ->assertSet('doneTotalCount.done', 0)
            ->assertSet('tasks.done', []);

        $this->assertNotNull($task->fresh()->archived_at);
    }

    public function test_show_archived_reveals_archived_done_tasks()
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('done')->updatedAt('-5 days')->create(['archived_at' => now()]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('showArchived', false)
            ->assertSet('totalCount.done', 0)
            ->call('toggleShowArchived')
            ->assertSet('showArchived', true)
            ->assertSet('totalCount.done', 1)
            ->assertSet('tasks.done.0.id', $task->id);
    }

    public function test_unarchive_restores_task_to_done_column()
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('done')->updatedAt('-5 days')->create(['archived_at' => now()]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('totalCount.done', 0)
            ->call('unarchiveTask', $task->id)
            ->assertSet('totalCount.done', 1)
            ->assertSet('tasks.done.0.id', $task->id);

        $this->assertNull($task->fresh()->archived_at);
    }

    public function test_archived_count_badge_stays_fresh_across_every_task_write()
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('done')->updatedAt('-5 days')->create();

        $board = Livewire::actingAs($user)->test(Main::class)->assertSet('archivedCount', 0);

        Task::factory()->ownedBy($user)->withStatus('done')->updatedAt('-5 days')->create(['archived_at' => now()]);
        $board->call('loadTasks')->assertSet('archivedCount', 1);

        $board->call('archiveTask', $task->id)->assertSet('archivedCount', 2);
        $board->call('unarchiveTask', $task->id)->assertSet('archivedCount', 1);
    }

    public function test_only_done_tasks_can_be_archived()
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('archiveTask', $task->id)
            ->assertHasNoErrors();

        $this->assertNull($task->fresh()->archived_at);
    }

    public function test_non_owner_cannot_archive_a_done_task()
    {
        $creator = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($creator)->withStatus('done')->updatedAt('-5 days')->create();

        Livewire::actingAs($stranger)
            ->test(Main::class)
            ->call('archiveTask', $task->id)
            ->assertHasNoErrors();

        $this->assertNull($task->fresh()->archived_at);
    }

    public function test_ticket_linked_task_can_be_archived_by_its_requester()
    {
        $requester = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($requester)->withStatus('done')->updatedAt('-5 days')->create(['ticket_id' => Ticket::factory()->create()->id]);

        Livewire::actingAs($requester)
            ->test(Main::class)
            ->call('archiveTask', $task->id)
            ->assertHasNoErrors();

        $this->assertNotNull($task->fresh()->archived_at);
    }

    public function test_ticket_linked_done_task_shows_the_archive_rail_and_archives_for_its_assignee()
    {
        $requester = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->delegatedBy($requester, $assignee)->withStatus('done')->updatedAt('-5 days')->create(['ticket_id' => Ticket::factory()->create()->id]);

        Livewire::actingAs($assignee)
            ->test(Main::class)
            ->assertSeeHtml("archiveTask({$task->id})")
            ->call('archiveTask', $task->id)
            ->assertHasNoErrors();

        $this->assertNotNull($task->fresh()->archived_at);
    }

    public function test_ticket_linked_done_task_cannot_be_archived_by_a_stranger()
    {
        $requester = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($requester)->withStatus('done')->updatedAt('-5 days')->create(['ticket_id' => Ticket::factory()->create()->id]);

        Livewire::actingAs($stranger)
            ->test(Main::class)
            ->call('archiveTask', $task->id)
            ->assertHasNoErrors();

        $this->assertNull($task->fresh()->archived_at);
    }

    public function test_toggle_show_archived_resets_done_page()
    {
        $user = User::factory()->create(['status' => 'active']);
        foreach (range(1, 6) as $i) {
            Task::factory()->ownedBy($user)->withStatus('done')->updatedAt('-5 days')->create();
        }

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('nextPage', 'done')
            ->assertSet('page.done', 2)
            ->call('toggleShowArchived')
            ->assertSet('showArchived', true)
            ->assertSet('page.done', 1);
    }

    public function test_switch_tab_resets_show_archived()
    {
        $user = User::factory()->create(['status' => 'active']);
        Task::factory()->ownedBy($user)->withStatus('done')->updatedAt('-5 days')->create(['archived_at' => now()]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('toggleShowArchived')
            ->assertSet('showArchived', true)
            ->call('switchTab', 'assigned-tasks')
            ->assertSet('showArchived', false);
    }

    public function test_search_finds_archived_done_task_regardless_of_show_archived()
    {
        $user = User::factory()->create(['status' => 'active']);
        Task::factory()->ownedBy($user)->withStatus('done')->updatedAt('-5 days')->create(['title' => 'Archived audit', 'archived_at' => now()]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('totalCount.done', 0)
            ->set('search', 'Archived audit')
            ->assertSet('totalCount.done', 1)
            ->assertSet('tasks.done.0.title', 'Archived audit');
    }

    public function test_reopening_an_archived_task_clears_archived_at()
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('done')->updatedAt('-5 days')->create(['archived_at' => now()]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('updateTaskStatus', $task->id, 'in-progress')
            ->assertHasNoErrors();

        $task->refresh();
        $this->assertSame('in-progress', $task->status);
        $this->assertNull($task->archived_at);
    }

    public function test_empty_state_flags_derive_window_archive_and_older_exist(): void
    {
        $presenter = new \App\Livewire\Dashboard\TaskBoard\Presentation\TaskBoardPresenter();

        $window = $presenter->emptyStateFlags('done', false, false, '', ['done' => 5]);
        $this->assertTrue($window['windowActive'], 'done column with no archive/all-done flags and no search is the active 45-day window');
        $this->assertTrue($window['olderExist'], 'olderExist when window active and doneTotalCount > 0');
        $this->assertFalse($window['archiveEmpty']);

        $windowEmpty = $presenter->emptyStateFlags('done', false, false, '', ['done' => 0]);
        $this->assertTrue($windowEmpty['windowActive']);
        $this->assertFalse($windowEmpty['olderExist'], 'olderExist false when doneTotalCount is 0');

        $archived = $presenter->emptyStateFlags('done', false, true, '', ['done' => 3]);
        $this->assertFalse($archived['windowActive'], 'window not active when showArchived is on');
        $this->assertFalse($archived['olderExist']);
        $this->assertTrue($archived['archiveEmpty'], 'archiveEmpty when done + showArchived + no search');

        $searching = $presenter->emptyStateFlags('done', false, false, 'keyword', ['done' => 9]);
        $this->assertFalse($searching['windowActive'], 'window not active while searching');
        $this->assertFalse($searching['olderExist']);
        $this->assertFalse($searching['archiveEmpty'], 'archiveEmpty suppressed while searching');

        $allDone = $presenter->emptyStateFlags('done', true, false, '', ['done' => 9]);
        $this->assertFalse($allDone['windowActive'], 'window not active when showAllDone is on');
        $this->assertFalse($allDone['olderExist']);

        $todo = $presenter->emptyStateFlags('todo', false, false, '', ['done' => 9]);
        $this->assertFalse($todo['windowActive']);
        $this->assertFalse($todo['archiveEmpty'], 'archiveEmpty only applies to the done column');
    }

    public function test_bulk_move_status_writes_activity_log_per_task(): void
    {
        $creator = User::factory()->create(['status' => 'active']);
        $task1 = Task::factory()->ownedBy($creator)->withStatus('todo')->create();
        $task2 = Task::factory()->ownedBy($creator)->withStatus('todo')->create();

        Livewire::actingAs($creator)
            ->test(Main::class)
            ->set('selectedTasks', [$task1->id, $task2->id])
            ->call('bulkMoveStatus', 'in-progress')
            ->assertHasNoErrors();

        $this->assertSame(2, $task1->replies()->count(), 'a mass update bypasses model events; the fix routes through per-task saves so each task gets its own status-change activity entry');
        $this->assertSame(2, $task2->replies()->count());
        $this->assertSame(\App\Enums\TaskActivityType::StatusChange, $task1->replies()->reorder()->latest('id')->first()->type);
    }

    public function test_bulk_assign_writes_assignment_activity_log(): void
    {
        $creator = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($creator)->withStatus('todo')->create();

        Livewire::actingAs($creator)
            ->test(Main::class)
            ->set('selectedTasks', [$task->id])
            ->call('bulkAssign', $assignee->id)
            ->assertHasNoErrors();

        $reply = $task->replies()->reorder()->latest('id')->first();
        $this->assertNotNull($reply, 'bulk assign must trigger the same saved-hook activity log a single assign would');
        $this->assertSame(\App\Enums\TaskActivityType::Assignment, $reply->type);
    }

    public function test_deadline_change_writes_deadline_change_activity_log(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['deadline' => now()->addDay()]);

        $this->actingAs($user);
        Task::find($task->id)->update(['deadline' => now()->addWeek()]);

        $reply = $task->replies()->reorder()->latest('id')->first();
        $this->assertSame(\App\Enums\TaskActivityType::DeadlineChange, $reply->type);
        $this->assertNotNull($reply->payload['from']);
        $this->assertNotNull($reply->payload['to']);
    }

    public function test_priority_change_writes_priority_change_activity_log(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['priority' => 'low']);

        $this->actingAs($user);
        Task::find($task->id)->update(['priority' => 'urgent']);

        $reply = $task->replies()->reorder()->latest('id')->first();
        $this->assertSame(\App\Enums\TaskActivityType::PriorityChange, $reply->type);
        $this->assertSame('low', $reply->payload['from']);
        $this->assertSame('urgent', $reply->payload['to']);
    }

    public function test_label_change_writes_added_and_removed_labels(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['labels' => ['a', 'b']]);

        $this->actingAs($user);
        Task::find($task->id)->update(['labels' => ['b', 'c']]);

        $reply = $task->replies()->reorder()->latest('id')->first();
        $this->assertSame(\App\Enums\TaskActivityType::LabelChange, $reply->type);
        $this->assertSame(['c'], $reply->payload['added']);
        $this->assertSame(['a'], $reply->payload['removed']);
    }

    public function test_project_change_writes_project_change_activity_log(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $project = \App\Models\Project::factory()->create(['owner_id' => $user->id]);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['project_id' => null]);

        $this->actingAs($user);
        Task::find($task->id)->update(['project_id' => $project->id]);

        $reply = $task->replies()->reorder()->latest('id')->first();
        $this->assertSame(\App\Enums\TaskActivityType::ProjectChange, $reply->type);
        $this->assertNull($reply->payload['from']);
        $this->assertSame($project->id, $reply->payload['to']);
    }

    public function test_task_comments_and_task_history_split_the_shared_replies_collection(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['priority' => 'low']);
        app(ActivityLogger::class)->comment($task, $user, 'a comment');
        $this->actingAs($user);
        Task::find($task->id)->update(['priority' => 'high']);

        $component = Livewire::actingAs($user)->test(Main::class)->call('editTask', $task->id);

        $comments = $component->instance()->taskComments;
        $history = $component->instance()->taskHistory;

        $this->assertCount(1, $comments);
        $this->assertSame(\App\Enums\TaskActivityType::Comment, $comments->first()->type);

        $this->assertCount(2, $history);
        $this->assertContains('تغییر اولویت', collect($history)->pluck('label')->all());
        $this->assertSame($user->name, collect($history)->firstWhere('label', 'تغییر اولویت')['user_name']);
    }

    public function test_history_tab_is_hidden_in_the_modal_for_a_task_that_belongs_to_a_project(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $project = \App\Models\Project::factory()->create(['owner_id' => $user->id]);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['project_id' => $project->id]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('editTask', $task->id)
            ->assertDontSee('formTab = \'history\'', false);
    }

    public function test_history_tab_is_visible_in_the_modal_for_a_standalone_task(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['project_id' => null]);
        $this->actingAs($user);
        Task::find($task->id)->update(['priority' => 'urgent']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('editTask', $task->id)
            ->assertSee('formTab = \'history\'', false)
            ->assertSee('تاریخچه')
            ->assertSee('اولویت وظیفه به «فوری» تغییر کرد.');
    }

    public function test_rank_for_priority_appends_after_last_rank_when_siblings_share_default_priority(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['rank' => 'm']);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['rank' => 'q']);

        $newRank = Task::rankForPriority(null, $user->id, 'todo', null);

        $this->assertSame(RankGenerator::between('q', null), $newRank, 'rankForPriority must sort after every existing rank in the column when the new task shares the default tier, not before');
    }

    public function test_rank_for_priority_on_empty_column_returns_a_valid_rank(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $rank = Task::rankForPriority(null, $user->id, 'todo', null);

        $this->assertNotEmpty($rank);
    }

    public function test_cross_project_move_places_task_by_priority_tier_in_new_column(): void
    {
        $creator = User::factory()->create(['status' => 'active']);
        $project = \App\Models\Project::factory()->create(['owner_id' => $creator->id, 'member_ids' => [], 'departments' => []]);
        Task::factory()->create(['project_id' => $project->id, 'status' => 'todo', 'priority' => 'urgent', 'rank' => 'b', 'assigned_to' => $creator->id, 'user_id' => $creator->id]);
        Task::factory()->create(['project_id' => $project->id, 'status' => 'todo', 'priority' => 'high', 'rank' => 'd', 'assigned_to' => $creator->id, 'user_id' => $creator->id]);
        Task::factory()->create(['project_id' => $project->id, 'status' => 'todo', 'priority' => 'low', 'rank' => 'f', 'assigned_to' => $creator->id, 'user_id' => $creator->id]);
        $task = Task::factory()->ownedBy($creator)->withStatus('todo')->create(['rank' => 'z', 'project_id' => null, 'priority' => 'medium']);

        Livewire::actingAs($creator)
            ->test(Main::class)
            ->call('editTask', $task->id)
            ->set('form.projectId', $project->id)
            ->call('updateTask')
            ->assertHasNoErrors()
            ->assertOk();

        $task->refresh();
        $this->assertSame($project->id, $task->project_id);
        $this->assertSame(RankGenerator::between('d', 'f'), $task->rank, 'a cross-project move must place the task by its priority tier among the new project\'s siblings, not always jump to the bottom or top');
    }

    public function test_duplicate_task_clones_labels_priority_and_resets_status_and_checklist(): void
    {
        $creator = User::factory()->create(['status' => 'active']);
        $project = \App\Models\Project::factory()->create(['owner_id' => $creator->id, 'member_ids' => [], 'departments' => []]);
        $task = Task::factory()->ownedBy($creator)->withStatus('done')->create([
            'project_id' => $project->id,
            'labels' => ['urgent-fix'],
            'priority' => 'high',
        ]);
        $task->detail()->create(['checklist' => [['text' => 'step 1', 'done' => true], ['text' => 'step 2', 'done' => false]]]);

        Livewire::actingAs($creator)
            ->test(Main::class)
            ->call('duplicateTask', $task->id)
            ->assertHasNoErrors()
            ->assertOk();

        $duplicate = Task::where('title', 'like', $task->title . '%')->where('id', '!=', $task->id)->firstOrFail();

        $this->assertSame('todo', $duplicate->status, 'duplicate must always reset to todo regardless of source status');
        $this->assertSame(['urgent-fix'], $duplicate->labels);
        $this->assertSame('high', $duplicate->priority->value);
        $this->assertSame($project->id, $duplicate->project_id);
        $this->assertSame($creator->id, $duplicate->assigned_to, 'acting user must become the assignee, not carried over from source');
        $this->assertSame($creator->id, $duplicate->user_id);

        $checklist = $duplicate->detail->checklist;
        $this->assertCount(2, $checklist);
        $this->assertFalse($checklist[0]['done'], 'every cloned checklist item must reset to not-done');
        $this->assertFalse($checklist[1]['done']);
    }

    public function test_duplicate_task_preserves_checklist_weights_instead_of_silently_unweighting_them(): void
    {
        $creator = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($creator)->withStatus('todo')->create();
        $task->detail()->create(['checklist' => [
            ['text' => 'step 1', 'done' => true, 'weight' => 70],
            ['text' => 'step 2', 'done' => false, 'weight' => 30],
        ]]);

        Livewire::actingAs($creator)
            ->test(Main::class)
            ->call('duplicateTask', $task->id)
            ->assertHasNoErrors()
            ->assertOk();

        $duplicate = Task::where('title', 'like', $task->title . '%')->where('id', '!=', $task->id)->firstOrFail();
        $checklist = $duplicate->detail->checklist;

        $this->assertSame(70, $checklist[0]['weight'], 'duplicate must keep the source checklist weights, not silently drop them to unweighted');
        $this->assertSame(30, $checklist[1]['weight']);
    }

    public function test_duplicate_task_copies_collaborators_and_responsible_user(): void
    {
        $creator = User::factory()->create(['status' => 'active']);
        $collaborator = User::factory()->create(['status' => 'active']);
        $responsible = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($creator)->withStatus('todo')->create();
        $task->detail()->create(['collaborators' => [$collaborator->id], 'responsible_user_id' => $responsible->id]);

        Livewire::actingAs($creator)
            ->test(Main::class)
            ->call('duplicateTask', $task->id)
            ->assertHasNoErrors()
            ->assertOk();

        $duplicate = Task::where('title', 'like', $task->title . '%')->where('id', '!=', $task->id)->firstOrFail();

        $this->assertSame([$collaborator->id], $duplicate->detail->collaborators);
        $this->assertSame($responsible->id, $duplicate->detail->responsible_user_id);
    }

    public function test_duplicate_task_is_refused_for_ticket_linked_task(): void
    {
        $creator = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($creator)->withStatus('todo')->create(['ticket_id' => \App\Models\Ticket::factory()->create()->id]);
        $before = Task::count();

        Livewire::actingAs($creator)
            ->test(Main::class)
            ->call('duplicateTask', $task->id)
            ->assertHasNoErrors()
            ->assertOk();

        $this->assertSame($before, Task::count(), 'a ticket-linked task must not be duplicatable');
    }

    public function test_duplicate_task_is_refused_for_archived_task(): void
    {
        $creator = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($creator)->withStatus('done')->updatedAt('-5 days')->create(['archived_at' => now()]);
        $before = Task::count();

        Livewire::actingAs($creator)
            ->test(Main::class)
            ->call('duplicateTask', $task->id)
            ->assertHasNoErrors()
            ->assertOk();

        $this->assertSame($before, Task::count(), 'an archived task must not be duplicatable');
    }

    public function test_deadline_filter_overdue_excludes_done_and_archived(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['deadline' => now()->subDays(2)]);
        Task::factory()->ownedBy($user)->withStatus('done')->create(['deadline' => now()->subDays(2)]);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['deadline' => now()->subDays(2), 'archived_at' => now()]);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['deadline' => now()->addDays(2)]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('setDeadlineFilter', 'overdue')
            ->assertSet('deadlineFilter', 'overdue')
            ->assertSet('totalCount.todo', 1);
    }

    public function test_deadline_filter_today_does_not_flag_task_due_later_today_as_overdue(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['deadline' => now()->startOfDay()->addHours(4)]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('setDeadlineFilter', 'overdue')
            ->assertSet('totalCount.todo', 0, 'a task due later today must not be misclassified as overdue');
    }

    public function test_deadline_filter_toggles_off_on_second_click(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('setDeadlineFilter', 'today')
            ->assertSet('deadlineFilter', 'today')
            ->call('setDeadlineFilter', 'today')
            ->assertSet('deadlineFilter', '');
    }

    public function test_project_filter_scopes_board_to_selected_project(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $project = \App\Models\Project::factory()->create(['owner_id' => $user->id, 'member_ids' => [], 'departments' => []]);
        $inProject = Task::factory()->ownedBy($user)->withStatus('todo')->create(['project_id' => $project->id]);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['project_id' => null]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('projectFilter', $project->id)
            ->assertSet('totalCount.todo', 1)
            ->assertSet('tasks.todo.0.id', $inProject->id);
    }

    public function test_priority_filter_scopes_board_to_selected_priority(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['priority' => 'urgent']);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['priority' => 'low']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('priorityFilter', 'urgent')
            ->assertSet('totalCount.todo', 1);
    }

    public function test_active_filter_count_reflects_only_currently_set_filters(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $component = Livewire::actingAs($user)->test(Main::class);
        $this->assertSame(0, $component->instance()->activeFilterCount);

        $component->set('priorityFilter', 'urgent')->set('deadlineFilter', 'overdue');
        $this->assertSame(2, $component->instance()->activeFilterCount);

        $component->set('priorityFilter', '');
        $this->assertSame(1, $component->instance()->activeFilterCount);
    }

    public function test_clear_filters_resets_every_filter_and_reloads_the_board(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['priority' => 'urgent']);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['priority' => 'low']);

        $component = Livewire::actingAs($user)->test(Main::class)
            ->set('priorityFilter', 'urgent')
            ->set('deadlineFilter', 'overdue')
            ->assertSet('totalCount.todo', 0);

        $component->call('clearFilters')
            ->assertSet('priorityFilter', '')
            ->assertSet('deadlineFilter', '')
            ->assertSet('totalCount.todo', 2);

        $this->assertSame(0, $component->instance()->activeFilterCount);
    }

    public function test_label_filter_matches_exact_label_only(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['labels' => ['urgent-fix']]);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['labels' => ['urgent-fix-followup']]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('labelFilter', ['urgent-fix'])
            ->assertSet('totalCount.todo', 1, 'JSON-contains matching must be exact, not a substring match');
    }

    public function test_label_filter_is_an_and_match_across_multiple_selected_labels(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $both = Task::factory()->ownedBy($user)->withStatus('todo')->create(['labels' => ['urgent', 'backend']]);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['labels' => ['urgent']]);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['labels' => ['backend']]);

        $tasks = Livewire::actingAs($user)->test(Main::class)
            ->set('labelFilter', ['urgent', 'backend'])
            ->instance()->tasks['todo'];

        $this->assertCount(1, $tasks);
        $this->assertSame($both->id, $tasks[0]['id']);
    }

    public function test_add_label_filter_appends_without_overwriting_existing_selection(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['labels' => ['urgent']]);

        $component = Livewire::actingAs($user)->test(Main::class)
            ->set('labelFilter', ['urgent'])
            ->call('addLabelFilter', 'backend');

        $this->assertSame(['urgent', 'backend'], $component->get('labelFilter'));
    }

    public function test_toggle_label_filter_adds_then_removes_a_label(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['labels' => ['urgent']]);

        $component = Livewire::actingAs($user)->test(Main::class)
            ->call('toggleLabelFilter', 'urgent');

        $component->assertSet('labelFilter', ['urgent']);

        $component->call('toggleLabelFilter', 'urgent')
            ->assertSet('labelFilter', []);
    }

    public function test_scheme_filter_scopes_to_matching_tasks(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $matching = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        TaskDetail::factory()->create(['task_id' => $matching->id, 'scheme' => 'Scheme A']);
        $other = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        TaskDetail::factory()->create(['task_id' => $other->id, 'scheme' => 'Scheme B']);

        $tasks = Livewire::actingAs($user)->test(Main::class)
            ->set('schemeFilter', 'Scheme A')
            ->instance()->tasks['todo'];

        $this->assertCount(1, $tasks);
        $this->assertSame($matching->id, $tasks[0]['id']);
    }

    public function test_scheme_options_lists_only_the_users_own_distinct_schemes(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $own = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        TaskDetail::factory()->create(['task_id' => $own->id, 'scheme' => 'Scheme A']);
        $others = Task::factory()->ownedBy($stranger)->withStatus('todo')->create();
        TaskDetail::factory()->create(['task_id' => $others->id, 'scheme' => 'Scheme Z']);

        $options = Livewire::actingAs($user)->test(Main::class)->get('schemeOptions');

        $this->assertSame(['Scheme A'], $options);
    }

    public function test_unit_filter_scopes_to_matching_tasks(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $matching = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        TaskDetail::factory()->create(['task_id' => $matching->id, 'unit' => 'Lab']);
        $other = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        TaskDetail::factory()->create(['task_id' => $other->id, 'unit' => 'Field']);

        $tasks = Livewire::actingAs($user)->test(Main::class)
            ->set('unitFilter', 'Lab')
            ->instance()->tasks['todo'];

        $this->assertCount(1, $tasks);
        $this->assertSame($matching->id, $tasks[0]['id']);
    }

    public function test_section_filter_scopes_to_matching_tasks(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $matching = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        TaskDetail::factory()->create(['task_id' => $matching->id, 'section' => 'Chemical']);
        $other = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        TaskDetail::factory()->create(['task_id' => $other->id, 'section' => 'Mechanical']);

        $tasks = Livewire::actingAs($user)->test(Main::class)
            ->set('sectionFilter', 'Chemical')
            ->instance()->tasks['todo'];

        $this->assertCount(1, $tasks);
        $this->assertSame($matching->id, $tasks[0]['id']);
    }

    public function test_unit_and_section_options_list_only_the_users_own_distinct_values(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $own = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        TaskDetail::factory()->create(['task_id' => $own->id, 'unit' => 'Lab', 'section' => 'Chemical']);
        $ownDup = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        TaskDetail::factory()->create(['task_id' => $ownDup->id, 'unit' => 'Lab', 'section' => 'Mechanical']);
        $others = Task::factory()->ownedBy($stranger)->withStatus('todo')->create();
        TaskDetail::factory()->create(['task_id' => $others->id, 'unit' => 'Field', 'section' => 'Electrical']);

        $component = Livewire::actingAs($user)->test(Main::class);

        $this->assertSame(['Lab'], $component->get('unitOptions'));
        $this->assertSame(['Chemical', 'Mechanical'], $component->get('sectionOptions'));
    }

    public function test_assignee_filter_scopes_to_matching_task(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $colleague = User::factory()->create(['status' => 'active']);
        $delegated = Task::factory()->delegatedBy($user, $colleague)->withStatus('todo')->create();
        Task::factory()->ownedBy($user)->withStatus('todo')->create();

        $tasks = Livewire::actingAs($user)->test(Main::class)
            ->set('activeTab', 'assigned-tasks')
            ->set('assigneeFilter', $colleague->id)
            ->instance()->tasks['todo'];

        $this->assertCount(1, $tasks);
        $this->assertSame($delegated->id, $tasks[0]['id']);
    }

    public function test_assignee_options_lists_self_and_delegated_colleagues_only(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $colleague = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        Task::factory()->delegatedBy($user, $colleague)->withStatus('todo')->create();
        Task::factory()->create(['user_id' => $stranger->id, 'assigned_to' => $stranger->id, 'status' => 'todo']);

        $options = Livewire::actingAs($user)->test(Main::class)->get('assigneeOptions');

        $this->assertSame([$colleague->id => $colleague->name], $options);
    }

    public function test_action_source_domain_filter_scopes_to_matching_tasks(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $matching = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        TaskDetail::factory()->create(['task_id' => $matching->id, 'action_source_domain' => 'email']);
        $other = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        TaskDetail::factory()->create(['task_id' => $other->id, 'action_source_domain' => 'call']);

        $tasks = Livewire::actingAs($user)->test(Main::class)
            ->set('actionSourceDomainFilter', 'email')
            ->instance()->tasks['todo'];

        $this->assertCount(1, $tasks);
        $this->assertSame($matching->id, $tasks[0]['id']);
    }

    public function test_action_source_options_are_narrowed_by_selected_domain(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $emailTask = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        TaskDetail::factory()->create(['task_id' => $emailTask->id, 'action_source_domain' => 'email', 'action_source' => 'inbox']);
        $callTask = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        TaskDetail::factory()->create(['task_id' => $callTask->id, 'action_source_domain' => 'call', 'action_source' => 'hotline']);

        $component = Livewire::actingAs($user)->test(Main::class);

        $this->assertSame(['call', 'email'], $component->get('actionSourceDomainOptions'));
        $this->assertSame(['hotline', 'inbox'], $component->get('actionSourceOptions'));

        $component->set('actionSourceDomainFilter', 'email');

        $this->assertSame(['inbox'], $component->get('actionSourceOptions'));
    }

    public function test_setting_action_source_domain_filter_resets_action_source_filter(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)->test(Main::class)
            ->set('actionSourceFilter', 'inbox')
            ->set('actionSourceDomainFilter', 'email')
            ->assertSet('actionSourceFilter', '');
    }

    public function test_label_input_renders_datalist_suggestions_from_label_options(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['labels' => ['urgent-fix']]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSeeHtml('<datalist id="task-label-suggestions">')
            ->assertSeeHtml('<option value="urgent-fix"></option>');
    }

    public function test_priority_chip_returns_null_for_empty_or_unknown_priority(): void
    {
        $presenter = new TaskBoardPresenter();

        $this->assertNull($presenter->priorityChip(null));
        $this->assertNull($presenter->priorityChip(''));
        $this->assertNull($presenter->priorityChip('not-a-real-priority'));
    }

    public function test_priority_chip_returns_label_class_and_urgent_flag_for_every_case(): void
    {
        $presenter = new TaskBoardPresenter();

        foreach (TaskPriority::cases() as $case) {
            $chip = $presenter->priorityChip($case->value);

            $this->assertSame($case->getLabel(), $chip['label']);
            $this->assertSame($case === TaskPriority::Urgent, $chip['isUrgent']);
            $this->assertNotEmpty($chip['class']);
        }
    }

    public function test_column_pager_meta_computes_last_page_and_boundary_flags(): void
    {
        $presenter = new TaskBoardPresenter();

        $first = $presenter->columnPagerMeta(25, 10, 1);
        $this->assertSame(3, $first['lastPage']);
        $this->assertTrue($first['isFirstPage']);
        $this->assertFalse($first['isLastPage']);

        $last = $presenter->columnPagerMeta(25, 10, 3);
        $this->assertFalse($last['isFirstPage']);
        $this->assertTrue($last['isLastPage']);
    }

    public function test_column_pager_meta_floors_last_page_to_one_and_handles_zero_per_page(): void
    {
        $presenter = new TaskBoardPresenter();

        $this->assertSame(1, $presenter->columnPagerMeta(0, 10)['lastPage']);
        $this->assertSame(1, $presenter->columnPagerMeta(3, 10)['lastPage']);
        $this->assertSame(1, $presenter->columnPagerMeta(5, 0)['lastPage']);
    }

    public function test_reply_attachment_meta_resolves_url_and_image_flag(): void
    {
        $presenter = new TaskBoardPresenter();

        $image = $presenter->replyAttachmentMeta(['path' => 'replies/a.jpg', 'mime' => 'image/png', 'name' => 'a.jpg']);
        $this->assertStringContainsString('replies/a.jpg', $image['url']);
        $this->assertTrue($image['isImage']);

        $doc = $presenter->replyAttachmentMeta(['path' => 'replies/b.pdf', 'mime' => 'application/pdf']);
        $this->assertFalse($doc['isImage']);

        $empty = $presenter->replyAttachmentMeta(['path' => null, 'mime' => null]);
        $this->assertSame('', $empty['url']);
        $this->assertFalse($empty['isImage']);
    }

    public function test_deadline_filter_options_bundles_labels_with_counts(): void
    {
        $presenter = new TaskBoardPresenter();

        $opts = $presenter->deadlineFilterOptions(['overdue' => 2, 'today' => 0, 'week' => 5]);
        $this->assertSame([
            ['value' => 'overdue', 'label' => 'سررسید گذشته', 'count' => 2],
            ['value' => 'today', 'label' => 'امروز', 'count' => 0],
            ['value' => 'week', 'label' => 'این هفته', 'count' => 5],
        ], $opts);

        $this->assertSame(0, $presenter->deadlineFilterOptions([])[1]['count']);
    }

    public function test_card_meta_bundles_derived_values_for_personal_board(): void
    {
        $presenter = new TaskBoardPresenter();

        $task = [
            'id' => 7,
            'status' => 'pending',
            'is_archived' => false,
            'can_change_status' => true,
            'ticket_id' => null,
            'deadline' => '2099-01-01 00:00:00',
            'deadline_formatted' => '1403/10/11',
            'urgency_state' => ['score' => 0.5, 'kind' => 'idle', 'label' => 'بیکار'],
            'detail' => [
                'checklist' => [['done' => true, 'text' => 'a'], ['done' => false, 'text' => 'b']],
                'attachments' => [['path' => 'x.pdf', 'mime' => 'application/pdf']],
                'state' => null,
                'department_id' => 'ENG',
                'collaborator_users' => [],
            ],
        ];

        $card = $presenter->cardMeta($task, true, ['ENG' => 'مهندسی']);

        $this->assertSame(2, $card['checklistTotal']);
        $this->assertSame(1, $card['checklistDone']);
        $this->assertSame(1, $card['attachmentsCount']);
        $this->assertTrue($card['canCyclePriority']);
        $this->assertSame('addLabelFilter', $card['labelFilterMethod']);
        $this->assertTrue($card['isPending']);
        $this->assertTrue($card['isUrgent']);
        $this->assertTrue($card['hasDeadline']);
        $this->assertSame("مهلت: 1403/10/11\nبیکار", $card['deadlineTitle']);
        $this->assertSame('isFavorite(7)', $card['isFavoriteExpression']);
        $this->assertSame('!showFavoritesOnly || isFavorite(7)', $card['showTaskExpression']);
        $this->assertSame('مهندسی', $card['departmentLabel']);
        $this->assertSame('error', $card['colState']['color']);
        $this->assertNull($card['stateChip']);
        $this->assertNull($card['responsibleUser']);
    }

    public function test_card_meta_action_source_chip_prefers_source_over_domain_and_is_null_when_both_blank(): void
    {
        $presenter = new TaskBoardPresenter();

        $withBoth = $presenter->cardMeta([
            'id' => 1, 'status' => 'todo', 'is_archived' => false, 'can_change_status' => true, 'ticket_id' => null,
            'detail' => ['action_source' => 'ایمیل مدیر', 'action_source_domain' => 'ایمیل'],
        ], true, []);
        $this->assertSame(['label' => 'ایمیل مدیر', 'source' => 'ایمیل مدیر', 'domain' => 'ایمیل'], $withBoth['actionSourceChip']);

        $domainOnly = $presenter->cardMeta([
            'id' => 2, 'status' => 'todo', 'is_archived' => false, 'can_change_status' => true, 'ticket_id' => null,
            'detail' => ['action_source' => '', 'action_source_domain' => 'ایمیل'],
        ], true, []);
        $this->assertSame('ایمیل', $domainOnly['actionSourceChip']['label']);

        $neither = $presenter->cardMeta([
            'id' => 3, 'status' => 'todo', 'is_archived' => false, 'can_change_status' => true, 'ticket_id' => null,
            'detail' => ['action_source' => '', 'action_source_domain' => null],
        ], true, []);
        $this->assertNull($neither['actionSourceChip']);
    }

    public function test_load_tasks_eager_load_surfaces_action_source_columns_on_the_detail_array(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        TaskDetail::factory()->create([
            'task_id' => $task->id,
            'action_source' => 'ایمیل مدیر',
            'action_source_domain' => 'ایمیل',
        ]);

        $tasks = Livewire::actingAs($user)->test(Main::class)->instance()->tasks['todo'];

        $this->assertSame('ایمیل مدیر', $tasks[0]['detail']['action_source']);
        $this->assertSame('ایمیل', $tasks[0]['detail']['action_source_domain']);
    }

    public function test_card_meta_kanban_board_switches_expressions_and_methods(): void
    {
        $presenter = new TaskBoardPresenter();

        $task = ['id' => 9, 'status' => 'todo', 'is_archived' => false, 'can_change_status' => true, 'ticket_id' => null, 'detail' => []];

        $card = $presenter->cardMeta($task, false, []);

        $this->assertSame('addKanbanLabelFilter', $card['labelFilterMethod']);
        $this->assertSame('false', $card['isFavoriteExpression']);
        $this->assertSame('true', $card['showTaskExpression']);
        $this->assertFalse($card['hasDeadline']);
        $this->assertNull($card['deadlineTitle']);
        $this->assertFalse($card['isPending']);

        $archived = $presenter->cardMeta(['id' => 1, 'status' => 'todo', 'is_archived' => true, 'can_change_status' => true, 'ticket_id' => null, 'detail' => []], false, []);
        $this->assertFalse($archived['canCyclePriority']);

        $ticket = $presenter->cardMeta(['id' => 1, 'status' => 'todo', 'is_archived' => false, 'can_change_status' => true, 'ticket_id' => 5, 'detail' => []], false, []);
        $this->assertFalse($ticket['canCyclePriority']);
    }

    public function test_card_meta_deadline_title_overdue_and_done_branches(): void
    {
        $presenter = new TaskBoardPresenter();

        $overdue = $presenter->cardMeta([
            'id' => 1, 'status' => 'todo', 'deadline' => '2020-01-01 00:00:00', 'deadline_formatted' => '1398/10/11',
            'urgency_state' => ['score' => 1.0, 'kind' => 'overdue', 'label' => 'هرز'], 'detail' => [],
        ], true, []);
        $this->assertStringContainsString('این وظیفه از مهلت گذشته است', $overdue['deadlineTitle']);
        $this->assertStringNotContainsString('هرز', $overdue['deadlineTitle']);

        $waiting = $presenter->cardMeta([
            'id' => 1, 'status' => 'pending', 'deadline' => '2020-01-01 00:00:00', 'deadline_formatted' => '1398/10/11',
            'urgency_state' => ['score' => 0.0, 'kind' => null, 'label' => null], 'detail' => [],
        ], true, []);
        $this->assertStringNotContainsString('این وظیفه از مهلت گذشته است', $waiting['deadlineTitle']);

        $done = $presenter->cardMeta([
            'id' => 1, 'status' => 'done', 'deadline' => '2020-01-01 00:00:00', 'deadline_formatted' => '1398/10/11',
            'urgency_state' => ['score' => 0.0, 'kind' => null, 'label' => 'هرز'], 'detail' => [],
        ], true, []);
        $this->assertSame("مهلت: 1398/10/11\nهرز", $done['deadlineTitle']);
    }

    public function test_create_task_rejects_description_over_5000_chars(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.newTitle', 'Tinker long description probe')
            ->set('form.newDescription', str_repeat('x', 5001))
            ->call('createTask')
            ->assertHasErrors(['form.newDescription']);
    }

    public function test_create_task_rejects_invalid_priority(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.newTitle', 'Tinker bad priority probe')
            ->set('form.priority', 'bogus')
            ->call('createTask')
            ->assertHasErrors(['form.priority']);
    }

    public function test_create_task_accepts_valid_priority(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.newTitle', 'Tinker valid priority probe')
            ->set('form.priority', 'high')
            ->call('createTask')
            ->assertHasNoErrors()
            ->assertOk();

        $task = Task::where('title', 'Tinker valid priority probe')->firstOrFail();
        $this->assertSame('high', $task->priority->value);
    }

    public function test_create_task_rejects_checklist_weight_above_100(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.newTitle', 'Tinker checklist weight overflow probe')
            ->set('form.checklist', [['text' => 'step', 'done' => false, 'weight' => 101]])
            ->call('createTask')
            ->assertHasErrors(['form.checklist.0.weight']);
    }

    public function test_create_task_rejects_negative_checklist_weight(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.newTitle', 'Tinker checklist negative weight probe')
            ->set('form.checklist', [['text' => 'step', 'done' => false, 'weight' => -1]])
            ->call('createTask')
            ->assertHasErrors(['form.checklist.0.weight']);
    }

    public function test_create_task_accepts_boundary_checklist_weights(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.newTitle', 'Tinker checklist boundary weight probe')
            ->set('form.checklist', [
                ['text' => 'step 1', 'done' => false, 'weight' => 0],
                ['text' => 'step 2', 'done' => true, 'weight' => 50],
                ['text' => 'step 3', 'done' => true, 'weight' => 100],
            ])
            ->call('createTask')
            ->assertHasNoErrors()
            ->assertOk();

        $task = Task::where('title', 'Tinker checklist boundary weight probe')->firstOrFail();
        $this->assertEquals(0, $task->detail->checklist[0]['weight']);
        $this->assertEquals(50, $task->detail->checklist[1]['weight']);
        $this->assertEquals(100, $task->detail->checklist[2]['weight']);
    }

    public function test_cycle_priority_advances_priority_for_an_actionable_task(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['priority' => null]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('cyclePriority', $task->id)
            ->assertHasNoErrors();

        $this->assertSame(TaskPriority::Low, Task::find($task->id)->priority);
    }

    public function test_cycle_priority_is_ignored_for_a_task_the_user_cannot_change_status_on(): void
    {
        $creator = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($creator)->withStatus('todo')->create(['priority' => 'low']);

        Livewire::actingAs($stranger)
            ->test(Main::class)
            ->call('cyclePriority', $task->id)
            ->assertHasNoErrors();

        $this->assertSame(TaskPriority::Low, Task::find($task->id)->priority);
    }

    public function test_cycle_priority_to_urgent_moves_task_ahead_of_lower_priority_sibling(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $sibling = Task::factory()->ownedBy($user)->withStatus('todo')->create(['priority' => 'low', 'rank' => 'b']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['priority' => 'high', 'rank' => 'd']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('cyclePriority', $task->id)
            ->assertHasNoErrors();

        $fresh = Task::find($task->id);
        $this->assertSame(TaskPriority::Urgent, $fresh->priority);
        $this->assertTrue($fresh->rank < $sibling->fresh()->rank);
    }

    public function test_update_task_status_to_done_without_state_is_blocked(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();

        $component = Livewire::actingAs($user)->test(Main::class)
            ->call('updateTaskStatus', $task->id, 'done');

        $component->assertDispatched('toast');
        $this->assertSame('todo', $task->fresh()->status);
    }

    public function test_update_task_status_to_done_with_state_set_succeeds(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        TaskDetail::factory()->create(['task_id' => $task->id, 'state' => TaskState::Completion->value]);

        Livewire::actingAs($user)->test(Main::class)
            ->call('updateTaskStatus', $task->id, 'done');

        $this->assertSame('done', $task->fresh()->status);
    }

    public function test_reorder_task_to_done_without_state_is_blocked(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();

        $component = Livewire::actingAs($user)->test(Main::class)
            ->call('reorderTask', $task->id, null, 'done');

        $component->assertDispatched('toast');
        $this->assertSame('todo', $task->fresh()->status);
    }

    public function test_bulk_move_to_done_skips_tasks_without_state(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $eligible = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        TaskDetail::factory()->create(['task_id' => $eligible->id, 'state' => TaskState::Completion->value]);
        $blocked = Task::factory()->ownedBy($user)->withStatus('todo')->create();

        $component = Livewire::actingAs($user)->test(Main::class)
            ->set('selectedTasks', [$eligible->id, $blocked->id])
            ->call('bulkMoveStatus', 'done');

        $component->assertDispatched('toast');
        $this->assertSame('done', $eligible->fresh()->status);
        $this->assertSame('todo', $blocked->fresh()->status);
    }

    public function test_collaborator_can_reply_to_a_task(): void
    {
        $creator = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $collaborator = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $task = Task::factory()->create(['user_id' => $creator->id, 'assigned_to' => $assignee->id, 'status' => 'todo']);
        TaskDetail::factory()->create(['task_id' => $task->id, 'collaborators' => [$collaborator->id]]);

        $instance = Livewire::actingAs($collaborator)->test(Main::class)->instance();
        $instance->editingTaskId = $task->id;

        $this->assertTrue($instance->canReplyToTask());

        $instance->taskReplyForm->body = 'یادداشت همکار';
        $instance->postTaskReply(app(ActivityLogger::class));

        $this->assertSame(2, $task->fresh()->replies()->count());
    }

    public function test_uninvolved_user_cannot_reply_even_as_non_collaborator(): void
    {
        $creator = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $stranger = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $task = Task::factory()->create(['user_id' => $creator->id, 'assigned_to' => $assignee->id, 'status' => 'todo']);
        TaskDetail::factory()->create(['task_id' => $task->id, 'collaborators' => []]);

        $component = Livewire::actingAs($stranger)->test(Main::class);
        $component->instance()->editingTaskId = $task->id;

        $this->assertFalse($component->instance()->canReplyToTask());
    }

    public function test_responsible_filter_scopes_to_matching_tasks(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $responsible = User::factory()->create(['status' => 'active']);
        $matching = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        TaskDetail::factory()->create(['task_id' => $matching->id, 'responsible_user_id' => $responsible->id]);
        Task::factory()->ownedBy($user)->withStatus('todo')->create();

        $tasks = Livewire::actingAs($user)->test(Main::class)
            ->set('responsibleFilter', $responsible->id)
            ->instance()->tasks['todo'];

        $this->assertCount(1, $tasks);
        $this->assertSame($matching->id, $tasks[0]['id']);
    }

    public function test_department_filter_scopes_to_matching_tasks(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $dept = Department::create(['code' => 'ENG', 'name' => 'Engineering', 'description' => 'probe dept']);
        $matching = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        TaskDetail::factory()->create(['task_id' => $matching->id, 'department_id' => $dept->code]);
        Task::factory()->ownedBy($user)->withStatus('todo')->create();

        $tasks = Livewire::actingAs($user)->test(Main::class)
            ->set('departmentFilter', $dept->code)
            ->instance()->tasks['todo'];

        $this->assertCount(1, $tasks);
        $this->assertSame($matching->id, $tasks[0]['id']);
    }

    public function test_collaborator_avatars_and_responsible_chip_flow_through_load_tasks(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $responsible = User::factory()->create(['status' => 'active', 'name' => 'Responsible Person']);
        $collaborator = User::factory()->create(['status' => 'active', 'name' => 'Collaborator Person']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        TaskDetail::factory()->create([
            'task_id' => $task->id,
            'responsible_user_id' => $responsible->id,
            'collaborators' => [$collaborator->id],
        ]);

        $loaded = Livewire::actingAs($user)->test(Main::class)->instance()->tasks['todo'][0];

        $this->assertSame('Responsible Person', $loaded['detail']['responsible_user']['name']);
        $this->assertCount(1, $loaded['detail']['collaborator_users']);
        $this->assertSame('Collaborator Person', $loaded['detail']['collaborator_users'][0]['name']);
    }

    public function test_card_popover_data_present_for_attachments_checklist_department_and_responsible(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $responsible = User::factory()->create(['status' => 'active', 'name' => 'Responsible Person']);
        $dept = Department::create(['code' => 'ENG', 'name' => 'Engineering', 'description' => '']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        TaskDetail::factory()->create([
            'task_id' => $task->id,
            'responsible_user_id' => $responsible->id,
            'department_id' => $dept->code,
            'checklist' => [['text' => 'step 1', 'done' => true, 'weight' => 100]],
            'attachments' => [['path' => 'task/attachments/a.png', 'name' => 'a.png', 'mime' => 'image/png', 'size' => 10]],
        ]);

        $component = Livewire::actingAs($user)->test(Main::class);
        $loaded = $component->instance()->tasks['todo'][0];

        $this->assertSame('ENG', $loaded['detail']['department_id']);
        $this->assertSame('Responsible Person', $loaded['detail']['responsible_user']['name']);
        $this->assertSame('step 1', $loaded['detail']['checklist'][0]['text']);
        $this->assertSame('a.png', $loaded['detail']['attachments'][0]['name']);

        $component->assertSeeHtml('Engineering')
            ->assertSeeHtml('Responsible Person')
            ->assertSeeHtml('step 1')
            ->assertSeeHtml('a.png');
    }

    public function test_toolbar_presenter_methods_return_consolidated_class_strings(): void
    {
        $presenter = new TaskBoardPresenter();

        $this->assertSame(
            'rounded-xl bg-[var(--md-sys-color-surface)] shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-shadow)_6%,transparent)]',
            $presenter->toolbarSurface(),
        );
        $this->assertSame('shrink-0 ripple-effect', $presenter->toolbarButtonClass());
        $this->assertSame(
            'ripple-effect w-7 h-7 rounded-lg transition-all duration-200 active:scale-95 flex items-center justify-center',
            $presenter->columnButtonBase(),
        );
        $this->assertSame(
            'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)]',
            $presenter->columnButtonIcon(),
        );
    }

    public function test_tools_bar_renders_unified_toolbar_without_kebab(): void
    {
        Livewire::withoutLazyLoading();
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSee('toggleHeaderDeclutter()', false)
            ->assertSee('$store.density.toggle()', false)
            ->assertSee('wire:click="exportTasks"', false)
            ->assertSee('toggleFavoritesOnly()', false)
            ->assertSee('wire:click="toggleSelectionMode"', false)
            ->assertSee('wire:click="switchTab(', false)
            ->assertSee('title="فیلتر بازه تاریخ"', false)
            ->assertSee(route('tasksheet'), false)
            ->assertDontSee('kebabTrigger', false)
            ->assertDontSee('title="ابزارهای بیشتر"', false)
            ->assertDontSee('toggleMaximize()', false)
            ->assertSee('toggleMaximize(col($el))', false)
            ->assertSee('group/max', false)
            ->assertSee("maximizedColumn === col(\$el) ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]'", false)
            ->assertSee("'max-widget-column': maximizedColumn === \$el.dataset.column", false)
            ->assertDontSee('wire:click="clearFilters"', false)
            ->set('deadlineFilter', 'overdue')
            ->assertDontSee('wire:click="clearFilters"', false)
            ->assertSet('activeFilterCount', 1);
    }

    public function test_date_span_filters_board_by_created_at_and_clears(): void
    {
        Livewire::withoutLazyLoading();
        $user = User::factory()->create(['status' => 'active']);
        $recent = Task::factory()->create(['user_id' => $user->id, 'assigned_to' => $user->id, 'status' => 'todo', 'created_at' => now()]);
        $old = Task::factory()->create(['user_id' => $user->id, 'assigned_to' => $user->id, 'status' => 'todo', 'created_at' => now()->subDays(90)]);

        $today = \Morilog\Jalali\Jalalian::now();

        $component = Livewire::actingAs($user)
            ->test(Main::class)
            ->call('setDateBasis', 'created')
            ->set('fromYear', $today->getYear())->set('fromMonth', $today->getMonth())->set('fromDay', $today->getDay())
            ->set('toYear', $today->getYear())->set('toMonth', $today->getMonth())->set('toDay', $today->getDay())
            ->call('applyDateSpan');

        $ids = collect($component->get('tasks.todo'))->pluck('id')->all();
        $this->assertContains($recent->id, $ids);
        $this->assertNotContains($old->id, $ids);

        $component->call('clearDateSpan');
        $ids = collect($component->get('tasks.todo'))->pluck('id')->all();
        $this->assertContains($old->id, $ids);
    }

    public function test_date_span_rejects_invalid_range_and_locks_applied_state(): void
    {
        Livewire::withoutLazyLoading();
        $user = User::factory()->create(['status' => 'active']);

        $component = Livewire::actingAs($user)
            ->test(Main::class)
            ->set('fromYear', 1405)->set('fromMonth', 5)->set('fromDay', 1)
            ->set('toYear', 1405)->set('toMonth', 1)->set('toDay', 1)
            ->call('applyDateSpan')
            ->assertSet('appliedDateParts', []);

        $this->assertFalse($component->instance()->dateSpanActive());

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);
        $component->set('appliedBasis', 'created');
    }

    public function test_date_basis_switch_only_requeries_when_a_span_is_active(): void
    {
        Livewire::withoutLazyLoading();
        $user = User::factory()->create(['status' => 'active']);
        $today = \Morilog\Jalali\Jalalian::now();

        $component = Livewire::actingAs($user)
            ->test(Main::class)
            ->call('setDateBasis', 'created')
            ->call('setDateBasis', 'bogus')
            ->assertSet('dateBasis', 'created')
            ->assertSet('appliedBasis', 'updated')
            ->set('fromYear', $today->getYear())->set('fromMonth', $today->getMonth())->set('fromDay', $today->getDay())
            ->set('toYear', $today->getYear())->set('toMonth', $today->getMonth())->set('toDay', $today->getDay())
            ->call('applyDateSpan')
            ->assertSet('appliedBasis', 'created')
            ->call('setDateBasis', 'updated')
            ->assertSet('appliedBasis', 'updated')
            ->call('clearDateSpan')
            ->assertSet('fromYear', null)
            ->assertSet('appliedDateParts', [null, null, null, null, null, null]);
    }

    public function test_sort_orders_cards_by_whitelisted_field_and_resets_to_manual_order(): void
    {
        Livewire::withoutLazyLoading();
        $user = User::factory()->create(['status' => 'active']);
        $zulu = Task::factory()->create(['user_id' => $user->id, 'assigned_to' => $user->id, 'status' => 'todo', 'title' => 'Zulu task']);
        $alpha = Task::factory()->create(['user_id' => $user->id, 'assigned_to' => $user->id, 'status' => 'todo', 'title' => 'Alpha task']);

        $order = fn($component) => collect($component->get('tasks.todo'))->pluck('id')->intersect([$zulu->id, $alpha->id])->values()->all();

        $component = Livewire::actingAs($user)->test(Main::class)->call('sortBy', 'title');
        $this->assertSame([$alpha->id, $zulu->id], $order($component));

        $component->call('sortBy', 'title');
        $this->assertSame([$zulu->id, $alpha->id], $order($component));

        $component->call('sortBy', 'password')->assertSet('sort', 'title');

        $component->call('resetSort')->assertSet('sort', '')->assertSet('sortDir', 'asc');
    }

    public function test_active_tab_is_remembered_across_mounts(): void
    {
        Livewire::withoutLazyLoading();
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)->test(Main::class)->call('switchTab', 'assigned-tasks')->assertSet('activeTab', 'assigned-tasks');
        Livewire::actingAs($user)->test(Main::class)->assertSet('activeTab', 'assigned-tasks');
    }

    public function test_is_my_tasks_computed_reflects_active_tab(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('activeTab', 'my-tasks')
            ->assertSet('isMyTasks', true)
            ->call('switchTab', 'assigned-tasks')
            ->assertSet('activeTab', 'assigned-tasks')
            ->assertSet('isMyTasks', false)
            ->call('switchTab', 'my-tasks')
            ->assertSet('isMyTasks', true);
    }

    public function test_export_tasks_streams_xlsx_of_every_task_the_user_owns_or_is_assigned(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $colleague = User::factory()->create(['status' => 'active']);

        $owned = Task::factory()->ownedBy($user)->withStatus('todo')->create(['title' => 'Export probe owned']);
        $assigned = Task::factory()->delegatedBy($colleague, $user)->withStatus('in-progress')->create(['title' => 'Export probe assigned']);
        Task::factory()->ownedBy($stranger)->withStatus('todo')->create(['title' => 'Export probe stranger only']);

        $response = app(\App\Livewire\Dashboard\TaskBoard\Actions\ExportTasksAction::class)->execute((int) $user->id);

        $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('tasks-', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));

        ob_start();
        ($response->getCallback())();
        $bytes = (string) ob_get_clean();
        $this->assertNotEmpty($bytes);

        $tmp = tempnam(sys_get_temp_dir(), 'fateh_export_test_');
        file_put_contents($tmp, $bytes);

        $rows = [];
        $reader = new Reader();
        $reader->open($tmp);
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
            break;
        }
        $reader->close();
        @unlink($tmp);

        $this->assertNotEmpty($rows, 'xlsx must contain at least the header row');
        $this->assertStringContainsString('عنوان', (string) $rows[0][1], 'header row must carry the title column');

        $dataRows = array_slice($rows, 1);
        $this->assertCount(2, $dataRows, 'export must include only tasks the user owns or is assigned to — never a stranger\'s');

        $titles = array_map(fn(array $r) => (string) $r[1], $dataRows);
        $this->assertContains('Export probe owned', $titles);
        $this->assertContains('Export probe assigned', $titles);
        $this->assertNotContains('Export probe stranger only', $titles);
    }

    public function test_export_tasks_endpoint_is_scoped_to_the_authenticated_user(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);

        Task::factory()->ownedBy($user)->withStatus('todo')->create(['title' => 'Wire export owned']);
        Task::factory()->ownedBy($stranger)->withStatus('todo')->create(['title' => 'Wire export stranger']);

        $component = Livewire::actingAs($user)->test(Main::class)->call('exportTasks');

        $download = $component->effects['download'];
        $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $download['contentType']);
        $this->assertStringContainsString('tasks-', $download['name']);
        $this->assertStringContainsString('.xlsx', $download['name']);

        $tmp = tempnam(sys_get_temp_dir(), 'fateh_wire_export_');
        file_put_contents($tmp, base64_decode($download['content']));

        $rows = [];
        $reader = new Reader();
        $reader->open($tmp);
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
            break;
        }
        $reader->close();
        @unlink($tmp);

        $titles = array_map(fn(array $r) => (string) $r[1], array_slice($rows, 1));
        $this->assertContains('Wire export owned', $titles);
        $this->assertNotContains('Wire export stranger', $titles, 'the export endpoint must scope to the authenticated user — never leak a stranger\'s tasks');
    }

    public function test_column_count_badge_tone_follows_column_config_color_not_a_local_override(): void
    {
        $presenter = new TaskBoardPresenter();
        $config = $presenter->columnConfig();

        $this->assertSame('primary', $config['todo']['color']);
        $this->assertSame('secondary', $config['in-progress']['color']);
        $this->assertSame('error', $config['pending']['color']);
        $this->assertSame('tertiary', $config['done']['color']);
    }

    public function test_toggle_level_up_form_prefills_project_name_from_task_title(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['title' => 'Ship the launch page']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('editTask', $task->id)
            ->call('toggleLevelUpForm')
            ->assertOk()
            ->assertSet('showLevelUpForm', true)
            ->assertSet('levelUpForm.name', 'Ship the launch page');
    }

    public function test_create_project_from_task_stashes_the_pending_name_without_creating_a_project_row(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['title' => 'Ship the launch page']);
        $countBefore = \App\Models\Project::count();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('editTask', $task->id)
            ->call('toggleLevelUpForm')
            ->set('levelUpForm.name', 'Launch Page Project')
            ->call('createProjectFromTask')
            ->assertOk()
            ->assertSet('showLevelUpForm', false)
            ->assertSet('form.pendingProjectName', 'Launch Page Project');

        $this->assertSame($countBefore, \App\Models\Project::count(), 'stashing the pending project name must not create a Project row until the task is actually saved');
    }

    public function test_closing_the_modal_after_stashing_a_pending_project_leaves_no_orphan_project(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        $countBefore = \App\Models\Project::count();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('editTask', $task->id)
            ->call('toggleLevelUpForm')
            ->set('levelUpForm.name', 'Abandoned Project')
            ->call('createProjectFromTask')
            ->call('openCreateModal');

        $this->assertSame($countBefore, \App\Models\Project::count(), 'abandoning the modal without saving the task must never leave an orphan Project row');
    }

    public function test_cancel_pending_project_clears_the_stash(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('editTask', $task->id)
            ->call('toggleLevelUpForm')
            ->set('levelUpForm.name', 'Changed My Mind')
            ->call('createProjectFromTask')
            ->call('cancelPendingProject')
            ->assertOk()
            ->assertSet('form.pendingProjectName', null);
    }

    public function test_saving_a_task_with_a_pending_project_creates_and_links_it(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('editTask', $task->id)
            ->call('toggleLevelUpForm')
            ->set('levelUpForm.name', 'Launch Page Project')
            ->call('createProjectFromTask')
            ->call('updateTask')
            ->assertHasNoErrors();

        $project = \App\Models\Project::where('name', 'Launch Page Project')->firstOrFail();
        $this->assertSame($user->id, $project->owner_id);
        $this->assertSame($project->id, $task->fresh()->project_id);
    }

    public function test_create_project_from_task_requires_a_name(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('editTask', $task->id)
            ->call('toggleLevelUpForm')
            ->set('levelUpForm.name', '')
            ->call('createProjectFromTask')
            ->assertOk()
            ->assertHasErrors(['levelUpForm.name' => 'required']);
    }

    public function test_url_bound_filters_carry_url_attribute_with_the_expected_query_param(): void
    {
        $expected = [
            'search' => 'q',
            'deadlineFilter' => 'deadline',
            'priorityFilter' => 'priority',
            'labelFilter' => 'label',
            'responsibleFilter' => 'responsible',
            'departmentFilter' => 'department',
            'schemeFilter' => 'scheme',
            'unitFilter' => 'unit',
            'sectionFilter' => 'section',
            'assigneeFilter' => 'assignee',
            'actionSourceDomainFilter' => 'actionSourceDomain',
            'actionSourceFilter' => 'actionSource',
        ];

        foreach ($expected as $property => $param) {
            $reflection = new \ReflectionProperty(Main::class, $property);
            $attributes = $reflection->getAttributes(\Livewire\Attributes\Url::class);

            $this->assertNotEmpty($attributes, "{$property} must carry #[Url] so filters survive refresh/share");
            $this->assertSame($param, $attributes[0]->newInstance()->as, "{$property}'s #[Url] query param name is wrong");
        }
    }

    public function test_meta_tab_renders_schema_mode_inputs_for_a_project_with_custom_schema(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = \App\Models\Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['custom_schema' => ['vendor' => ['label' => 'تأمین‌کننده']]],
        ]);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('openCreateModal')
            ->set('form.projectId', $project->id)
            ->assertSeeHtml('این فیلدها توسط مدیر پروژه برای این پروژه تعریف شده است.')
            ->assertSeeHtml('wire:model="form.meta.vendor"')
            ->assertSeeHtml('تأمین‌کننده')
            ->assertDontSeeHtml('wire:click="addMetaKey"');
    }

    public function test_meta_tab_renders_free_mode_add_row_ui_without_a_project_schema(): void
    {
        $owner = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('openCreateModal')
            ->assertSeeHtml('هنوز دیتای سفارشی ثبت نشده است.')
            ->assertSeeHtml('wire:model="newMetaKey"')
            ->assertSeeHtml('wire:click="addMetaKey"')
            ->assertDontSeeHtml('این فیلدها توسط مدیر پروژه برای این پروژه تعریف شده است.');
    }

    public function test_add_meta_key_enforces_the_key_regex_server_side(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $toast = 'کلید دیتای سفارشی فقط می‌تواند شامل حروف کوچک انگلیسی، رقم و زیرخط باشد.';

        foreach (['Bad Key', 'a.b', 'a"b'] as $badKey) {
            Livewire::withoutLazyLoading();
            Livewire::actingAs($owner)
                ->test(Main::class)
                ->call('openCreateModal')
                ->set('newMetaKey', $badKey)
                ->set('newMetaValue', 'مقدار')
                ->call('addMetaKey')
                ->assertDispatched('toast', message: $toast, type: 'error')
                ->assertSet('form.meta', []);
        }

        Livewire::withoutLazyLoading();
        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('openCreateModal')
            ->set('newMetaKey', 'cost_center')
            ->set('newMetaValue', 'CC-9')
            ->call('addMetaKey')
            ->assertNotDispatched('toast')
            ->assertSet('form.meta', ['cost_center' => 'CC-9'])
            ->assertSet('newMetaKey', '');
    }

    public function test_pending_approval_card_shows_last_touched_caption_for_the_latest_reply_author(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $editor = User::factory()->create(['status' => 'active', 'name' => 'سارا ویرایشگر']);
        $project = \App\Models\Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['requires_approval' => true],
        ]);
        $task = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create(['approved_at' => null]);
        $task->replies()->create(['user_id' => $editor->id, 'body' => 'پاسخ نهایی']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->assertSeeHtml('منتظر تأیید')
            ->assertSeeHtml('ویرایش توسط')
            ->assertSeeHtml('سارا ویرایشگر');
    }

    public function test_pending_approval_card_without_replies_hides_the_last_touched_caption(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = \App\Models\Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['requires_approval' => true],
        ]);
        $task = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create(['approved_at' => null]);
        \App\Models\Reply::where('repliable_type', Task::class)->where('repliable_id', $task->id)->delete();

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->assertSeeHtml('منتظر تأیید')
            ->assertDontSeeHtml('ویرایش توسط');
    }

    public function test_approve_task_is_blocked_for_a_non_owner_member(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $member = User::factory()->create(['status' => 'active']);
        $project = \App\Models\Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [$member->id],
            'departments' => [],
            'settings' => ['requires_approval' => true],
        ]);
        $task = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create([
            'approved_at' => null,
            'assigned_to' => $member->id,
        ]);

        Livewire::actingAs($member)
            ->test(Main::class)
            ->call('approveTask', $task->id)
            ->assertHasNoErrors();

        $this->assertNull($task->fresh()->approved_at);
        $this->assertFalse(app(\App\Services\ProjectTask\ApproveTaskAction::class)->execute($task->fresh(), $member));
        $this->assertNull($task->fresh()->approved_at);
    }

    public function test_editing_task_id_is_locked_against_client_tampering(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        $otherTask = Task::factory()->ownedBy($user)->withStatus('todo')->create();

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($user)->test(Main::class)
            ->call('editTask', $task->id)
            ->set('editingTaskId', $otherTask->id);
    }

    public function test_reorder_action_inserts_rank_strictly_between_neighbor_siblings(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $first = Task::factory()->ownedBy($user)->withStatus('todo')->create(['rank' => 'b']);
        $second = Task::factory()->ownedBy($user)->withStatus('todo')->create(['rank' => 'q']);
        $moving = Task::factory()->ownedBy($user)->withStatus('todo')->create(['rank' => 'z']);

        $this->actingAs($user);
        app(ReorderTaskAction::class)->execute(Task::find($moving->id), $second->id);

        $fresh = Task::find($moving->id);
        $this->assertSame('todo', $fresh->status);
        $this->assertTrue($first->rank < $fresh->rank && $fresh->rank < $second->rank);
    }

    public function test_reorder_action_applies_target_status_and_places_rank_in_the_target_column(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $sibling = Task::factory()->ownedBy($user)->withStatus('in-progress')->create(['rank' => 'm']);
        $moving = Task::factory()->ownedBy($user)->withStatus('todo')->create(['rank' => 'a']);

        $this->actingAs($user);
        app(ReorderTaskAction::class)->execute(Task::find($moving->id), $sibling->id, 'in-progress');

        $fresh = Task::find($moving->id);
        $this->assertSame('in-progress', $fresh->status);
        $this->assertTrue($fresh->rank < $sibling->rank);
    }

    public function test_reorder_action_moving_to_done_without_state_is_a_silent_no_op(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $moving = Task::factory()->ownedBy($user)->withStatus('todo')->create(['rank' => 'a']);

        $this->actingAs($user);
        app(ReorderTaskAction::class)->execute(Task::find($moving->id), null, 'done');

        $fresh = Task::find($moving->id);
        $this->assertSame('todo', $fresh->status);
        $this->assertSame('a', $fresh->rank);
    }

    public function test_reorder_action_falls_back_to_appending_when_the_before_task_id_is_outside_the_rank_set(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $sibling = Task::factory()->ownedBy($user)->withStatus('todo')->create(['rank' => 'b']);
        $moving = Task::factory()->ownedBy($user)->withStatus('todo')->create(['rank' => 'z']);
        $outsider = Task::factory()->ownedBy($user)->withStatus('in-progress')->create(['rank' => 'x']);

        $this->actingAs($user);
        app(ReorderTaskAction::class)->execute(Task::find($moving->id), $outsider->id);

        $fresh = Task::find($moving->id);
        $this->assertSame('todo', $fresh->status);
        $this->assertNotNull($fresh->rank);
        $this->assertTrue($fresh->rank > $sibling->rank);
    }

    public function test_duplicate_action_returns_a_new_persisted_task_built_from_the_source_snapshot(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->actingAs($user);
        $source = Task::factory()->ownedBy($user)->withStatus('done')->create([
            'deadline' => now()->addDays(10),
            'labels' => ['probe-label'],
        ]);
        $source->detail()->create(['checklist' => [
            ['text' => 'step 1', 'done' => true, 'weight' => 70],
            ['text' => 'step 2', 'done' => false, 'weight' => 30],
        ]]);

        $form = Livewire::test(Main::class)->instance()->form;
        $duplicate = app(DuplicateTaskAction::class)->execute($source, $form);

        $this->assertTrue($duplicate->exists);
        $this->assertNotSame($source->id, $duplicate->id);
        $this->assertSame($source->title . ' (کپی)', $duplicate->title);
        $this->assertSame('todo', $duplicate->status);

        $checklist = $duplicate->detail->checklist;
        $this->assertCount(2, $checklist);
        $this->assertFalse($checklist[0]['done'], 'cloned checklist items must reset to not-done');
        $this->assertFalse($checklist[1]['done']);
        $this->assertSame(70, $checklist[0]['weight']);
        $this->assertSame('step 2', $checklist[1]['text']);

        $this->assertSame($source->deadline->toDateString(), $duplicate->deadline->toDateString());
        $this->assertSame('12:00:00', $duplicate->deadline->format('H:i:s'));
    }

    public function test_cycle_priority_action_walks_the_full_loop_and_wraps_back_to_low(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['priority' => 'low']);
        $this->actingAs($user);

        foreach ([TaskPriority::Medium, TaskPriority::High, TaskPriority::Urgent, TaskPriority::Low] as $next) {
            $cycled = app(CyclePriorityAction::class)->execute($task->id);
            $this->assertSame($next, $cycled->priority);
            $this->assertSame($next, Task::find($task->id)->priority);
        }
    }

    public function test_cycle_priority_action_returns_null_for_ticket_archived_and_missing_tasks(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->actingAs($user);
        $ticketTask = Task::factory()->ownedBy($user)->withStatus('todo')->create(['priority' => 'low', 'ticket_id' => Ticket::factory()->create()->id]);
        $archivedTask = Task::factory()->ownedBy($user)->withStatus('done')->create(['priority' => 'low', 'archived_at' => now()]);
        $missingId = (int) Task::max('id') + 1000;

        $this->assertNull(app(CyclePriorityAction::class)->execute($ticketTask->id));
        $this->assertNull(app(CyclePriorityAction::class)->execute($archivedTask->id));
        $this->assertNull(app(CyclePriorityAction::class)->execute($missingId));

        $this->assertSame(TaskPriority::Low, Task::find($ticketTask->id)->priority);
        $this->assertSame(TaskPriority::Low, Task::find($archivedTask->id)->priority);
    }

    public function test_cycle_priority_action_refreshes_rank_for_the_new_priority_tier(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $sibling = Task::factory()->ownedBy($user)->withStatus('todo')->create(['priority' => 'low', 'rank' => 'b']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['priority' => 'high', 'rank' => 'd']);
        $this->actingAs($user);

        app(CyclePriorityAction::class)->execute($task->id);

        $fresh = Task::find($task->id);
        $this->assertSame(TaskPriority::Urgent, $fresh->priority);
        $this->assertTrue($fresh->rank < $sibling->rank);
    }

    public function test_approve_action_returns_false_for_a_null_actor(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => [], 'settings' => ['requires_approval' => true]]);
        $task = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create(['approved_at' => null]);

        $this->assertFalse(app(ApproveTaskAction::class)->execute(Task::find($task->id), null));
        $this->assertNull(Task::find($task->id)->approved_at);
    }

    public function test_status_action_and_bulk_move_refuse_an_approved_task_for_a_non_owner(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $member = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$member->id], 'departments' => [], 'settings' => ['requires_approval' => true]]);
        $task = Task::factory()->forProject($project)->withStatus('done')->create(['user_id' => $owner->id, 'assigned_to' => $member->id, 'approved_at' => now(), 'approved_by' => $owner->id]);

        $this->actingAs($member);
        $this->assertFalse(app(UpdateTaskStatusAction::class)->execute($task->id, 'in-progress'));

        $result = app(BulkMoveTasksAction::class)->execute([$task->id], 'in-progress');
        $this->assertSame(0, $result['moved']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame('done', Task::find($task->id)->status);
        $this->assertTrue(Task::find($task->id)->is_frozen);

        $this->actingAs($owner);
        $this->assertFalse(Task::find($task->id)->is_frozen);
        $this->assertTrue(app(UpdateTaskStatusAction::class)->execute($task->id, 'in-progress'));
    }

    public function test_bulk_assign_and_bulk_delete_skip_a_frozen_task(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $member = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $other = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$member->id, $other->id], 'departments' => [], 'settings' => ['requires_approval' => true]]);
        $task = Task::factory()->forProject($project)->withStatus('done')->create(['user_id' => $member->id, 'assigned_to' => $member->id, 'approved_at' => now(), 'approved_by' => $owner->id]);

        $this->actingAs($member);
        $this->assertSame(0, app(BulkAssignTasksAction::class)->execute([$task->id], $other->id));
        $this->assertSame(0, app(BulkDeleteTasksAction::class)->execute([$task->id]));
        $this->assertSame($member->id, Task::find($task->id)->assigned_to);
    }

    public function test_modal_opens_read_only_for_a_member_and_editable_for_the_owner_on_a_frozen_task(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $member = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$member->id], 'departments' => [], 'settings' => ['requires_approval' => true]]);
        $task = Task::factory()->forProject($project)->withStatus('done')->create(['user_id' => $member->id, 'assigned_to' => $member->id, 'approved_at' => now(), 'approved_by' => $owner->id]);

        Livewire::actingAs($member)->test(Main::class)->call('editTask', $task->id)->assertSet('isReadOnly', true);
        Livewire::actingAs($owner)->test(Main::class)->call('editTask', $task->id)->assertSet('isReadOnly', false);
    }

    public function test_reopen_is_refused_for_an_archived_approved_task_and_a_task_of_a_deleted_project_is_not_frozen(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $member = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$member->id], 'departments' => [], 'settings' => ['requires_approval' => true]]);
        $task = Task::factory()->forProject($project)->withStatus('done')->create(['user_id' => $member->id, 'assigned_to' => $member->id, 'approved_at' => now(), 'approved_by' => $owner->id, 'archived_at' => now()]);

        $this->actingAs($owner);
        $this->assertFalse(app(ReopenTaskAction::class)->execute(Task::find($task->id), $owner));

        $project->delete();
        $this->actingAs($member);
        $fresh = Task::find($task->id);
        $this->assertFalse($fresh->isFrozenFor($member));
        $this->assertFalse($fresh->isPendingApproval());
    }

    public function test_approve_action_returns_false_when_the_task_is_not_pending_approval(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => [], 'settings' => ['requires_approval' => true]]);
        $task = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create(['approved_at' => now()]);

        $this->actingAs($owner);
        $this->assertFalse(app(ApproveTaskAction::class)->execute(Task::find($task->id), $owner));
        $this->assertNull(Task::find($task->id)->approved_by);
    }

    public function test_approve_action_returns_false_when_the_actor_cannot_approve(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $member = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$member->id], 'departments' => [], 'settings' => ['requires_approval' => true]]);
        $task = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create(['approved_at' => null]);

        $this->actingAs($member);
        $this->assertFalse(app(ApproveTaskAction::class)->execute(Task::find($task->id), $member));
        $this->assertNull(Task::find($task->id)->approved_at);
    }

    public function test_approve_action_records_approved_columns_for_the_project_owner(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => [], 'settings' => ['requires_approval' => true]]);
        $task = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create(['approved_at' => null]);

        $this->actingAs($owner);
        $this->assertTrue(app(ApproveTaskAction::class)->execute(Task::find($task->id), $owner));

        $fresh = Task::find($task->id);
        $this->assertNotNull($fresh->approved_at);
        $this->assertSame($owner->id, $fresh->approved_by);
    }

    public function test_update_task_status_action_rejects_an_invalid_status(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();

        $this->actingAs($user);
        $this->assertFalse(app(UpdateTaskStatusAction::class)->execute($task->id, 'bogus'));
        $this->assertSame('todo', Task::find($task->id)->status);
    }

    public function test_update_task_status_action_blocks_done_without_state(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();

        $this->actingAs($user);
        $this->assertFalse(app(UpdateTaskStatusAction::class)->execute($task->id, 'done'));
        $this->assertSame('todo', Task::find($task->id)->status);
    }

    public function test_update_task_status_action_returns_true_for_same_status_without_writing(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();

        $this->actingAs($user);
        $updatedAt = Task::find($task->id)->updated_at;
        $this->travel(5)->seconds();

        $this->assertTrue(app(UpdateTaskStatusAction::class)->execute($task->id, 'todo'));
        $this->assertTrue($updatedAt->equalTo(Task::find($task->id)->updated_at));
    }

    public function test_update_task_status_action_moves_the_task_and_assigns_the_next_rank(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $sibling = Task::factory()->ownedBy($user)->withStatus('in-progress')->create(['rank' => 'm']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['rank' => 'a']);

        $this->actingAs($user);
        $this->assertTrue(app(UpdateTaskStatusAction::class)->execute($task->id, 'in-progress'));

        $fresh = Task::find($task->id);
        $this->assertSame('in-progress', $fresh->status);
        $this->assertTrue($fresh->rank < $sibling->rank);
    }

    public function test_collaborator_resolver_maps_ids_to_name_and_avatar_entries(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $collaboratorOne = User::factory()->create(['status' => 'active', 'role' => 'user', 'name' => 'همکار یکم']);
        $collaboratorTwo = User::factory()->create(['status' => 'active', 'role' => 'user', 'name' => 'Second Collaborator']);
        $taskOne = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        TaskDetail::factory()->create(['task_id' => $taskOne->id, 'collaborators' => [$collaboratorOne->id]]);
        $taskTwo = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        TaskDetail::factory()->create(['task_id' => $taskTwo->id, 'collaborators' => [$collaboratorTwo->id, $collaboratorOne->id]]);

        $map = app(BoardCollaboratorResolver::class)->resolve(
            Task::with('detail')->whereIn('id', [$taskOne->id, $taskTwo->id])->get(),
        );

        $this->assertCount(2, $map);
        $this->assertSame($collaboratorOne->id, $map[$collaboratorOne->id]['id']);
        $this->assertSame('همکار یکم', $map[$collaboratorOne->id]['name']);
        $this->assertSame('Second Collaborator', $map[$collaboratorTwo->id]['name']);
        $this->assertArrayHasKey('avatar_url', $map[$collaboratorOne->id]);
        $this->assertNotSame('', $map[$collaboratorOne->id]['avatar_url']);
    }

    public function test_collaborator_resolver_returns_an_empty_map_without_collaborators(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $withDetail = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        TaskDetail::factory()->create(['task_id' => $withDetail->id, 'collaborators' => []]);
        $withoutDetail = Task::factory()->ownedBy($user)->withStatus('todo')->create();

        $this->assertSame([], app(BoardCollaboratorResolver::class)->resolve(
            Task::with('detail')->whereIn('id', [$withDetail->id, $withoutDetail->id])->get(),
        ));
    }

    public function test_column_task_count_renders_persian_digits(): void
    {
        $creator = User::factory()->create(['status' => 'active']);

        $component = Livewire::actingAs($creator)
            ->test(Main::class)
            ->set('form.newTitle', 'persian digits probe')
            ->set('form.newDescription', 'probe')
            ->call('createTask')
            ->assertHasNoErrors();

        $this->assertMatchesRegularExpression('/rounded-sm text-\[10px\] font-bold leading-none[^>]*>\s*[۰-۹]/u', $component->html());
    }

    public function test_user_picker_serves_candidates_with_the_modal_closed_for_the_delegate_popover(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $seededIds = [];
        foreach (range(1, 15) as $i) {
            $seededIds[] = User::factory()->create(['status' => 'active', 'name' => 'PkProbe Popover ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT)])->id;
        }

        $component = Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('isModalOpen', false)
            ->set('userSearch', 'PkProbe Popover');

        $results = $component->instance()->userPickerResults();
        $this->assertCount(12, $results['candidates']);
        $this->assertTrue($results['hasMore']);
        $ids = array_column($results['candidates'], 'id');
        $this->assertContains($seededIds[0], $ids);
        $this->assertNotContains($user->id, $ids);
    }

    public function test_user_picker_respects_the_limit_and_flips_has_more_when_all_matches_are_loaded(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $seededIds = [];
        foreach (range(1, 15) as $i) {
            $seededIds[] = User::factory()->create(['status' => 'active', 'name' => 'PkProbe Batch ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT)])->id;
        }

        $component = Livewire::actingAs($user)->test(Main::class)->call('openCreateModal');

        $open = $component->instance()->userPickerResults();
        $this->assertCount(12, $open['candidates']);
        $this->assertTrue($open['hasMore']);

        $component->set('userSearch', 'PkProbe Batch');
        $firstWindow = $component->instance()->userPickerResults();
        $this->assertCount(12, $firstWindow['candidates']);
        $this->assertTrue($firstWindow['hasMore']);

        $component->call('loadMoreUsers')->assertSet('userPickerLimit', 24);
        $secondWindow = $component->instance()->userPickerResults();
        $this->assertFalse($secondWindow['hasMore']);
        $this->assertSame($seededIds, array_column($secondWindow['candidates'], 'id'));
    }

    public function test_user_picker_search_matches_user_names_and_department_names_and_is_empty_for_garbage(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Department::create(['code' => 'PKN', 'name' => 'PkProbe Search Dept', 'description' => 'probe', 'units' => [], 'sections' => []]);
        $member = User::factory()->create(['status' => 'active', 'name' => 'PkProbe Member']);
        Profile::factory()->create(['user_id' => $member->id, 'department_id' => 'PKN']);

        $component = Livewire::actingAs($user)->test(Main::class)->call('openCreateModal');

        $component->set('userSearch', 'PkProbe Member');
        $byName = $component->instance()->userPickerResults();
        $this->assertSame([$member->id], array_column($byName['candidates'], 'id'));
        $this->assertFalse($byName['hasMore']);

        $component->set('userSearch', 'PkProbe Search Dept');
        $byDepartment = $component->instance()->userPickerResults();
        $this->assertSame([$member->id], array_column($byDepartment['candidates'], 'id'));

        $component->set('userSearch', 'zzqq-pkprobe-no-such-person-97531');
        $garbage = $component->instance()->userPickerResults();
        $this->assertSame([], $garbage['candidates']);
        $this->assertFalse($garbage['hasMore']);
    }

    public function test_user_picker_department_filter_returns_only_that_departments_users(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Department::create(['code' => 'PKF', 'name' => 'PkProbe Filter One', 'description' => 'probe', 'units' => [], 'sections' => []]);
        Department::create(['code' => 'PKG', 'name' => 'PkProbe Filter Two', 'description' => 'probe', 'units' => [], 'sections' => []]);
        $one = User::factory()->create(['status' => 'active', 'name' => 'PkProbe Filter Aaa']);
        $two = User::factory()->create(['status' => 'active', 'name' => 'PkProbe Filter Zzz']);
        Profile::factory()->create(['user_id' => $one->id, 'department_id' => 'PKF']);
        Profile::factory()->create(['user_id' => $two->id, 'department_id' => 'PKF']);
        $elsewhere = User::factory()->create(['status' => 'active', 'name' => 'PkProbe Filter Other']);
        Profile::factory()->create(['user_id' => $elsewhere->id, 'department_id' => 'PKG']);

        $component = Livewire::actingAs($user)->test(Main::class)->call('openCreateModal');
        $component->set('userDept', 'PKF');

        $results = $component->instance()->userPickerResults();
        $this->assertSame([$one->id, $two->id], array_column($results['candidates'], 'id'));
        $this->assertFalse($results['hasMore']);
    }

    public function test_user_picker_pins_selected_people_under_a_search_that_does_not_match_them(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active', 'name' => 'PkPin Assignee']);
        $collaborator = User::factory()->create(['status' => 'active', 'name' => 'PkPin Collaborator']);
        $target = User::factory()->create(['status' => 'active', 'name' => 'PkPin Target']);

        $component = Livewire::actingAs($user)->test(Main::class)->call('openCreateModal');
        $component
            ->set('form.selectedAssignee', $assignee->id)
            ->set('form.collaborators', [(string) $collaborator->id])
            ->set('userSearch', 'PkPin Target');

        $results = $component->instance()->userPickerResults();
        $this->assertSame(
            [$assignee->id, $collaborator->id, $target->id],
            array_column($results['candidates'], 'id'),
            'pinned assignee and string-id collaborator must surface above the search match, in name order'
        );
        $this->assertFalse($results['hasMore']);
    }

    public function test_unknown_tab_value_never_widens_the_board_to_other_users_tasks()
    {
        $user = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $mine = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        $theirs = Task::factory()->ownedBy($stranger)->withStatus('todo')->create();

        $component = Livewire::actingAs($user)->test(Main::class)->call('switchTab', 'x');

        $component->assertSet('activeTab', 'my-tasks');
        $ids = collect($component->get('tasks'))->flatten(1)->pluck('id')->all();
        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($theirs->id, $ids);
    }
}
